<?php
/**
 * Specific frontend code for WP Job Manager.
 *
 * @package GoFetch/WPJM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $gofetch_wpjm_frontend;

class GoFetch_WPJM_Frontend {

	public function __construct() {

		if ( is_admin() ) {
			return;
		}

		add_filter( 'the_job_description', array( $this, 'append_external_url' ), 10 );
		add_filter( 'the_job_description', array( $this, 'append_source' ), 11 );
		add_filter( 'goft_wpjm_read_more', array( $this, 'remove_more_on_scraped_descriptions' ), 10, 3 );
		add_filter( 'goft_wpjm_post_insert', array( $this, 'maybe_expire_job' ), 10, 2 );
		add_filter( 'wp', array( $this, 'maybe_override_wpjm_applications' ), 10 );
		add_action( 'wp', array( $this, 'maybe_override_application_details_url' ), 11 );
		add_action( 'wp', array( $this, 'maybe_override_wpjm_resume_manager' ), 25 );
		add_filter( 'get_canonical_url', array( $this, 'maybe_add_rel_canonical_to_header' ), 10, 2 );
	}

	/**
	 * Append the job details external URL to the post content.
	 */
	public function append_external_url( $content ) {
		global $post, $goft_wpjm_options;

		if ( ! $post || ! is_single() ) {
			return $content;
		}

		$append_external_url = $goft_wpjm_options->allow_visitors_apply || is_user_logged_in();

		if ( ! apply_filters( 'goft_wpjm_append_external_url', $append_external_url ) || ! ( $is_external = get_post_meta( $post->ID, '_goft_wpjm_is_external', true ) ) ) {
			return $content;
		}

		// Skip earlier if the content already contains the '[...]' with the external url.
		if ( false !== stripos( $content, '[&#8230;]' ) ) {
			return $content;
		}

		$source_data = get_post_meta( $post->ID, '_goft_source_data', true );

		$link = get_post_meta( $post->ID, '_goft_external_link', true );
		$link = GoFetch_WPJM_Importer::add_query_args( $source_data, $link );

		// If the content is wrapped in <p> tags make sure the <a> is added inside it.
		$content_inline = '/p>' === trim( substr( $content, -4 ) ) ? substr( $content, 0, -5 ) : $content;

		$read_more = apply_filters( 'goft_wpjm_read_more', $goft_wpjm_options->read_more_text, $content, $link );

		if ( $read_more ) {

			$link_atts = apply_filters( 'goft_wpjm_read_more_link_attributes', array(
				'class'  => 'goftj-exernal-link',
				'href'   => esc_url( $link ),
				'rel'    => 'nofollow',
				'target' => '_blank',
			), $post );

			$content = sprintf( '%1$s %2$s', $content_inline, html( 'a', $link_atts, $read_more ) ) . '</p>';
		} else {
			$content = $content_inline;
		}
		return $content;
	}

	/**
	 * Append the source job URL to the post content.
	 */
	public function append_source( $content ) {
		global $post;

		if ( ! $post || ! is_single() ) {
			return $content;
		}

		if ( ! apply_filters( 'goft_wpjm_append_source_data', true ) || ! ( $is_external = get_post_meta( $post->ID, '_goft_wpjm_is_external', true ) ) ) {
			return $content;
		}

		$source_data = get_post_meta( $post->ID, '_goft_source_data', true );

		if ( empty( $source_data['name'] ) || empty( $source_data['website'] ) ) {
			return $content;
		}

?>
		<style type="text/css">
			.entry-content a.goftj-logo-exernal-link {
				box-shadow: none;
			}
			.goftj-source-logo {
				height: 32px;
			}
			.goftj-logo-exernal-link {
				display: -webkit-inline-box;
			}
		</style>
<?php
		$external_link = get_post_meta( $post->ID, '_goft_external_link', true );

		$link = GoFetch_WPJM_Importer::add_query_args( $source_data, $external_link );

		if ( ! empty( $source_data['logo'] ) ) {

			$atts = apply_filters( 'goft_wpjm_source_image_attributes', array(
				'src'    => esc_url( $source_data['logo'] ),
				'rel'    => 'nofollow',
				'title'  => esc_attr( $source_data['name'] ),
			) );
			$source = html( 'img class="goftj-source-logo"', $atts, '&nbsp;' );

		} else {
			$source = html( 'span class="goftj-source"', $source_data['name'] );
		}

		if ( $link ) {

			$atts = apply_filters( 'goft_wpjm_source_link_attributes', array(
				'class'  => 'goftj-logo-exernal-link',
				'rel'    => 'nofollow',
				'href'   => esc_url( $link ),
				'title'  => esc_attr( $source_data['name'] ),
				'target' => '_blank',
			), $post );

			$source = html( 'a', $atts, $source );
		}

		$source = html( 'p', sprintf( __( '<em class="goft-source">%1$s</em> %2$s', 'gofetch-wpjm' ), __( 'Source:', 'gofetch-wpjm' ), $source ) );

		return $content . $source;
	}

	/**
	 * Overrides the 'WPJM Applications' add-on for imported jobs.
	 */
	public function maybe_override_wpjm_applications() {
		global $job_manager, $wp_filter, $post;

		if ( ! class_exists( 'WP_Job_Manager_Applications_Apply' ) ) {
			return;
		}

		// Skip on 'regular' jobs.
		if ( ! is_singular( 'job_listing' ) || ! get_post_meta( $post->ID, '_goft_wpjm_is_external', true ) ) {
			return;
		}

		$application = get_the_job_application_method( $post );

		// Don't override if the application is done through email.
		if ( ! empty( $application->type ) && 'email' === $application->type ) {
			return;
		}

		// Get the instance for the current application.
		if ( ! empty( $wp_filter['job_manager_application_details_url']->callbacks ) ) {
			$this->remove_applications_action( 'WP_Job_Manager_Applications_Apply', 'application_form', 20 );
		}
		add_action( 'job_manager_application_details_url', array( $job_manager->post_types, 'application_details_url' ) );
	}

	/**
	 * Override the default applications URL markup with a custom one, if requested.
	 */
	public function maybe_override_application_details_url() {
		global $job_manager, $wp_filter, $post;

		// Skip on 'regular' jobs.
		if ( ! is_singular( 'job_listing' ) || ! get_post_meta( $post->ID, '_goft_wpjm_is_external', true ) ) {
			return;
		}

		$application = get_the_job_application_method( $post );

		// Don't override if the application is done through email.
		if ( ! empty( $application->type ) && 'email' === $application->type ) {
			return;
		}

		if ( ! apply_filters( 'goft_wpjm_override_application_details', false ) ) {
			return;
		}

		if ( ! empty( $wp_filter['job_manager_application_details_url']->callbacks ) ) {
			$this->remove_applications_action( 'WP_Job_Manager_Post_Types' );
		}
		return true;
	}


	/**
	 * Override WPJM Resume Manager add-on and remove resume applications.
	 */
	public function maybe_override_wpjm_resume_manager() {
		global $resume_manager, $post;

		// Skip on 'regular' jobs.
		if ( ! is_singular( 'job_listing' ) || ! get_post_meta( $post->ID, '_goft_wpjm_is_external', true ) ) {
			return;
		}

		$application = get_the_job_application_method( $post );

		// Don't override if the application is done through email.
		if ( ! class_exists( 'WP_Resume_Manager' ) || ( ! empty( $application->type ) && 'email' === $application->type ) ) {
			return;
		}

		remove_action( 'job_manager_application_details_email', array( $resume_manager->apply, 'apply_with_resume' ), 20 );
		remove_action( 'job_manager_application_details_url', array( $resume_manager->apply, 'apply_with_resume' ), 20 );
	}

	/**
	 * 	Don't display the [...] (more) link if the job description was scraped.
	 *
	 * @since 1.3.
	 */
	public function remove_more_on_scraped_descriptions( $more, $content, $link ) {
		global $post;

		$meta = get_post_meta( $post->ID, '_goft_wpjm_other', true );

		if ( ! empty( $meta['scrape']['description'] ) ) {
			return '';
		}

		return $more;
	}

	/**
	 * Expire job that explicitly have the 'expired' attribute set to 'true'.
	 */
	public function maybe_expire_job( $post_arr, $item ) {

		if ( ! isset( $item['expired'] ) || ! ( (bool) $item['expired'] ) ) {
			return $post_arr;
		}

		$post_array['post_status'] = 'expired';

		return $post_arr;
	}

	/**
	 * Optionally replaces the canonical URL for imported jobs with the job external link.
	 *
	 * @since 1.3.
	 */
	public function maybe_add_rel_canonical_to_header( $canonical_url, $post ) {
		global $goft_wpjm_options;

		if ( ! get_post_meta( $post->ID, '_goft_wpjm_is_external', true ) ) {
			return $canonical_url;
		}

		if ( $goft_wpjm_options->canonical_links ) {
			$canonical_url = get_post_meta( $post->ID, '_goft_external_link', true );
		}
		return $canonical_url;
	}

	/**
	 * Helper function to remove job applications action hooks.
	 */
	protected function remove_applications_action( $class_name, $action = 'application_details_url', $priority = 10 ) {
		global $wp_filter;

		$callbacks = $wp_filter['job_manager_application_details_url']->callbacks;

		foreach ( $callbacks as $callback ) {
			$object   = wp_list_pluck( array_values( $callback ), 'function' );
			$instance = array_pop( $object );
			if ( is_a( $instance[0], $class_name ) ) {
				remove_action( 'job_manager_application_details_url', array( $instance[0], $action ), $priority );
			}
		}

	}

}

$gofetch_wpjm_frontend = new GoFetch_WPJM_Frontend();
