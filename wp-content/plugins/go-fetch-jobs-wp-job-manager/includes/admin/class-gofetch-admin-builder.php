<?php

/**
 * Provides and outputs the template builder.
 *
 * @package GoFetch/Admin/Settings
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}

global  $goft_wpjm_options ;
/**
 * Screen options class.
 */
class GoFetch_WPJM_Help_Admin_Screen_Options
{
    /**
     * @var The current screen ID.
     */
    var  $screen_id ;
    public function __construct()
    {
        $this->screen_id = 'toplevel_page_go-fetch-jobs-wp-job-manager';
        add_filter(
            'screen_settings',
            array( $this, 'screen_settings' ),
            12,
            3
        );
    }
    
    /**
     * Retrieves the custom settings HTML.
     */
    public function screen_settings( $settings, $args )
    {
        
        if ( $args->base === $this->screen_id ) {
            $options = get_user_meta( get_current_user_id(), 'bc_screen_options', true );
            $input = 'goft-settings-type';
            $value = 'advanced';
            if ( !empty($options[$input]) ) {
                $value = $options[$input];
            }
            $settings .= "\n\t\t\t\t<fieldset class='gofetch-screen-options'>\n\t\t\t\t\t<legend>" . __( 'Toggle Basic/Advanced Settings <small>(changes are applied instantly)</small>', 'gofetch-wpjm' ) . "</legend>\n\t\t\t\t\t<div class='metabox-prefs'>\n\t\t\t\t\t\t<label for='bc_screen_options[" . $input . "]'>\n\t\t\t\t\t\t\t<input type='radio' value='basic' " . checked( 'basic' === $value, true, false ) . " name='bc_screen_options[" . esc_attr( $input ) . "]' id='" . esc_attr( $input ) . "' />" . __( 'Basic', 'gofetch-wpjm' ) . "\n\t\t\t\t\t\t\t <input type='radio' value='advanced' " . checked( 'advanced' === $value, true, false ) . " name='bc_screen_options[" . esc_attr( $input ) . "]' id='" . esc_attr( $input ) . "' />" . __( 'Advanced', 'gofetch-wpjm' ) . "\n\t\t\t\t\t\t</label>\n\t\t\t\t\t</div>\n\t\t\t\t</fieldset>";
        }
        
        return $settings;
    }

}
/**
 * Settings Admin class.
 */
class GoFetch_WPJM_Admin_Builder extends BC_Framework_Admin_page
{
    private static  $taxonomy_compat ;
    private static  $taxonomy_compat_index = 0 ;
    private static  $imported = 0 ;
    /**
     * Setup the Import page.
     */
    public function setup()
    {
        $this->args = array(
            'page_title'    => __( 'Import Jobs', 'gofetch-wpjm' ),
            'page_slug'     => GoFetch_WPJM()->slug,
            'parent'        => 'options.php',
            'menu_title'    => __( 'Go Fetch Jobs', 'gofetch-wpjm' ),
            'submenu_title' => __( 'Import Jobs', 'gofetch-wpjm' ),
            'toplevel'      => 'menu',
        );
        add_action( 'wp_ajax_goft_wpjm_save_template', array( $this, 'ajax_save_template' ) );
        add_action( 'admin_init', array( $this, 'init_tooltips' ), 9999 );
    }
    
    /**
     * Load tooltips for the current screen.
     * Avoids loading multiple tooltip instances on metaboxes.
     */
    public function init_tooltips()
    {
        new BC_Framework_ToolTips( array( 'toplevel_page_gofetch' ) );
    }
    
    /**
     * The settings for the Import page.
     */
    public function page_content()
    {
        global  $goft_wpjm_options ;
        if ( get_option( 'goft-wpjm-error' ) ) {
            return;
        }
        $this->init_tooltips();
        echo  html( 'p', __( 'From this page you can import jobs from any valid RSS job feed to your jobs database. Select a feed provider from the <em>Providers</em> dropdown or just paste a known feed URL on the <em>Feed URL</em> field.', 'gofetch-wpjm' ) . ' ' . sprintf( __( 'If you\'re having trouble with a feed make sure it works and it\'s retrieving jobs by copy & pasting the URL on our web browser (you should be able to see a structured XML file with jobs inside) or use the <a href="%s" rel="nofollow">W3C online validator</a>.', 'gofetch-wpjm' ), 'https://validator.w3.org/feed/' ) ) ;
        echo  html( 'p', __( 'It is recommended to import targeted/categorized feeds, meaning, for a specific job category, location, etc, or filtered by some criteria instead of a big generic feed for better control of the jobs being imported.', 'gofetch-wpjm' ) ) ;
        echo  html( 'p style="background: #e4e4e4; padding: 10px;"', html( 'span class="dashicons dashicons-warning" style="float: left; padding-right: 10px;"', '&nbsp;' ) . ' ' . __( 'Please make sure that job providers are always credited (done automatically unless deactivated) and try not to abuse on the number of jobs imported for a single provider. Some providers have strict policies on their terms of service about letting third party sites storing their jobs.', 'gofetch-wpjm' ) ) ;
        if ( gfjwjm_fs()->is_not_paying() ) {
            echo  scb_admin_notice( sprintf( html( 'span class="dashicons dashicons-warning" style=""', '&nbsp;' ) . ' ' . __( 'If you need further features like more job providers, a custom feed builder, featuring imported jobs, smart categories assign, schedule imports, and more, please upgrade to a <a href="%1$s">premium plan</a>.', 'gofetch-wpjm' ), esc_url( gfjwjm_fs()->get_upgrade_url() ) ) ) ;
        }
        $templates = GoFetch_WPJM_Helper::get_sanitized_templates();
        $feed_setup = array(
            array(
            'title'         => '',
            'name'          => '_blank',
            'type'          => 'custom',
            'section_break' => true,
            'render'        => array( $this, 'section_break' ),
        ),
            array(
            'title'    => __( 'Load Saved Template', 'gofetch-wpjm' ),
            'type'     => 'select',
            'name'     => 'templates_list',
            'extra'    => array(
            'id' => 'templates_list',
        ),
            'choices'  => array_keys( $templates ),
            'selected' => '',
            'desc'     => html( 'input', array(
            'type'  => 'submit',
            'name'  => 'refresh_templates',
            'class' => 'refresh-templates button-secondary',
            'value' => __( 'Refresh', 'gofetch-wpjm' ),
        ) ) . ' ' . html( 'input', array(
            'type'     => 'submit',
            'name'     => 'delete_template',
            'class'    => 'button-secondary',
            'disabled' => true,
            'value'    => __( 'Delete', 'gofetch-wpjm' ),
        ) ),
            'text'     => __( 'Choose a Template . . .', 'gofetch-wpjm' ),
            'tip'      => __( 'The list of all your saved RSS feeds import templates. Choosing an existing template automatically loads the related RSS feed and configuration.', 'gofetch-wpjm' ),
            'tr'       => 'tr-templates',
        ),
            array(
            'title'  => __( 'Job Providers', 'gofetch-wpjm' ),
            'type'   => 'custom',
            'name'   => '_blank',
            'render' => array( $this, 'provider_helper_dropdown' ),
            'tip'    => __( 'A list with some of the most popular job sites that offer jobs via RSS feeds or through an API. Click a provider to view more details and instructions on how to use the RSS feed.', 'gofetch-wpjm' ) . (( gfjwjm_fs()->is_not_paying() ? html( 'p', html( 'code', html( 'span class="dashicons dashicons-warning"', '&nbsp;' ) . ' ' . __( 'Premium plans include a bigger list and an RSS feed builder for select providers.' ) ) ) : '<br/><br/>' . sprintf( __( 'Missing any of you favorites RSS job feed providers? Submit your providers suggestions using the official site <a href="%s" target="_blank" rel="nofollow">contact form</a>. The most popular requests will be added on future releases.', 'gofetch-wpjm' ), esc_url( 'gofetchjobs.com/contact' ) ) )),
            'render' => array( $this, 'provider_helper_dropdown' ),
            'tr'     => 'tr-providers',
        ),
            array(
            'title'  => '',
            'name'   => '_blank',
            'type'   => 'custom',
            'render' => array( $this, 'section_providers_placeholder' ),
            'tr'     => 'tr-hide',
        ),
            array(
            'title' => __( 'Feed URL', 'gofetch-wpjm' ),
            'type'  => 'text',
            'name'  => 'rss_feed_import',
            'extra' => array(
            'class'        => 'regular-text2',
            'placeholder'  => 'e.g: http://jobs.wordpress.net/feed/?s=developer',
            'data-example' => 'http://jobs.wordpress.net/feed/?s=developer',
        ),
            'tip'   => __( 'A feed URL with job listings that you wish to import to your site. Make sure you use a targeted feed instead of a generic feed.', 'gofetch-wpjm' ) . __( '<br/><br/><code>GOOD - http://jobs.wordpress.net/feed/?s=developer (filtered results)</code> <br/><code>BAD - http://jobs.wordpress.net/feed (bulk results)</code>', 'gofetch-wpjm' ) . __( '<br/><br/>If you prefer to use a generic feed, after loading the feed you can specify some keywords to match against the jobs being imported.', 'gofetch-wpjm' ) . (( gfjwjm_fs()->is_not_paying() ? html( 'p', html( 'code', html( 'span class="dashicons dashicons-warning"', '&nbsp;' ) . ' ' . __( 'Keyword filtering is only available in Premium plans.' ) ) ) : '' )),
            'desc'  => html( 'input', array(
            'type'  => 'submit',
            'name'  => 'import_feed',
            'class' => 'import-feed button-primary',
            'value' => __( 'Load', 'gofetch-wpjm' ),
        ) ) . $this->feed_specific_options(),
            'value' => ( !empty($_POST['rss_feed_import']) ? $_POST['rss_feed_import'] : '' ),
            'tr'    => 'tr-rss-url',
        ),
            array(
            'title'  => '',
            'name'   => '_blank',
            'type'   => 'custom',
            'render' => array( $this, 'placeholder' ),
        ),
            array(
            'title'  => __( 'Content Sample', 'gofetch-wpjm' ),
            'name'   => '_blank',
            'type'   => 'custom',
            'render' => array( $this, 'table_fields_title' ),
            'tr'     => 'temp-tr-hide tr-sample',
        ),
            array(
            'title'  => '',
            'name'   => '_blank',
            'type'   => 'custom',
            'render' => array( $this, 'output_sample_table' ),
            'tr'     => 'temp-tr-hide',
        )
        );
        $feed_setup = apply_filters( 'goft_wpjm_settings', $feed_setup, 'feed_setup' );
        $provider_details = array(
            array(
            'title'         => '',
            'name'          => '_blank',
            'type'          => 'custom',
            'section_break' => true,
            'render'        => array( $this, 'section_break' ),
            'tr'            => 'temp-tr-hide tr-provider-details tr-advanced',
        ),
            array(
            'title' => __( 'Name', 'gofetch-wpjm' ),
            'type'  => 'text',
            'name'  => 'source[name]',
            'extra' => array(
            'class'       => 'regular-text',
            'placeholder' => 'e.g: Monster Jobs',
            'section'     => 'source',
        ),
            'tip'   => __( 'The feed source name (e.g: Monster Jobs).', 'gofetch-wpjm' ),
            'value' => ( !empty($_POST['source[name]']) ? $_POST['source[name]'] : '' ),
            'desc'  => html( 'span', array(
            'class'       => "wp-ui-text-highlight reset-val",
            'data-parent' => 'source[name]',
            'title'       => esc_attr( __( 'Revert to default value.', 'gofetch-wpjm' ) ),
        ), html( 'span', array(
            'class' => "dashicons dashicons-image-rotate",
        ) ) ),
            'tr'    => 'tr-hide',
        ),
            array(
            'title' => __( 'Website', 'gofetch-wpjm' ),
            'type'  => 'text',
            'name'  => 'source[website]',
            'extra' => array(
            'class'       => 'regular-text',
            'placeholder' => 'e.g: www.monster.com',
            'section'     => 'source',
        ),
            'tip'   => __( 'The feed source URL (e.g: www.monster.com).', 'gofetch-wpjm' ),
            'value' => ( !empty($_POST['source[url]']) ? $_POST['source[url]'] : '' ),
            'desc'  => html( 'span', array(
            'class'       => "wp-ui-text-highlight reset-val",
            'data-parent' => 'source[website]',
            'title'       => esc_attr( __( 'Revert to default value.', 'gofetch-wpjm' ) ),
        ), html( 'span', array(
            'class' => "dashicons dashicons-image-rotate",
        ) ) ),
            'tr'    => 'tr-hide',
        ),
            array(
            'title'  => __( 'Logo', 'gofetch-wpjm' ),
            'name'   => 'source[logo]',
            'type'   => 'custom',
            'tip'    => __( "Specify an image URL here to display the jobs source site logo instead of only the site name. It is recommend that you use a local image so you can resize it accordingly.", 'gofetch-wpjm' ),
            'render' => array( $this, 'provider_logo_uploader' ),
            'tr'     => 'tr-hide',
        )
        );
        $provider_details = apply_filters( 'goft_wpjm_settings', $provider_details, 'provider_details' );
        $monetize[] = array(
            'title'         => '',
            'name'          => '_blank',
            'type'          => 'custom',
            'section_break' => true,
            'render'        => array( $this, 'section_break' ),
            'tr'            => 'temp-tr-hide tr-advanced',
        );
        $monetize = apply_filters( 'goft_wpjm_settings', $monetize, 'monetize' );
        $jobs_setup = array(
            array(
            'title'         => '',
            'name'          => '_blank',
            'type'          => 'custom',
            'section_break' => true,
            'render'        => array( $this, 'section_break' ),
            'tr'            => 'temp-tr-hide',
        ),
            array(
            'title'         => '',
            'name'          => '_blank',
            'type'          => 'custom',
            'section_break' => true,
            'render'        => array( $this, 'section_break' ),
            'section'       => 'taxonomies',
            'tr'            => 'temp-tr-hide tr-taxonomies',
        ),
            array(
            'title'         => '',
            'name'          => '_blank',
            'type'          => 'custom',
            'section_break' => true,
            'render'        => array( $this, 'section_break' ),
            'section'       => 'meta',
            'tr'            => 'temp-tr-hide tr-meta tr-advanced',
        ),
            array(
            'title'         => '',
            'name'          => '_blank',
            'type'          => 'custom',
            'section_break' => true,
            'render'        => array( $this, 'section_break' ),
            'tr'            => 'temp-tr-hide',
        ),
            array(
            'title'  => __( 'Name', 'gofetch-wpjm' ),
            'name'   => 'job_author',
            'type'   => 'custom',
            'render' => array( $this, 'output_job_listers' ),
            'tip'    => __( 'Choose the user to be assigned to the imported jobs. This user will only be assigned to the jobs your are currently importing. It is not saved in templates.', 'gofetch-wpjm' ),
            'tr'     => 'temp-tr-hide tr-job-lister',
        )
        );
        $jobs_setup = apply_filters( 'goft_wpjm_settings', $jobs_setup, 'jobs_setup' );
        $filter = array( array(
            'title'         => '',
            'name'          => '_blank',
            'type'          => 'custom',
            'section_break' => true,
            'render'        => array( $this, 'section_break' ),
            'tr'            => 'temp-tr-hide tr-filter tr-advanced',
        ), array(
            'title'   => __( 'Limit', 'gofetch-wpjm' ),
            'name'    => 'limit',
            'type'    => 'text',
            'extra'   => array(
            'class'     => 'small-text',
            'maxlength' => 5,
        ),
            'tip'     => __( 'Choose the number of jobs to import (leave empty to import all the jobs in the feed - not recommended on large RSS feeds).', 'gofetch-wpjm' ),
            'value'   => ( !empty($_POST['limit']) ? wp_strip_all_tags( $_POST['limit'] ) : '' ),
            'tr'      => 'temp-tr-hide tr-limit tr-advanced',
            'default' => 50,
        ) );
        $filter = apply_filters( 'goft_wpjm_settings', $filter, 'filter' );
        $save = array( array(
            'title'         => '',
            'name'          => '_blank',
            'type'          => 'custom',
            'section_break' => true,
            'render'        => array( $this, 'section_break' ),
            'tr'            => 'temp-tr-hide tr-save',
        ), array(
            'title' => __( 'Template', 'gofetch-wpjm' ),
            'type'  => 'text',
            'name'  => 'template_name',
            'extra' => array(
            'style' => 'width: 312px',
            'class' => 'field_dependent',
        ),
            'desc'  => html( 'a', array(
            'class' => 'save-template button button-primary field_dependent',
        ), __( 'Save', 'gofetch-wpjm' ) ),
            'tip'   => __( 'Specify a template name and and save your import setup. Templates can be loaded later and can also be used on scheduled imports.', 'gofetch-wpjm' ) . '<br/><br/>' . __( 'Some template name examples: <em>my fulltime jobs</em>, <em>my tech jobs</em>, <em>my big salary jobs</em>, etc.', 'gofetch-wpjm' ),
            'value' => ( !empty($_POST['template_name']) ? wp_strip_all_tags( $_POST['template_name'] ) : 'my-rss-feed' ),
            'tr'    => 'temp-tr-hide tr-template-name',
        ), array(
            'title'         => '',
            'name'          => '_blank',
            'type'          => 'custom',
            'section_break' => true,
            'render'        => array( $this, 'section_break' ),
            'tr'            => 'temp-tr-hide',
        ) );
        $save = apply_filters( 'goft_wpjm_settings', $save, 'save' );
        $fields = array_merge(
            $feed_setup,
            $provider_details,
            $monetize,
            $jobs_setup,
            $filter,
            $save
        );
        $fields = apply_filters( 'goft_wpjm_settings', $fields );
        $taxonomies = apply_filters( 'goft_wpjm_settings_taxonomies', $this->page_content_taxonomies() );
        $tax_pos = BC_Framework_Utils::list_find_pos( $fields, array(
            'section' => 'taxonomies',
        ) ) + 1;
        $fields = array_merge( array_slice(
            $fields,
            0,
            $tax_pos,
            true
        ), $taxonomies, array_slice(
            $fields,
            $tax_pos,
            count( $fields ) - 1,
            true
        ) );
        $meta_fields = apply_filters( 'goft_wpjm_settings_meta_fields', $this->page_content_meta_fields() );
        $meta_fields_options = apply_filters( 'goft_wpjm_settings_meta_fields_options', $this->page_content_meta_fields_options() );
        $meta_fields = array_merge( $meta_fields, $meta_fields_options );
        $meta_pos = BC_Framework_Utils::list_find_pos( $fields, array(
            'section' => 'meta',
        ) ) + 1;
        $fields = array_merge( array_slice(
            $fields,
            0,
            $meta_pos,
            true
        ), $meta_fields, array_slice(
            $fields,
            $meta_pos,
            count( $fields ) - 1,
            true
        ) );
        echo  $this->form_table( $fields ) ;
    }
    
    protected function feed_specific_options()
    {
        return;
    }
    
    /**
     * Retrieves the main taxonomies related with the the 'job_listing' post type.
     */
    protected function page_content_taxonomies()
    {
        $taxonomies = get_object_taxonomies( GoFetch_WPJM()->parent_post_type, 'objects' );
        $fields = array();
        // Provide compatibility for objects in anonymous functions (PHP < 5.3).
        $o_this = $this;
        foreach ( $taxonomies as $taxonomy ) {
            
            if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
                $render = function () use( $o_this, $taxonomy ) {
                    return $o_this->output_taxonomy( $taxonomy );
                };
            } else {
                // For older PHP versions (< 5.3.0) keep a list of the used taxonomies.
                self::$taxonomy_compat[] = $taxonomy;
                $render = array( $this, 'output_taxonomy' );
            }
            
            $fields[] = array(
                'title'  => $taxonomy->label,
                'name'   => '_blank',
                'type'   => 'custom',
                'render' => $render,
                'tip'    => sprintf( __( 'The %s for the jobs being imported.', 'gofetch-wpjm' ), $taxonomy->label ),
                'tr'     => 'tr-hide',
            );
        }
        return $fields;
    }
    
    /**
     * Retrieves all the meta fields available for the 'job_listing' post type.
     */
    protected function page_content_meta_fields()
    {
        $fields = apply_filters( 'goft_wpjm_meta_fields', array() );
        $defaults = array(
            'title' => '',
            'name'  => '',
            'type'  => 'text',
            'extra' => array(
            'section' => 'meta',
            'default' => '',
        ),
            'tr'    => 'tr-hide',
        );
        $final_fields = array();
        foreach ( $fields as $field ) {
            
            if ( !empty($field['type']) && 'checkbox' === $field['type'] ) {
                $defaults['extra']['class'] = '';
            } else {
                $defaults['extra']['class'] = 'regular-text';
            }
            
            $new_field = wp_parse_args( $field, $defaults );
            if ( !empty($field['extra']) ) {
                $new_field['extra'] = wp_parse_args( $field['extra'], $defaults['extra'] );
            }
            // Automatically set the default value.
            
            if ( empty($new_field['extra']['default']) ) {
                preg_match_all( "/\\[([^\\]]*)\\]/", $field['name'], $field_name );
                if ( !empty($field_name[1][0]) ) {
                    $new_field['extra']['default'] = $this->get_default_value_for_meta( $field_name[1][0] );
                }
            }
            
            $final_fields[] = $new_field;
        }
        return $final_fields;
    }
    
    /**
     * Retrieves all the meta fields available for the 'job_listing' post type.
     */
    protected function page_content_meta_fields_options()
    {
        $fields[] = array(
            'title' => __( 'Search Embedded Logos', 'gofetch-wpjm' ),
            'type'  => 'checkbox',
            'name'  => 'logos',
            'desc'  => __( 'Yes', 'gofetch-wpjm' ),
            'tip'   => __( 'Checking this option instructs the importer to search for embedded job companies logos within the RSS feed (not available in most cases). Import will be slower.', 'gofetch-wpjm' ) . '<br/><br/>' . __( 'Note that some RSS feeds may retrieve the RSS provider logo instead of the job companies logos.', 'gofetch-wpjm' ) . '<br/><br/>' . __( 'Always try a couple of test imports to decide if you want this option checked or not.', 'gofetch-wpjm' ),
            'tr'    => 'temp-tr-hide tr-special-logos-options',
        );
        return $fields;
    }
    
    /**
     *
     */
    public function provider_helper_dropdown()
    {
        $atts = array(
            'name' => 'providers_list',
            'id'   => 'providers_list',
        );
        return GoFetch_WPJM_RSS_Providers::output_providers_dropdown( $atts );
    }
    
    /**
     * Renders the provider logo uploader field.
     */
    public function provider_logo_uploader()
    {
        $field = array(
            'name'  => 'source[logo]',
            'type'  => 'text',
            'extra' => array(
            'class'              => 'goft-source-logo goft-image regular-text',
            'placeholder'        => 'e.g: www.my-jobs-site/uploads/monster-logo.png',
            'section'            => 'source',
            'data-image-id-name' => 'source[image_id]',
        ),
            'tip'   => __( "Specify an image URL here to display the jobs source site logo instead of only the site name. It's recommend that you use a local image so you can resize it accordingly.", 'gofetch-wpjm' ),
            'value' => ( !empty($_POST['source[logo]']) ? $_POST['source[logo]'] : '' ),
            'desc'  => html( 'span', array(
            'class'       => "wp-ui-text-highlight reset-val",
            'data-parent' => 'source[logo]',
            'title'       => esc_attr( __( 'Revert to default value.', 'gofetch-wpjm' ) ),
        ), html( 'span', array(
            'class' => "dashicons dashicons-image-rotate",
        ) ) ) . html( 'a', array(
            'class' => 'goft-source-logo goft-upload button-secondary',
        ), __( 'Browse...', 'gofetch-wpjm' ) ),
        );
        return $this->image_uploader( $field, 'goft-source-logo' );
    }
    
    /**
     * Outputs an image uploader field;
     */
    public function image_uploader( $field, $class )
    {
        
        if ( function_exists( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        } else {
            wp_enqueue_style( 'thickbox' );
            wp_enqueue_script( 'media-upload' );
            wp_enqueue_script( 'thickbox' );
        }
        
        ob_start();
        echo  html( 'img', array(
            'class' => "{$class}",
            'src'   => '',
        ) ) ;
        echo  $this->input( $field ) ;
        ?>
		<script>
			jQuery(document).ready(function($) {

				$( ".goft-upload.<?php 
        echo  $class ;
        ?>" ).on( 'click', function(e) {
					e.preventDefault();

					var custom_uploader = wp.media({
						title: "<?php 
        echo  __( 'Custom Image', 'gofetch-wpjm' ) ;
        ?>",
						button: {
							text: "<?php 
        echo  __( 'Upload Image', 'gofetch-wpjm' ) ;
        ?>"
						},
						multiple: false  // Set this to true to allow multiple files to be selected
					})
					.on('select', function() {
						var attachment = custom_uploader.state().get('selection').first().toJSON();
						$( "input[name='<?php 
        echo  esc_attr( $field['name'] ) ;
        ?>']" ).val( attachment.url ).trigger('change');
						<?php 
        
        if ( !empty($field['extra']['data-image-id-name']) ) {
            ?>
							$( "input[name='<?php 
            echo  esc_attr( $field['extra']['data-image-id-name'] ) ;
            ?>']" ).val( attachment.id );
						<?php 
        }
        
        ?>
					})
					.open();
				});

				$( "input[name='<?php 
        echo  esc_attr( $field['name'] ) ;
        ?>']" ).on( 'change', function(e) {

						$( "img.<?php 
        echo  $class ;
        ?>" ).attr( 'src', $(this).val() );

						if ( $(this).val() ) {
							$( "img.<?php 
        echo  $class ;
        ?>" ).show();
						} else {
							$( "img.<?php 
        echo  $class ;
        ?>" ).hide();
						}

				});

				$( "input[name='<?php 
        echo  esc_attr( $field['name'] ) ;
        ?>']" ).change();
			});
		</script>
<?php 
        return ob_get_clean();
    }
    
    /**
     * Retrieves the key/value mapping for the geolocation meta keys and the geocomplete 'data-geo' helper fields.
     */
    public static function get_geocomplete_hidden_fields()
    {
        return apply_filters( 'goft_wpjm_geocomplete_hidden_fields', array() );
    }
    
    /**
     * Output hidden gecocomplete meta fields.
     */
    public static function get_geocomplete_fields()
    {
        $hidden_fields = '';
        foreach ( self::get_geocomplete_hidden_fields() as $att => $location_att ) {
            $meta_key = "meta[{$att}]";
            $hidden_fields .= html( 'input', array(
                'type'     => 'hidden',
                'name'     => esc_attr( $meta_key ),
                'data-geo' => esc_attr( $location_att ),
                'value'    => '',
            ) );
        }
        return html( 'div', array(
            'class' => 'custom-location',
        ), $hidden_fields );
    }
    
    /**
     * Outputs the placeholder that the user sees before the RSS feed is loaded.
     */
    public function placeholder()
    {
        return html( 'p class="import-notes"', html( 'strong', __( '- Please load an existing feed template or a valid feed to continue with the import process -', 'gofetch-wpjm' ) ) );
    }
    
    /**
     * Outputs the sample fields table title.
     */
    public function table_fields_title()
    {
        return html( 'p', __( 'The table below shows all the available fields for the loaded RSS Feed, and a sample of the content inside each field.', 'gofetch-wpjm' ) );
    }
    
    // _Sections.
    /**
     * The sections titles.
     */
    public function section_title( $section )
    {
        $title_desc = array();
        $i = 1;
        $title_desc[$i++] = array(
            'title'  => '<span class="dashicons dashicons-rss title-icon"></span>' . __( 'RSS Feed Setup', 'gofetch-wpjm' ),
            'header' => 'h2 style="font-style: italic;"',
        );
        $title_desc[$i++] = array(
            'title'   => '<span class="dashicons dashicons-id-alt title-icon"></span>' . __( 'Provider Details', 'gofetch-wpjm' ),
            'desc'    => __( "Provide some information about the current jobs feed provider. This information will help identify each job source and will be displayed on each job page. Leave empty if you don't want to show the jobs source (not recommended!).", 'gofetch-wpjm' ),
            'tip'     => __( "It is highly recommended that you show the original job site attribution. Failing to do so might infringe Copywrite. The original jobs source should always be given the proper attribution.", 'gofetch-wpjm' ),
            'header'  => 'h2 style="font-style: italic;"',
            'section' => 'source',
        );
        $i++;
        $title_desc[$i++] = array(
            'title'  => '<span class="icon icon-goft-briefcase title-icon"></span>' . __( 'Jobs Setup', 'gofetch-wpjm' ),
            'desc'   => __( 'Fill in additional details like job categories, job types, job duration, etc, for the jobs you are importing. These details will be added to each imported job.', 'gofetch-wpjm' ),
            'header' => 'h2 style="font-style: italic;"',
        );
        $title_desc[$i++] = array(
            'title'   => '<span class="dashicons dashicons-tag title-icon"></span>' . __( 'Terms', 'gofetch-wpjm' ),
            'desc'    => __( 'The terms that best fit the content you are importing or configuring. Click "Edit..." to specify terms yourself, otherwise jobs will use default terms.', 'gofetch-wpjm' ),
            'tip'     => __( "To make sure jobs are imported with relevant terms terms it's recommended that you import a relevant feed for each of your job types and job categories (e.g: import a feed containing 'Full-Time', 'Marketing' jobs). " . 'Otherwise, any taxonomy terms you choose below will be blindly assigned to all imported jobs.', 'gofetch-wpjm' ) . '<br/><br/>' . __( "Alternatively, you can enable <em>Smart Assign</em> to let the import process scan and automatically assign terms using term marching on each job being imported. " . 'If the automatic process fails it will assign the terms you choose below.', 'gofetch-wpjm' ) . (( gfjwjm_fs()->is_not_paying() ? html( 'p', html( 'code', html( 'span class="dashicons dashicons-warning"', '&nbsp;' ) . ' ' . __( '<em>Smart Assign</em> is only available in Premium plans.' ) ) ) : '' )),
            'section' => 'taxonomies',
        );
        $title_desc[$i++] = array(
            'title'   => '<span class="dashicons dashicons-index-card title-icon"></span>' . __( 'Details', 'gofetch-wpjm' ),
            'desc'    => __( 'The default values for the custom fields (details) of each job being imported. Click "Edit..." to assign values to custom fields, otherwise they will be left as is.', 'gofetch-wpjm' ),
            'tip'     => __( 'The custom fields values will only be assigned if not already provided by the feed. They will not override the original values. As an example, if a job being imported already contains the location or the job company ***, that information will be used instead of any values you add here.', 'gofetch-wpjm' ) . ' ' . __( 'The plugin will also try to assign default values based on the feed parameters (if any).', 'gofetch-wpjm' ) . '<br/><br/>' . __( '*** Although the import process does it\'s best to find the custom fields values it is not guaranteed since each provider outputs that information in their feeds differently.', 'gofetch-wpjm' ) . ' ' . __( 'Check the sample table to see all the valid fields in the RSS feed.', 'gofetch-wpjm' ) . (( !gfjwjm_fs()->is_plan__premium_only( 'proplus', true ) ? html( 'p', html( 'code', html( 'span class="dashicons dashicons-warning"', '&nbsp;' ) . ' ' . __( 'The Pro+ plan includes the \'Featured\' meta field to feature imported jobs.' ) ) ) : '' )),
            'section' => 'meta',
        );
        $title_desc[$i++] = array(
            'title' => '<span class="dashicons dashicons-admin-users title-icon"></span>' . __( 'Posted by', 'gofetch-wpjm' ),
            'desc'  => __( 'Choose the user to be assigned to the jobs being imported. Note that this option is not saved on the template and applies only for this import.', 'gofetch-wpjm' ),
        );
        $title_desc[$i++] = array(
            'title'  => '<span class="dashicons dashicons-filter title-icon"></span>' . __( 'Filter', 'gofetch-wpjm' ),
            'desc'   => __( 'Use the filters below to limit the items that will be imported. Note that filter options are not saved on the template and apply only for this import.', 'gofetch-wpjm' ),
            'tip'    => __( 'Filters allow you to further refine the jobs that should be imported.', 'gofetch-wpjm' ) . (( !gfjwjm_fs()->is_plan__premium_only( 'proplus', true ) ? html( 'p', html( 'code', html( 'span class="dashicons dashicons-warning"', '&nbsp;' ) . ' ' . __( 'The Pro+ plan provides an additional filter to filter jobs by their feed date.' ) ) ) : '' )),
            'header' => 'h2 style="font-style: italic;"',
        );
        $title_desc[$i++] = array(
            'title'  => '<span class="icon icon-goft-floppy-1 title-icon"></span>' . __( 'Save', 'gofetch-wpjm' ),
            'desc'   => __( "Save your current settings as a template to simplify future imports or to use later in scheduled imports.", 'gofetch-wpjm' ) . '<br/>' . html( 'small class="save-warning"', '<span class="dashicons dashicons-warning"></span>' . __( "The <em>Posted by</em> and <em>Filter</em> settings are not saved. Those settings are applied to the current import only.", 'gofetch-wpjm' ) ),
            'header' => 'h2 style="font-style: italic;"',
        );
        if ( empty($title_desc[$section]) ) {
            return array();
        }
        $defaults = array(
            'title'       => '',
            'description' => '',
            'header'      => 'h4 style="font-style: italic;"',
            'section'     => '',
        );
        return wp_parse_args( $title_desc[$section], $defaults );
    }
    
    /**
     * Outputs a placeholder for the providers RSS setup instructions.
     */
    public function section_providers_placeholder()
    {
        $title = __( '<h3>Follow these instructions to configure this provider ...</h3>', 'gofetch-wpjm' );
        $placeholder = html( 'div', array(
            'class' => 'providers-placeholder',
        ), $title . html( 'div', array(
            'class' => 'providers-placeholder-content',
        ), '&nbsp;' ) );
        return $placeholder;
    }
    
    /**
     * Outputs section breaks.
     */
    public function section_break( $section )
    {
        static  $section ;
        
        if ( !$section ) {
            $section = 1;
        } else {
            $section++;
        }
        
        $title_desc_html = '';
        $title_desc = $this->section_title( $section );
        $section_slug = $section;
        if ( !empty($title_desc['section']) ) {
            $section_slug = $title_desc['section'];
        }
        if ( !empty($title_desc['title']) ) {
            $title_desc_html .= html( $title_desc['header'], array(
                'class' => "section-{$section_slug}",
            ), $title_desc['title'] );
        }
        
        if ( !empty($title_desc['desc']) ) {
            
            if ( !empty($title_desc['tip']) ) {
                $tip = html( 'span', array(
                    'class'        => 'dashicons-before dashicons-editor-help tip-icon bc-tip',
                    'title'        => __( 'Click to read additional info...', 'gofetch-wpjm' ),
                    'data-tooltip' => ( BC_Framework_ToolTips::supports_wp_pointer() ? $title_desc['tip'] : __( 'Click for more info', 'gofetch-wpjm' ) ),
                ) );
                if ( !BC_Framework_ToolTips::supports_wp_pointer() ) {
                    $tip .= html( "div class='tip-content'", $title_desc['tip'] );
                }
                $title_desc['desc'] .= ' ' . $tip;
            }
            
            $title_desc_html .= html( 'p', array(
                'class' => "section-{$section_slug}",
                'style' => "font-weight: normal",
            ), $title_desc['desc'] );
        }
        
        return $title_desc_html . '<hr/>';
    }
    
    // _Form handling.
    /**
     * The main handler that starts the import process and/or saves the user settings and/or deletes user templates.
     */
    public function form_handler()
    {
        global  $_wp_using_ext_object_cache, $goft_wpjm_options ;
        if ( empty($_POST['submit']) && empty($_POST['action']) && empty($_POST['delete_template']) ) {
            return false;
        }
        
        if ( !empty($_POST['delete_template']) ) {
            
            if ( !empty($_POST['templates_list']) ) {
                $this->delete_template( sanitize_text_field( $_POST['templates_list'] ) );
            } else {
                echo  scb_admin_notice( __( 'Please select a template to delete.', 'gofetch-wpjm' ), 'error' ) ;
            }
            
            return;
        }
        
        // Only skip nonce check when saving a template since it already checks the nonce when calling the parent form handler.
        
        if ( empty($_POST['save_template']) ) {
            check_admin_referer( $this->nonce );
        } else {
            
            if ( empty($_POST['template_name']) ) {
                echo  scb_admin_notice( __( 'Please name your template.', 'gofetch-wpjm' ), 'error' ) ;
                return;
            }
        
        }
        
        // Save the template and settings if requested.
        if ( !empty($_POST['save_template']) ) {
            $this->save_template( sanitize_text_field( $_POST['template_name'] ) );
        }
        // Skip earlier if the import was not requested.
        if ( empty($_POST['submit']) ) {
            return;
        }
        $defaults = array(
            'post_author'      => 1,
            'tax_input'        => array(),
            'smart_tax_input'  => '',
            'meta'             => array(),
            'source'           => array(),
            'from_date'        => '',
            'to_date'          => '',
            'limit'            => '',
            'keywords'         => '',
            'keywords_exclude' => '',
            'rss_feed_import'  => '',
            'logos'            => '',
            'special'          => '',
        );
        $params = wp_parse_args( wp_array_slice_assoc( wp_unslash( $_POST ), array_keys( $defaults ) ), $defaults );
        // Temporarily turn off the object cache while we deal with transients since
        // some caching plugins like W3 Total Cache conflicts with our work.
        $_wp_using_ext_object_cache_previous = $_wp_using_ext_object_cache;
        $_wp_using_ext_object_cache = false;
        // Retrieve the cached RSS items imported earlier.
        // Get the cached RSS items chunks.
        $chunks = get_transient( '_goft-rss-feed-chunks' );
        $chunks = ( $chunks ? $chunks : 1 );
        $items = array();
        $skip_chunks = false;
        
        if ( !empty($chunks) ) {
            // Iterate trough each cached RSS items chunk.
            for ( $i = 0 ;  $i < $chunks ;  $i++ ) {
                $chunk = get_transient( "_goft-rss-feed-{$i}" );
                
                if ( !$chunk ) {
                    $skip_chunks = true;
                    break;
                }
                
                $items = array_merge( $items, (array) $chunk );
            }
            // Restore the caching values.
            $_wp_using_ext_object_cache = $_wp_using_ext_object_cache_previous;
        }
        
        // If we can't get the items previously stored in chunks for faster import try to fetch the feed items directly again now.
        
        if ( $skip_chunks ) {
            // __LOG.
            $vars = array(
                'context' => 'GOFT :: WARNING: COULD NOT GET CHUNKS FOR IMPORT! FETCHING ITEMS FROM FEED DIRECTLY!',
                'url'     => ( !empty($_POST['rss_feed_import']) ? wp_strip_all_tags( $_POST['rss_feed_import'] ) : 'URL is EMPTY!' ),
                'params'  => $params,
            );
            BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
            //
            
            if ( !empty($_POST['rss_feed_import']) ) {
                $url = wp_strip_all_tags( $_POST['rss_feed_import'] );
                $results = GoFetch_WPJM_Importer::import_feed( $url, $_POST, $cache = false );
                $items = $results['items'];
            }
        
        }
        
        
        if ( empty($items) ) {
            echo  scb_admin_notice( __( "Sorry, couldn't find anything to import.", 'gofetch-wpjm' ), 'error' ) ;
            return;
        }
        
        if ( empty($_POST['save_template']) ) {
            add_action( 'admin_notices', array( $this, 'admin_msg' ) );
        }
        $results = GoFetch_WPJM_Importer::import( $items, $params );
        self::$imported = $results;
        // Clear memory.
        $results = null;
    }
    
    /**
     * Retrieves all the form data that will be stored in the template.
     */
    private function handle_template_settings()
    {
        $params = array(
            'rss_feed_import' => esc_url_raw( $_POST['rss_feed_import'] ),
            'logos'           => (int) $_POST['logos'],
            'special'         => stripslashes_deep( $_POST['special'] ),
            'tax_input'       => stripslashes_deep( $_POST['tax_input'] ),
            'smart_tax_input' => stripslashes_deep( $_POST['smart_tax_input'] ),
            'meta'            => stripslashes_deep( $_POST['meta'] ),
            'source'          => stripslashes_deep( $_POST['source'] ),
        );
        return $params;
    }
    
    /**
     * Save the user settings.
     */
    private function save_template( $template_name )
    {
        global  $goft_wpjm_options ;
        $result = parent::form_handler();
        if ( !$result ) {
            die( 0 );
        }
        $templates = GoFetch_WPJM_Helper::get_sanitized_templates();
        // Get the user params for the current template.
        $params = $this->handle_template_settings();
        $templates[$template_name] = $params;
        $goft_wpjm_options->templates = $templates;
    }
    
    /**
     * Saves the user settings in a template using AJAX:
     */
    public function ajax_save_template()
    {
        $this->save_template( sanitize_text_field( $_POST['template'] ) );
        echo  1 ;
        die( 1 );
    }
    
    /**
     * Deletes an existing template.
     */
    private function delete_template( $name )
    {
        global  $goft_wpjm_options ;
        $name = GoFetch_WPJM_Helper::remove_slashes( $name );
        $templates = GoFetch_WPJM_Helper::get_sanitized_templates();
        
        if ( empty($templates[$name]) ) {
            echo  scb_admin_notice( __( 'Could not delete template. Template name not found.', 'gofetch-wpjm' ) ) ;
            return;
        }
        
        // Clear memory.
        $templates[$name] = null;
        unset( $templates[$name] );
        $goft_wpjm_options->templates = $templates;
        echo  scb_admin_notice( __( 'The template was deleted.', 'gofetch-wpjm' ) ) ;
    }
    
    /**
     * Retrieve terms key/value pairs given a taxonomy.
     */
    private function _get_terms_key_value_pairs( $taxonomy )
    {
        $terms = get_terms( $taxonomy, array(
            'hide_empty' => false,
        ) );
        $terms_kvp[''] = __( 'None', 'gofetch-wpjm' );
        foreach ( $terms as $term ) {
            $terms_kvp[$term->slug] = $term->name;
        }
        return $terms_kvp;
    }
    
    /**
     * Retrieve default values for a given meta field.
     */
    protected function get_default_value_for_meta( $field )
    {
        
        if ( !empty($_POST['meta'][$field]) ) {
            if ( !empty($field['type']) && 'textarea' !== $field['type'] ) {
                return wp_strip_all_tags( $_POST['meta'][$field] );
            }
            return stripslashes( $_POST['meta'][$field] );
        }
        
        $value = '';
        return apply_filters( 'goft_wpjm_default_value_for_field', $value, $field );
    }
    
    /**
     * Retrieve default values for a given taxonomy.
     */
    protected function get_default_value_for_tax( $taxonomy )
    {
        if ( !empty($_POST['tax_input'][$taxonomy]) ) {
            return wp_strip_all_tags( $_POST['tax_input'][$taxonomy] );
        }
        $value = '';
        return apply_filters( 'goft_wpjm_default_value_for_taxonomy', $value, $taxonomy );
    }
    
    // Output callbacks for the 'render' property.
    /**
     * Outputs the list of job listers.
     */
    public function output_job_listers()
    {
        $job_listers_raw = apply_filters( 'goft_wpjm_job_listers', get_users() );
        $job_listers = array();
        foreach ( $job_listers_raw as $job_lister ) {
            $job_listers[$job_lister->ID] = $job_lister->display_name;
        }
        $type = array(
            'title'    => __( 'Job Lister', 'gofetch-wpjm' ),
            'type'     => 'select',
            'name'     => 'post_author',
            'extra'    => array(
            'id' => 'job_lister',
        ),
            'choices'  => $job_listers,
            'selected' => get_current_user_id(),
        );
        if ( !empty($_POST['post_author']) ) {
            $type['selected'] = wp_strip_all_tags( $_POST['post_author'] );
        }
        $output = $this->input( $type );
        return $output;
    }
    
    /**
     * Output the list of taxonomies.
     */
    public function output_taxonomy( $taxonomy = '' )
    {
        // For older PHP versions (< 5.3.0) output each taxonomy considering a previous stored list.
        if ( !$taxonomy ) {
            $taxonomy = self::$taxonomy_compat[self::$taxonomy_compat_index++];
        }
        $default_value = $this->get_default_value_for_tax( $taxonomy->name );
        $type = array(
            'title'    => $taxonomy->label,
            'type'     => 'select',
            'name'     => 'tax_input[' . $taxonomy->name . ']',
            'extra'    => array(
            'id'      => $taxonomy->name,
            'section' => 'taxonomies',
            'default' => $default_value,
        ),
            'choices'  => $this->_get_terms_key_value_pairs( $taxonomy->name ),
            'selected' => $default_value,
        );
        
        if ( false !== strpos( $taxonomy->name, '_tag' ) ) {
            $type['type'] = 'text';
            $type['extra']['class'] = 'regular-text';
            $type['extra']['section'] = 'taxonomies';
            $type['value'] = $default_value;
        }
        
        return $this->input( $type );
    }
    
    /**
     * Outputs the date interval settings.
     */
    public function output_date_span()
    {
        return apply_filters( 'goft_wpjm_setting_date_span', false );
    }
    
    /**
    	 * Outputs the affiliate parameter.
    	 *
    	public function output_affiliate_param() {
    		return apply_filters( 'goft_wpjm_setting_url_params', false );
    	}*/
    /**
     * Outputs the main fields table.
     */
    public function output_sample_table()
    {
        return GoFetch_WPJM_Sample_Table::display();
    }
    
    /**
     * Overrides the parent method to provide a different form button.
     */
    public function form_table_wrap( $content )
    {
        $args = array(
            'class' => 'button-primary import-posts',
            'value' => __( 'Go Fetch Jobs!', 'gofetch-wpjm' ),
        );
        $output = self::table_wrap( $content );
        $output = self::extra_content( $output );
        $output = $this->form_wrap( $output, $args );
        return $output;
    }
    
    /**
     * Outputs the <table> wrapper.
     */
    public static function table_wrap( $content )
    {
        return html( "table class='form-table goft'", $content );
    }
    
    /**
     * Outputs additional content within the main form.
     */
    public static function extra_content( $content )
    {
        $content .= '<span class="helper-fields"></span>';
        $content = $content . self::get_geocomplete_fields();
        return apply_filters( 'goft_wpjm_form_extra_content', $content );
    }
    
    /**
     * The admin messages to display after user actions.
     */
    public function admin_msg( $msg = '', $class = 'updated' )
    {
        $stats = self::$imported;
        
        if ( !empty($stats['imported']) || !empty($stats['updated']) || !empty($stats['excluded']) ) {
            $msg = html( 'p', html( 'em', html( 'strong', sprintf( __( 'Imported %d NEW %s!', 'gofetch-wpjm' ), $stats['imported'], _n(
                'job',
                'jobs',
                $stats['imported'],
                'gofetch-wpjm'
            ) ) ) ) );
            $li = html( 'li', html( 'em', sprintf( __( 'Skipped: %d %s <small>(discarded - enforced import limit)</small>', 'gofetch-wpjm' ), $stats['limit'], _n(
                'job',
                'jobs',
                $stats['limit'],
                'gofetch-wpjm'
            ) ) ) );
            $li .= html( 'li', html( 'em', sprintf( __( 'Updated: %d %s <small>(updated - changes found)</small>', 'gofetch-wpjm' ), $stats['updated'], _n(
                'job',
                'jobs',
                $stats['updated'],
                'gofetch-wpjm'
            ) ) ) );
            $li .= html( 'li', html( 'em', sprintf( __( 'Duplicate: %d %s <small>(discarded - already exist in DB)</small>', 'gofetch-wpjm' ), $stats['duplicates'], _n(
                'job',
                'jobs',
                $stats['duplicates'],
                'gofetch-wpjm'
            ) ) ) );
            $li .= html( 'li', html( 'em', sprintf( __( 'Excluded: %d %s <small>(discarded - criteria not met)</small>', 'gofetch-wpjm' ), $stats['excluded'], _n(
                'job',
                'jobs',
                $stats['excluded'],
                'gofetch-wpjm'
            ) ) ) );
            $msg .= html( 'ul', $li );
            $msg .= html( 'p', html( 'small', sprintf( __( 'Feed contained <strong>%d %s</strong>.', 'gofetch-wpjm' ), $stats['in_rss_feed'], _n(
                'job',
                'jobs',
                $stats['in_rss_feed'],
                'gofetch-wpjm'
            ) ) ) );
            $msg .= html( 'p', html( 'small', sprintf( __( 'Elapsed time: <strong>%s</strong>.', 'gofetch-wpjm' ), ( $stats['duration'] > 60 ? date( 'i:s', $stats['duration'] ) : __( '< 1 min', 'gofetch-wpjm' ) ), 'gofetch-wpjm' ) ) );
            $msg = html( 'div class="goft-stats"', $msg );
        } else {
            $msg .= __( 'No new jobs found.', 'gofetch-wpjm' );
        }
        
        
        if ( !empty($_POST['save_template']) ) {
            if ( $msg ) {
                $msg .= '<br/><br/>';
            }
            $msg .= __( 'Template <strong>saved</strong>.', 'gofetch-wpjm' );
        }
        
        echo  scb_admin_notice( $msg, $class ) ;
    }

}
new GoFetch_WPJM_Admin_Builder( GOFT_WPJM_PLUGIN_FILE, $goft_wpjm_options );
new GoFetch_WPJM_Help_Admin_Screen_Options();