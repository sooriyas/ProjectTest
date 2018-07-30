<?php
/**
 * Contains AJAX admin related callbacks.
 *
 * @package GoFetchJobs/Admin/Ajax
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Ajax class.
 */
class GoFetch_WPJM_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_goft_wpjm_load_template_content', array( $this, 'load_template_content' ) );
		add_action( 'wp_ajax_goft_wpjm_update_templates_list', array( $this, 'update_templates_list' ) );
		add_action( 'wp_ajax_goft_wpjm_load_provider_info', array( $this, 'load_provider_info' ) );
		add_action( 'wp_ajax_goft_wpjm_import_feed', array( $this, 'import_feed' ) );
		add_action( 'wp_ajax_goft_wpjm_toggle_settings', array( $this, 'toggle_settings' ) );
	}

	/**
	 * Dynamically outputs the pre-saved fields settings for a given template.
	 */
	public function load_template_content() {
		global $goft_wpjm_options;

		if ( ! wp_verify_nonce( $_POST['_ajax_nonce'], 'goft_wpjm_nonce' ) ) {
			die(0);
		}

		// User selected 'none' on the template list dropdown.
		if ( empty( $_POST['template'] ) ) {

			die(0);

		} else {

			// User has selected a template.

			$template_name = sanitize_text_field( $_POST['template'] );
			$template_name = GoFetch_WPJM_Helper::remove_slashes( $template_name );

			$templates = GoFetch_WPJM_Helper::get_sanitized_templates();

			$template_settings = $templates[ $template_name ];

			$query_args = apply_filters( 'goft_wpjm_template_setup', $template_settings, $template_name );
		}

		echo wp_json_encode( $query_args );
		die(1);
	}

	/**
	 * Dynamically update the templates list.
	 */
	public function update_templates_list() {
		global $goft_wpjm_options;

		if (  ! wp_verify_nonce( $_POST['_ajax_nonce'], 'goft_wpjm_nonce' ) ) {
			die(0);
		}

		$templates = GoFetch_WPJM_Helper::get_sanitized_templates();

		echo wp_json_encode( array(
			'templates' => array_keys( $templates ),
		) );

		die(1);
	}

	/**
	 * Load a given providers info.
	 */
	public function load_provider_info() {

		if ( ! wp_verify_nonce( $_POST['_ajax_nonce'], 'goft_wpjm_nonce' ) ) {
			die(0);
		}

		// User didn't select a provider
		if ( empty( $_POST['provider'] ) ) {

			die(0);

		} else {

			// User has selected a provider.

			$provider = sanitize_text_field( $_POST['provider'] );

			$data = array(
				'provider' => GoFetch_WPJM_RSS_Providers::get_providers( $provider ),
				'setup'    => GoFetch_WPJM_RSS_Providers::setup_instructions_for( $provider ),
			);

		}

		echo wp_json_encode( $data );
		die(1);
	}

	/**
	 * Dynamically import an RSS feed.
	 */
	public function import_feed() {

		if ( ! wp_verify_nonce( $_POST['_ajax_nonce'], 'goft_wpjm_nonce' ) ) {
			die(0);
		}

		$url = wp_strip_all_tags( $_POST['url'] );

		$options = array();

		$result = GoFetch_WPJM_Importer::import_feed( $url, $_POST, $cache = true );

		if ( ! $result || is_wp_error( $result ) ) {
			echo wp_json_encode( array(
				'error' => is_wp_error( $result ) ? $result->get_error_message() : __( 'This feed does not appear to be valid.', 'gofetch-wpjm' ),
			) );
			die(0);
		}

		if ( ! is_array( $result ) ) {
			echo wp_json_encode( array(
				'error' => __( 'Unknown error.', 'gofetch-wpjm' ),
			) );
			die(0);
		}

		$provider    = $result['provider'];
		$sample_item = apply_filters( 'goft_wpjm_sample_item', $result['sample_item'], $provider );

		$args = array(
			'content_type' => GoFetch_WPJM()->parent_post_type,
		);

		$total_items = count( $result['items'] );

		// Clear memory.
		$result = null;

		if ( ! empty( $sample_item['logo_html'] ) ) {
			$sample_item['logo'] = $sample_item['logo_html'];
			// Clear memory.
			$sample_item['logo_html'] = null; unset( $sample_item['logo_html'] );
		}

		echo wp_json_encode( array(
			'provider'    => $provider,
			'sample_item' => $total_items ? GoFetch_WPJM_Sample_Table::display( $args, $sample_item ) : '',
			'total_items' => $total_items,
		) );
		die(1);
	}

	/**
	 * Dynamically update toggle settings.
	 */
	public function toggle_settings() {

		if ( ! wp_verify_nonce( $_POST['_ajax_nonce'], 'goft_wpjm_nonce' ) ) {
			die(0);
		}

		$toggle = wp_strip_all_tags( $_POST['toggle'] );

		$user_id = get_current_user_id();

		$value = get_user_meta( $user_id, 'bc_screen_options', true );
		$value['goft-settings-type'] = $toggle;

		update_user_meta( $user_id, 'bc_screen_options', $value, true );

		die(1);
	}

}
new GoFetch_WPJM_Ajax;
