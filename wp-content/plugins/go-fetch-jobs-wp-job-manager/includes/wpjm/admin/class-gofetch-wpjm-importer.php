<?php
/**
 * Specific import code for WP Job Manager.
 *
 * @package GoFetch/WPJM/Admin/Import
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( GOFT_WPJM_PLUGIN_FILE ) . '/includes/class-gofetch-importer.php';

/**
 * WPJM specific import functionality.
 */
class GoFetch_WPJM_Specific_Import extends GoFetch_WPJM_Importer {

	/**
 	 * @var The single instance of the class.
	 */
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		add_filter( 'goft_wpjm_item_meta_value', array( $this, 'replace_item_meta_placeholders' ), 10, 5 );
		add_filter( 'goft_wpjm_update_meta', array( $this, 'maybe_skip_geo_field' ), 10, 4 );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'default_item_meta' ), 10, 3 );
		add_action( 'goft_wpjm_after_insert_job', array( $this, 'maybe_remove_expiry_date' ) );
	}

	/**
	 * Retrieves the custom meta fields/known fields key/value pair mappings.
	 *
	 * Note: adding more meta fields will require having a related RSS feed tag from where the data is extracted.
	 * @see GoFetch_WPJM_RSS_Providers::valid_item_tags()
	 */
	public static function meta_mappings() {

		$mappings = array(
			'_job_location'    => 'location',
			'_company_name'    => 'company',
			'_company_logo'    => 'logo',
			'geolocation_lat'  => 'latitude',
			'geolocation_long' => 'longitude',
		);
		return apply_filters( 'goft_wpjm_meta_mappings', $mappings );
	}

	/**
	 * Setup default values for meta fields.
	 */
	public function default_item_meta( $params, $items ) {

		$defaults = array(
			'_application' => '',
		);
		$params['meta'] = wp_parse_args( $params['meta'], $defaults );

		return $params;
	}

	/**
	 * Replaces string placeholders with valid data on a given meta key.
	 */
	public function replace_item_meta_placeholders( $meta_value, $meta_key, $item, $post_id, $params ) {

		switch ( $meta_key ) {

			case '_application':
				// Use the external job URL for application only if the application field is empty.
				if ( ! $meta_value ) {
					$meta_value = self::add_query_args( $params, $item['link'] );
				}
				break;

			case '_job_expires':
				$curr_date = date( 'Y-m-d', current_time( 'timestamp' ) );

				// Get the value provided by the user (if greater then current date) or default to WPJM duration.
				if ( $meta_value && strtotime( $meta_value ) > strtotime( $curr_date ) ) {
					return $meta_value;
				}
				$meta_value = self::get_expire_date();
				break;

		}
		return $meta_value;
	}

	/**
	 * Remove the expiry date meta on non-published jobs and let WPJM calculate it when the user publishes the job.
	 *
	 * @since 1.3.1
	 */
	public function maybe_remove_expiry_date( $post_id ) {
		if ( 'publish' !== get_post_status( $post_id ) ) {
			delete_post_meta( $post_id, '_job_expires' );
		}
	}

	/**
	 * Skip adding any geolocation fields if WPJM already geolocated the job.
	 */
	public function maybe_skip_geo_field( $update, $meta_key, $meta_value, $post_id ) {

		if ( ! apply_filters( 'job_manager_geolocation_enabled', true ) ) {
			return $update;
		}

		$geo_fields = GoFetch_WPJM_Admin_Builder::get_geocomplete_hidden_fields();

		return isset( $geo_fields[ $meta_key ] ) && class_exists( 'WP_Job_Manager_Geocode' ) && WP_Job_Manager_Geocode::has_location_data( $post_id );
	}

	/**
	 * Calculates the jobs expire date considering the WPJM job duration option.
	 * Leave empty if not set
	 *
	 * @since 1.3.1
	 */
	public static function get_expire_date( $date = '' ) {
		global $goft_wpjm_options;

		if ( $duration = $goft_wpjm_options->jobs_duration ) {
			$date = $date ? $date: current_time( 'mysql' );
			return date( 'Y-m-d', strtotime( $date . ' +' . absint( $duration ) . ' days' ) );
		}
		return;
	}

}

GoFetch_WPJM_Specific_Import::instance();
