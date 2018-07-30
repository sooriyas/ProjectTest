<?php
/**
 * Specific admin settings for WP Job Manager.
 *
 * @package GoFetch/WPJM/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class GoFetch_WPJM_Specific_Settings extends GoFetch_WPJM_Admin_Builder {

	public function __construct() {
		add_filter( 'goft_wpjm_meta_fields', array( $this, 'meta_fields' ) );
		add_filter( 'goft_wpjm_geocomplete_hidden_fields', array( $this, 'geocomplete_hidden_fields' ) );
		add_filter( 'goft_wpjm_form_extra_content', array( $this, 'other_hidden_fields' ) );
		add_filter( 'goft_wpjm_default_value_for_field', array( $this, 'default_value_for_field' ), 10, 2 );
		add_filter( 'goft_wpjm_default_value_for_taxonomy', array( $this, 'default_value_for_tax' ), 10, 3 );
		add_filter( 'goft_wpjm_template_setup', array( $this, 'template_setup' ), 10, 3 );
	}

	/**
	 * WP Job Manager Meta Fields.
	 */
	public function meta_fields( $fields ) {
		global $goft_wpjm_options;

		$fields = array(
			array(
				'title'  => __( 'Expiry Date', 'gofetch-wpjm' ),
				'name'   => '_blank',
				'type'   => 'custom',
				'tip'    => __( 'Choose the expiry date for the jobs being imported.', 'gofetch-wpjm' ),
				'render' => array( $this, 'output_expiry_field' )
			),
			array(
				'title' => __( 'Location', 'gofetch-wpjm' ),
				'name'  => 'meta[_job_location]',
				'extra' => array(
					'class'          => 'geocomplete regular-text',
					'placeholder'    => __( 'e.g: Lisbon', 'gofetch-wpjm' ),
					'data-default'   => __( 'Anywhere', 'gofetch-wpjm' ),
					'data-core-name' => 'location',
				),
				'value' => $this->get_default_value_for_meta( '_job_location' ),
				'tip'   => __( 'The location for the jobs being imported.', 'gofetch-wpjm' ),
				'desc'  => $goft_wpjm_options->geocode_api_key ? '<br/><img class="goft-powered-by-google" src="' . esc_url( GoFetch_WPJM()->plugin_url() . '/includes/admin/assets/images/powered_by_google_on_white_hdpi.png' ) . '">' : '',
			),
			array(
				'title' => __( 'Company', 'gofetch-wpjm' ),
				'name'  => 'meta[_company_name]',
				'value' => $this->get_default_value_for_meta( '_company_name' ),
				'extra' => array(
					'placeholder'    => __( 'e.g: Google', 'gofetch-wpjm' ),
					'data-core-name' => 'company',
				),
				'tip' => __( 'Company name for the jobs being imported.', 'gofetch-wpjm' ),
			),
			array(
				'title'  => __( 'Logo', 'gofetch-wpjm' ),
				'name'   => 'meta[_company_logo]',
				'type'   => 'custom',
				'value'  => $this->get_default_value_for_meta( '_company_logo' ),
				'extra' => array(
					'data-core-name' => 'logo',
				),
				'render' => array( $this, 'logo_uploader' ),
				'tip' => __( 'Company logo for the jobs being imported.', 'gofetch-wpjm' ),
			),
			array(
				'title' => __( 'Website', 'gofetch-wpjm' ),
				'name'  => 'meta[_company_website]',
				'value' => $this->get_default_value_for_meta( '_company_website' ),
				'extra' => array(
					'placeholder' => __( 'e.g: www.google.com', 'gofetch-wpjm' ),
					'data-core-name' => 'website',
				),
				'tip' => __( 'Company Website for the jobs being imported.', 'gofetch-wpjm' ),
			),
			array(
				'title' => __( 'Tagline', 'gofetch-wpjm' ),
				'name'  => 'meta[_company_tagline]',
				'extra' => array(
					'placeholder' => __( 'e.g: Your source for jobs and career opportunities.', 'gofetch-wpjm' ),
				),
				'value' => $this->get_default_value_for_meta( '_company_tagline' ),
				'tip' => __( 'Tagline for the company whose jobs are being imported.', 'gofetch-wpjm' ),
			),
			array(
				'title' => __( 'Video', 'gofetch-wpjm' ),
				'name'  => 'meta[_company_video]',
				'extra' => array(
					'placeholder' => __( 'e.g: https://youtu.be/y_yaAj2tZIw', 'gofetch-wpjm' ),
				),
				'value' => $this->get_default_value_for_meta( '_company_video' ),
				'tip' => __( 'Video URL for the company whose jobs are being imported.', 'gofetch-wpjm' ),
			),
			array(
				'title' => __( 'Twitter', 'gofetch-wpjm' ),
				'name'  => 'meta[_company_twitter]',
				'extra' => array(
					'placeholder' => __( 'e.g: @google', 'gofetch-wpjm' ),
				),
				'value' => $this->get_default_value_for_meta( '_company_twitter' ),
				'tip' => __( 'Twitter username for the company whose jobs are being imported.', 'gofetch-wpjm' ),
			),
		);
		return $fields;
	}

	/**
	 * Outputs a meta field.
	 */
	public function output_featured_meta_field() {
		return apply_filters( 'goft_wpjm_setting_meta_featured', false );
	}

	/**
	 * Geolocation meta fields.
	 */
	public function geocomplete_hidden_fields( $fields = array() ) {

		return array_merge( $fields, array(
			'geolocation_formatted_address' => 'formatted_address',
			'geolocation_city'              => 'city',
			'geolocation_lat'               => 'lat',
			'geolocation_long'              => 'lng',
			'geolocation_country_long'      => 'country',
			'geolocation_country_short'     => 'country_short',
			'geolocation_state_short'       => 'administrative_area_level_1_short',
			'geolocation_state_long'        => 'administrative_area_level_1',
		) );

	}

	/**
	 * Output additional form hidden fields.
	 */
	public function other_hidden_fields( $content ) {

		$fields = array();

		foreach ( $fields as $field => $atts ) {
			$content .= $this->input( $atts );
		}

		return $content;
	}

	/**
	 * Renders the provider logo uploader field.
	 */
	public function logo_uploader() {

		$field = array(
			'name'  => 'meta[_company_logo]',
			'type'  => 'text',
			'extra' => array(
				'class'       => 'goft-company-logo goft-image regular-text',
				'placeholder' => 'e.g: google.png',
				'section'     => 'meta',
			),
			'tip'   => __( 'Company logo for the jobs being imported', 'gofetch-wpjm' ),
			'value' => ( ! empty( $_POST['meta[_company_logo]'] ) ? $_POST['meta[_company_logo]'] : '' ),
			'desc'  => html( 'input', array( 'type' => 'button', 'name' => 'upload_company_logo', 'class' => 'goft-company-logo goft-upload button-secondary', 'value' => __( 'Browse...', 'gofetch-wpjm' ) ) ),
		);

		return $this->image_uploader( $field, 'goft-company-logo' );
	}

	/**
	 * The default value to use on a given meta field.
	 */
	public function default_value_for_field( $value, $field ) {

		switch ( $field ) {

			case '_job_expires':
				$value = GoFetch_WPJM_Specific_Import::get_expire_date();
				break;

			case '_application':
				$value = __( 'Apply to this job by clicking this <a href="%external_apply_to_url%">link</a>', 'gofetch-wpjm' );
				break;

		}
		return $value;
	}

	/**
	 * Override template setup values if necessary.
	 */
	public function template_setup( $settings ) {

		// Update the expire date if necessary.
		if ( ! empty( $settings['meta']['_job_expires'] ) && strtotime( $settings['meta']['_job_expires'] ) < current_time( 'timestamp' ) ) {
			$settings['meta']['_job_expires'] = GoFetch_WPJM_Specific_Import::get_expire_date();
		}
		return $settings;
	}

	/**
	 * Default to use on a given taxonomy.
	 */
	public function default_value_for_tax( $value, $tax, $slug = '' ) {

		switch ( $tax ) {

			case 'job_listing_category':
			case 'job_listing_type':

				$args = array(
					'number'     => 1,
					'fields'     => 'id=>slug',
					'hide_empty' => false
				);

				if ( $slug ) {
					$args['slug'] = $slug;
				}

				$terms = get_terms( $tax, $args );

				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$value = reset( $terms );
				}
				break;

		}
		return $value;
	}

	/**
	 * Outputs the date interval settings.
	 */
	public function output_expiry_field() {

		$value = $this->get_default_value_for_meta( '_job_expires' );

		$atts = array(
			'type'  => 'text',
			'name'  => 'meta[_job_expires]',
			'extra' => array(
				'section'      => 'meta',
				'class'        => 'span_date meta-job-expires',
				'style'        => 'width: 120px;',
				'placeholder'  => __( 'click to choose...', 'gofetch-wpjm' ),
				'readonly'     => true,
				'data-default' => $value,
				),
			'desc'  => html( 'a', array( 'class' => 'button clear_span_dates', 'data-goft_parent' => 'meta-job-expires' ), __( 'Clear', 'gofetch-wpjm' ) ),
			'value' => $value,
		);
?>
		<script>
			jQuery(document).ready(function($) {

				// Date picker.
				$('.meta-job-expires').datepicker({
					dateFormat: 'yy-mm-dd',
					changeMonth: true,
				});

			    $(document).on( 'goftj_rss_content_loaded', function( e, data ) {

					$('.meta-job-expires').bind( 'change', function() {
						var date = new Date( $(this).val() );

						if ( date.getTime() > $.now() ) {
							$(this).removeClass('value-warning');
						} else {
							$(this).addClass('value-warning')
						}

					});
					$('.meta-job-expires').change();
			    });

			});
		</script>
<?php
		return $this->input( $atts );
	}

}

$GLOBALS['goft_wpjm']['specific_settings'] = new GoFetch_WPJM_Specific_Settings;
