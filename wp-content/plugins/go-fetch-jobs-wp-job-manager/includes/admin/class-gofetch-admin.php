<?php
/**
 * Provides basic admin functionality.
 *
 * @package GoFetchJobs/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Base admin class.
 */
class GoFetch_WPJM_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {

		if ( get_option( 'goft-wpjm-error' ) ) {
			add_action( 'admin_notices', array( $this, 'warnings' ) );
		}

		$this->hooks();
		$this->includes();

		if ( class_exists( 'GoFetch_WPJM_Guided_Tutorial' ) ) {
			new GoFetch_WPJM_Guided_Tutorial;
		}

		add_action( 'restrict_manage_posts', array( $this, 'jobs_filter_restrict_manage_posts' ) );
		add_action( 'restrict_manage_posts', array( $this, 'jobs_filter_restrict_providers' ) );
		add_action( 'manage_' . GoFetch_WPJM()->parent_post_type . '_posts_custom_column', array( $this, 'custom_columns' ), 2 );
		add_filter( 'manage_edit-' . GoFetch_WPJM()->parent_post_type . '_columns', array( $this, 'columns' ) );
		add_filter( 'parse_query', array( $this, 'jobs_filter' ) );
		add_filter( 'parse_query', array( $this, 'providers_filter' ) );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		require_once 'class-gofetch-admin-builder.php';
		require_once 'class-gofetch-admin-sample-table.php';
		require_once 'class-gofetch-admin-settings.php';
		require_once 'class-gofetch-admin-help.php';
		require_once 'class-gofetch-admin-ajax.php';
		require_once 'class-gofetch-guided-tutorial.php';
	}

	public function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 21 );
		add_action( 'admin_head', array( $this, 'admin_icon' ) );
	}

	/**
	 * Register admin JS scripts and CSS styles.
	 */
	public function register_admin_scripts( $hook ) {
		global $goft_wpjm_options;

		$ext = ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ? '.min' : '' ) . '.js';

		wp_register_style(
			'goft-fontello',
			GoFetch_WPJM()->plugin_url() . '/includes/admin/assets/font-icons/css/goft-fontello.css'
		);

		// Selective load.
		if ( ! $this->load_scripts( $hook ) ) {
			return;
		}

		wp_register_script(
			'goft_wpjm-settings',
			GoFetch_WPJM()->plugin_url() . '/includes/admin/assets/js/scripts' . $ext,
			array( 'jquery' ),
			GoFetch_WPJM()->version,
			true
		);

		wp_register_script(
			'select2-goft',
			GoFetch_WPJM()->plugin_url() . '/includes/admin/assets/select2/4.0.3/js/select2.min.js',
			array( 'jquery' ),
			GoFetch_WPJM()->version,
			true
		);

		wp_register_script(
			'select2-resize',
			GoFetch_WPJM()->plugin_url() . '/includes/admin/assets/select2/maximize-select2-height.min.js',
			array( 'select2-goft' ),
			GoFetch_WPJM()->version,
			true
		);

		wp_register_script(
			'validate',
			GoFetch_WPJM()->plugin_url() . '/includes/admin/assets/js/jquery.validate.min.js',
			array( 'jquery' ),
			GoFetch_WPJM()->version
		);

		if ( ! empty( $goft_wpjm_options->geocode_api_key ) ) {

			$params = array(
				'sensor'    => false,
				'libraries' => 'places',
				'key'       => $goft_wpjm_options->geocode_api_key,
				'v'         => 3,
				'language'  => get_bloginfo( 'language' ),
			);
			$google_api = add_query_arg( apply_filters( 'goft_wpjm_gmaps_params', $params ), 'https://maps.googleapis.com/maps/api/js' );

			wp_register_script(
				'gmaps',
				$google_api,
				array( 'jquery' ),
				GoFetch_WPJM()->version
			);

			wp_register_script(
				'geocomplete',
				GoFetch_WPJM()->plugin_url() . '/includes/admin/assets/js/jquery.geocomplete.min.js',
				array( 'jquery', 'gmaps' ),
				GoFetch_WPJM()->version
			);

		}

		wp_register_style(
			'goft_wpjm',
			GoFetch_WPJM()->plugin_url() . '/includes/admin/assets/css/styles.css'
		);

		wp_register_style(
			'select2-goft',
			GoFetch_WPJM()->plugin_url() . '/includes/admin/assets/select2/4.0.3/css/select2.min.css'
		);

	}

	/**
	 * Enqueue registered admin JS scripts and CSS styles.
	 */
	public function enqueue_admin_scripts( $hook ) {
		global $goft_wpjm_options;

		wp_enqueue_style( 'goft-fontello' );

		// Selective load.
		if ( ! $this->load_scripts( $hook ) ) {
			return;
		}

		wp_enqueue_script( 'goft_wpjm-settings' );
		wp_enqueue_script( 'select2-goft' );
		wp_enqueue_script( 'select2-resize' );
		wp_enqueue_script( 'validate' );

		wp_enqueue_script( 'gmaps' );
		wp_enqueue_script( 'geocomplete' );

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_style( 'goft_wpjm' );
		wp_enqueue_style( 'select2-goft' );
		wp_enqueue_style( 'goft_wpjm-jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

		wp_localize_script( 'goft_wpjm-settings', 'goft_wpjm_admin_l18n', array(
			'ajaxurl'               => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'            => wp_create_nonce( 'goft_wpjm_nonce' ),
			'ajax_save_nonce'       => wp_create_nonce( 'go-fetch-jobs-wp-job-manager' ),
			'date_format'           => get_option( 'date_format' ),
			'geocode_api_key'       => (bool) $goft_wpjm_options->geocode_api_key,

			// Messages.
			'msg_jobs_found'        => __( 'Job(s) Available', 'gofetch-wpjm' ),
			'msg_jobs_limit_warn'   => gfjwjm_fs()->is_plan__premium_only( 'proplus', true ) ? __( 'You are choosing to import a very high number of jobs, which can be very resource intensive. This it not recommended, specially if used with multiple schedules. If you get memory related issues please try reducing this number.', 'gofetch-wpjm' ): '',
			'msg_simple'            => __( 'Simple...', 'gofetch-wpjm' ),
			'msg_advanced'          => __( 'Edit...', 'gofetch-wpjm' ),
			'msg_specify_valid_url' => __( 'Please specify a valid URL to import the feed.', 'gofetch-wpjm' ),
			'msg_invalid_feed'      => __( 'Could not load feed.', 'gofetch-wpjm' ),
			'msg_no_jobs_found'     => __( 'No Jobs Found in Feed.', 'gofetch-wpjm' ),
			'msg_template_missing'  => __( 'Please specify a template name.', 'gofetch-wpjm' ),
			'msg_template_saved'    => __( 'Template Settings Saved.', 'gofetch-wpjm' ),
			'msg_save_error'        => __( 'Save failed. Please try again later.', 'gofetch-wpjm' ),
			'msg_rss_copied'        => __( 'Feed URL copied', 'gofetch-wpjm' ),
			'msg_import_jobs'       => __( 'Jobs are being fetched and imported, please wait. This can take some time depending on your options and number of jobs in the feed...' , 'goftech-wpjm' ),

			'title_close'           => esc_attr( __( 'Close/Hide', 'gofetch-wpjm' ) ),

			'label_yes'             => __( 'Yes', 'gofetch-wpjm' ),
			'label_no'              => __( 'No', 'gofetch-wpjm' ),
			'label_providers'       => __( 'Choose a Job Provider . . .', 'gofetch-wpjm' ),
			'label_templates'       => __( 'Choose a Template . . .', 'gofetch-wpjm' ),
			'label_scrape_fields'   => __( 'Choose fields to scrape . . .', 'gofetch-wpjm' ),

			'cancel_feed_load'      => __( 'Cancel', 'gofetch-wpjm' ),

			'default_query_args'    => GoFetch_WPJM_RSS_Providers::valid_item_tags(),

			'jobs_limit_warn'       => apply_filters( 'goft_wpjm_jobs_limit_warn', 99 ),
		) );

	}

	/**
	 * Criteria used for the selective load of scripts/styles.
	 */
	private function load_scripts( $hook = '' ) {

	 	if ( empty( $_GET['post_type'] ) && empty( $_GET['post'] ) && 'toplevel_page_' . GoFetch_WPJM()->slug !== $hook ) {
			return false;
	    }

		$post_type = '';

		if ( ! empty( $_GET['post'] ) ) {
			$post = get_post( (int) $_GET['post'] );
			$post_type = $post->post_type;
		} elseif ( isset( $_GET['post_type'] ) ) {
			$post_type = $_GET['post_type'];
		}

		if ( GoFetch_WPJM()->post_type !== $post_type && 'toplevel_page_' . GoFetch_WPJM()->slug !== $hook ) {
			return false;
		}
		return true;
	}

	/**
	 * Use external font icons in dashboard.
	 */
	public function admin_icon() {
		echo "<style type='text/css' media='screen'>
	   		#toplevel_page_" . GoFetch_WPJM()->slug . " div.wp-menu-image:before {
	   		font-family: goft-fontello !important;
	   		content: '\\e802';
	     	}
	     	</style>";
	}

	/**
	 * Display a custom filter dropdown to filter imported/user submitted jobs.
	 *
	 * @since 1.3.
	 */
	public function jobs_filter_restrict_manage_posts( $type ) {
		global $goft_wpjm_options;

		if ( ! $goft_wpjm_options->admin_jobs_filter ) {
			return;
		}

	    $post_type = GoFetch_WPJM()->parent_post_type;

	    if ( $post_type !== $type ) {
			return;
		}

		$values = array(
			__( 'Imported', 'gofetch-wpjm' )       => 1,
			__( 'User Submitted', 'gofetch-wpjm' ) => 2,
		);
	?>
		<select name="goft_imported_jobs">
			<option value=""><?php _e( 'All Jobs', 'gofetch-wpjm' ); ?></option>
			<?php
				$current_v = isset( $_GET['goft_imported_jobs'] ) ? (int) $_GET['goft_imported_jobs'] : '';
				foreach ( $values as $label => $value ) {
					printf( '<option value="%s"%s>%s</option>', $value, selected( $value, $current_v ), $label );
				}
			?>
		</select>
	<?php
	}

	/**
	 * Display a custom filter dropdown to filter providers.
	 *
	 * @since 1.3.1
	 */
	public function jobs_filter_restrict_providers( $type ) {
		global $goft_wpjm_options, $wpdb;

		if ( ! $goft_wpjm_options->admin_provider_filter ) {
			return;
		}

	    $post_type = GoFetch_WPJM()->parent_post_type;

	    if ( $post_type !== $type ) {
			return;
		}

		$values = array();

		$providers = $this->get_current_providers();

		if ( $providers ) {
			$values = array_combine( $providers, range( 1, count( $providers ) ) );
			ksort( $values );
		}
	?>
		<select name="goft_provider">
			<option value=""><?php _e( 'All Providers', 'gofetch-wpjm' ); ?></option>
			<?php
				$current_v = isset( $_GET['goft_provider'] ) && ( empty( $_GET['goft_imported_jobs'] ) || 2 !== (int) $_GET['goft_imported_jobs'] ) ? (int) $_GET['goft_provider'] : '';

				foreach ( $values as $label => $value ) {
					printf( '<option value="%s"%s>%s</option>', $value, selected( $value, $current_v, false ), $label );
				}
			?>
		</select>
		<input type="hidden" name="providers_list" value="<?php echo implode( ',', $providers ); ?>">
	<?php
	}

	/**
	 * Display additional columns on job listings.
	 *
	 * @since 1.3.
	 */
	public function columns( $columns ) {
		global $goft_wpjm_options;

		if ( ! $goft_wpjm_options->admin_jobs_provider_col ) {
			return $columns;
		}

		if ( ! is_array( $columns ) ) {
			$columns = array();
		}

		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			if ( 'featured_job' === $key ) {
				$new_columns['job_provider'] = __( 'Provider', 'gofetch-wpjm' );
			}
			$new_columns[ $key ] = $label;
		}
		return $new_columns;
	}

	/**
	 * Display custom columns on job listings.
	 *
	 * @since 1.3.
	 */
	public function custom_columns( $column ) {
		global $post, $goft_wpjm_options;

		if ( ! $goft_wpjm_options->admin_jobs_provider_col ) {
			return;
		}

		$source = get_post_meta( $post->ID, '_goft_source_data', true );

		switch ( $column ) {

			case 'job_provider' :
				if ( ! empty( $source['website'] ) ) {
					$url = GoFetch_WPJM_RSS_Providers::simple_url( $source['website'] );
					echo '<span class="goft-job-provider">' . $url . '</span>';
				} else {
					echo '-';
				}
				break;

		}

	}

	/**
	 * Apply the custom filter.
	 *
	 * @since 1.3.
	 */
	public function jobs_filter( $query ) {
	    global $pagenow;

	    $post_type = GoFetch_WPJM()->parent_post_type;

	    if ( ! isset( $_GET['post_type'] ) || $post_type !== $_GET['post_type'] ) {
	        return;
	    }

	    if ( is_admin() && $pagenow === 'edit.php' && isset( $_GET['goft_imported_jobs'] ) && $_GET['goft_imported_jobs'] != '' ) {

			if ( 2 == $_GET['goft_imported_jobs'] ) {
				$query->query_vars['meta_compare'] = 'NOT EXISTS';
			}

			$query->query_vars['meta_key']   = '_goft_wpjm_is_external';
			$query->query_vars['meta_value'] = (int) $_GET['goft_imported_jobs'];
	    }

	}

	/**
	 * Apply the custom filter.
	 *
	 * @since 1.3.1
	 */
	public function providers_filter( $query ) {
	    global $pagenow;

	    $post_type = GoFetch_WPJM()->parent_post_type;

	    if ( ! isset( $_GET['post_type'] ) || $post_type !== $_GET['post_type'] ) {
	        return;
	    }

	    if ( is_admin() && 'edit.php' === $pagenow && ! empty( $_GET['goft_provider'] ) && ( empty( $_GET['goft_imported_jobs'] ) || 2 !== (int) $_GET['goft_imported_jobs'] ) ) {

			$providers = explode( ',', stripslashes( $_GET['providers_list'] ) );
			$values    = array_combine( range( 1, count( $providers ) ), $providers );

			if ( empty( $_GET['goft_provider'] ) ) {
				return;
			}

			$query->query_vars['meta_compare'] = 'LIKE';
			$query->query_vars['meta_key']     = '_goft_source_data';
			$query->query_vars['meta_value']   =  $values[ (int) $_GET['goft_provider'] ];
	    }

	}

	/**
	 * Admin notices.
	 */
	public function warnings() {
		echo scb_admin_notice( sprintf( __( 'The <strong>%1$s</strong> was not found. Please install it first to be able to use <strong>%2$s</strong>.', 'gofetch-wpjm' ),  'WP Job Manager plugin', 'Go Fetch Jobs' ), 'error' );
	}

	public static function limited_plan_warn() {

		$text = '';

		if ( gfjwjm_fs()->is_not_paying() ) {
			$tooltip = __( 'Not available on the Free plan.', 'gofetch-wpjm' );
			$text = html( 'span class="dashicons dashicons-warning tip-icon bc-tip limitation" data-tooltip="' . $tooltip . '"', '&nbsp;' );
		}
		return $text;
	}

	/**
	 * Retrieve providers for the jobs being listed.
	 *
	 * @since 1.3.1
	 */
	protected function get_current_providers() {
		global $wpdb;

		$providers = array();

		$screen = get_current_screen();
		$option = $screen->get_option( 'per_page', 'option' );

		$post_status = ! empty( $_GET['post_status'] ) && 'all' !== $_GET['post_status'] ? wp_strip_all_tags( $_GET['post_status'] ): '';

		if ( $post_status ) {
			$sql = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta a, $wpdb->posts b WHERE a.post_id = b.ID AND post_type = '%s' AND meta_key = '_goft_source_data' AND post_status = %s", GoFetch_WPJM()->parent_post_type, $post_status );
		} else {
			$sql = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta a, $wpdb->posts b WHERE a.post_id = b.ID AND post_type = '%s' AND meta_key = '_goft_source_data' AND post_status <> 'trash' ", GoFetch_WPJM()->parent_post_type );
		}

		$results = $wpdb->get_results( $sql );

		foreach ( $results as $result ) {
			$meta = get_post_meta( (int) $result->post_id, '_goft_source_data', true );
			if ( ! empty( $meta['website'] ) ) {
				$providers[ $meta['website'] ] = GoFetch_WPJM_RSS_Providers::simple_url( $meta['website'] );
			}
		}
		return $providers;
	}

}

$GLOBALS['goft_wpjm']['admin'] = new GoFetch_WPJM_Admin;
