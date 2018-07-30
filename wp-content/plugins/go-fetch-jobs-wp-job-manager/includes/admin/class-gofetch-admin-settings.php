<?php

/**
 * Admin options for the 'Settings' page.
 *
 * @package GoFetchJobs/Admin/Settings
 */
// __Classes.
/**
 * The plugin admin settings class.
 */
class GoFetch_WPJM_Admin_Settings extends BC_Framework_Tabs_Page
{
    /**
     * Constructor.
     */
    function __construct()
    {
        global  $goft_wpjm_options ;
        parent::__construct( $goft_wpjm_options, 'gofetch-wpjm' );
        add_action( 'admin_init', array( $this, 'init_tooltips' ), 9999 );
        add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-settings_form_handler', array( $this, 'maybe_clear_cache' ) );
        add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-settings', array( $this, 'init_tab_advanced' ), 20 );
    }
    
    /**
     * Load tooltips for the current screen.
     */
    public function init_tooltips()
    {
        new BC_Framework_ToolTips( array( 'toplevel_page_' . GoFetch_WPJM()->slug ) );
    }
    
    /**
     * Setup the plugin sub-menu.
     */
    public function setup()
    {
        $this->args = array(
            'page_title'            => __( 'Settings', 'gofetch-wpjm' ),
            'menu_title'            => __( 'Settings', 'gofetch-wpjm' ),
            'page_slug'             => 'go-fetch-jobs-wpjm-settings',
            'parent'                => GoFetch_WPJM()->slug,
            'admin_action_priority' => 10,
        );
    }
    
    // __Hook Callbacks.
    /**
     * Initialize tabs.
     */
    protected function init_tabs()
    {
        $_SERVER['REQUEST_URI'] = esc_url_raw( remove_query_arg( array( 'firstrun' ), $_SERVER['REQUEST_URI'] ) );
        $this->tabs->add( 'importer', __( 'Importer', 'gofetch-wpjm' ) );
        $this->tab_importer();
    }
    
    /**
     * Init the 'Advanced' as the last tab.
     */
    public function init_tab_advanced()
    {
        $this->tabs->add( 'advanced', __( 'Advanced', 'gofetch-wpjm' ) );
        $this->tab_advanced();
    }
    
    /**
     * General settings tab.
     */
    protected function tab_importer()
    {
        global  $goft_wpjm_options ;
        $this->tab_sections['importer']['admin'] = array(
            'title'  => __( 'Admin', 'gofetch-wpjm' ),
            'fields' => array( array(
            'title' => __( 'Imported Jobs Filter', 'gofetch-wpjm' ),
            'name'  => 'admin_jobs_filter',
            'type'  => 'checkbox',
            'desc'  => __( 'Yes', 'gofetch-wpjm' ),
            'tip'   => __( 'Enable this option to display an additional dropdown to filter jobs by user submitted/imported jobs, on job listings.', 'gofetch-wpjm' ),
        ), array(
            'title' => __( 'Provider Filter', 'gofetch-wpjm' ),
            'name'  => 'admin_provider_filter',
            'type'  => 'checkbox',
            'desc'  => __( 'Yes', 'gofetch-wpjm' ),
            'tip'   => __( 'Enable this option to display an additional dropdown to filter imported jobs by provider, on job listings.', 'gofetch-wpjm' ),
        ), array(
            'title' => __( 'Provider Column', 'gofetch-wpjm' ),
            'name'  => 'admin_jobs_provider_col',
            'type'  => 'checkbox',
            'desc'  => __( 'Yes', 'gofetch-wpjm' ),
            'tip'   => __( 'Enable this option to display an additional column with the job provider website, on job listings .', 'gofetch-wpjm' ),
        ) ),
        );
        $this->tab_sections['importer']['importer'] = array(
            'title'  => __( 'Importer', 'gofetch-wpjm' ),
            'fields' => array( array(
            'title'   => __( 'Duplicates Behavior', 'gofetch-wpjm' ),
            'name'    => 'rss_item_check_updates',
            'type'    => 'select',
            'choices' => array(
            'ignore_all' => __( 'Ignore All', 'gofetch-wpjm' ),
            'update'     => __( 'Update', 'gofetch-wpjm' ),
        ),
            'tip'     => __( 'The import process uses the RSS items external link as the unique identifier for all jobs and can quickly discard duplicates just by simply comparing the external links in the RSS feed with the ones on the database (<code>Ignore All</code> option).', 'gofetch-wpjm' ) . '<br/><br/>' . __( 'Since sometimes, an RSS feed can contain updated jobs (considered duplicate by default), you can choose to have the importer check if these jobs should be updated instead of considered duplicate (<code>Update</code> option) <code>(*)</code>.', 'gofetch-wpjm' ) . '<br/><br/>' . __( '<code>(*)</code> Please note that this option will make the import process slower since it needs to do additional checks.', 'gofetch-wpjm' ),
        ), array(
            'title'   => __( 'Keyword Matching', 'gofetch-wpjm' ),
            'name'    => 'keyword_matching',
            'type'    => 'select',
            'choices' => array(
            'all'     => __( 'Content & Title', 'gofetch-wpjm' ),
            'content' => __( 'Content', 'gofetch-wpjm' ),
            'title'   => __( 'Title', 'gofetch-wpjm' ),
        ),
            'tip'     => __( 'Choose whether keywords matching should be done against each job content and/or title.', 'gofetch-wpjm' ),
        ), array(
            'title' => __( 'Use CORS Proxy', 'gofetch-wpjm' ),
            'name'  => 'use_cors_proxy',
            'type'  => 'checkbox',
            'desc'  => __( 'Yes', 'gofetch-wpjm' ),
            'tip'   => __( 'Some RSS feeds that use <em>https://</em> might be considered invalid if your site does not use <em>https://</em>.', 'gofetch-wpjm' ) . '<br/><br/>' . sprintf( __( 'Check this option to let the plugin try to load these feeds through a <a href="%s">CORS proxy</a>.', 'gofetch-wpjm' ), 'https://crossorigin.me/' ) . ' ' . __( 'Leave it unchecked if you don\'t have issues loading RSS feeds.', 'gofetch-wpjm' ),
        ) ),
        );
        $tip_geocode = sprintf( __( '<a href="%s">Create/get your API key</a> and paste it here to if you want to geocode the <code>\'location\'</code> field, on the import jobs screen.', 'gofetch-wpjm' ), 'https://developers.google.com/maps/documentation/geocoding/start#get-a-key' ) . '<br/><br/>' . __( "Make sure you enable the <code>'Google Maps Javascript API'</code> and the <code>'Google Maps Geocoding API'</code> on your <em>Google Developers Console</em>. Otherwise you'll get javascript warnings and geocoding will fail.", 'gofetch-wpjm' );
        $geocoding_fields = array( array(
            'title' => __( 'Google API Key', 'gofetch-wpjm' ),
            'name'  => 'geocode_api_key',
            'type'  => 'text',
            'desc'  => sprintf( __( 'Read the plugin documentation on <a href="%s" target="_new" rel="nofollow">How to Generate a Google Maps API Key</a>.', 'gofetch-wpjm' ), 'https://gofetchjobs.com/documentation/generate-google-maps-api-key/' ),
            'tip'   => $tip_geocode,
        ) );
        $this->tab_sections['importer']['geocode'] = array(
            'title'  => __( 'Geocoding', 'gofetch-wpjm' ),
            'fields' => $geocoding_fields,
        );
        $this->tab_sections['importer']['jobs'] = array(
            'title'  => __( 'Imported Jobs', 'gofetch-wpjm' ),
            'fields' => array(
            array(
            'title'   => __( 'Status', 'gofetch-wpjm' ),
            'name'    => 'post_status',
            'type'    => 'select',
            'choices' => array(
            'publish' => __( 'Publish', 'gofetch-wpjm' ),
            'pending' => __( 'Pending', 'gofetch-wpjm' ),
            'draft'   => __( 'Draft', 'gofetch-wpjm' ),
        ),
            'tip'     => __( 'Choose the status to be assigned to each imported job.', 'gofetch-wpjm' ),
        ),
            array(
            'title' => __( 'Duration', 'gofetch-wpjm' ),
            'name'  => 'jobs_duration',
            'type'  => 'text',
            'extra' => array(
            'class' => 'small-text',
        ),
            'tip'   => __( 'The default job duration for imported jobs.', 'gofetch-wpjm' ),
        ),
            array(
            'title' => __( 'Allow Visitors to Apply', 'gofetch-wpjm' ),
            'name'  => 'allow_visitors_apply',
            'type'  => 'checkbox',
            'desc'  => __( 'Yes', 'gofetch-wpjm' ),
            'tip'   => __( 'Enable this option to make the import jobs external apply link visible to site visitors. By default, only registered users can see the application link.', 'gofetch-wpjm' ),
        ),
            array(
            'title' => __( 'Read More Text', 'gofetch-wpjm' ),
            'name'  => 'read_more_text',
            'type'  => 'text',
            'extra' => array(
            'class' => 'small-text2',
        ),
            'tip'   => __( 'The text appended to job description excerpts.', 'gofetch-wpjm' ),
        ),
            array(
            'title' => __( 'Canonical Links', 'gofetch-wpjm' ),
            'name'  => 'canonical_links',
            'type'  => 'checkbox',
            'desc'  => __( 'Yes', 'gofetch-wpjm' ),
            'tip'   => sprintf( __( '<a href="%s">Canonical links</a> allow you to notify search engines about the original source of the jobs imported.', 'gofetch-wpjm' ), 'https://www.ltnow.com/rel-canonical-seo/' ) . '<br/><br/>' . __( 'Since duplicate content is a big no-no to search engines, having jobs with the same content as the original source or with similar content on your website is seen as a negative, and may be used by Google to devalue your website when determining rankings.', 'gofetch-wpjm' ) . '<br/><br/>' . __( 'Checking this option can improve your SEO on this aspect.', 'gofetch-wpjm' ),
        )
        ),
        );
    }
    
    /**
     * Advanced settings tab.
     */
    protected function tab_advanced()
    {
        global  $goft_wpjm_options ;
        $this->tab_sections['advanced']['log'] = array(
            'title'  => __( 'Logging', 'gofetch-wpjm' ),
            'fields' => array( array(
            'title' => __( 'Debug Log', 'gofetch-wpjm' ),
            'name'  => 'debug_log',
            'type'  => 'checkbox',
            'desc'  => __( 'Enable', 'gofetch-wpjm' ),
            'tip'   => __( 'Enables debug logging. Use it to report any errors to the support team. Keep it disabled, otherwise.', 'gofetch-wpjm' ) . '<br/><br/>' . sprintf( __( '<code>NOTE:</code> You must <a href="%s">enable</a> <code>WP_DEBUG_LOG</code> on your \'wp-config.php\' file.', 'gofetch-wpjm' ), 'https://codex.wordpress.org/Editing_wp-config.php#Configure_Error_Logging' ),
        ), array(
            'title' => __( 'Clear Cache', 'gofetch-wpjm' ),
            'name'  => '_blank',
            'type'  => 'checkbox',
            'desc'  => __( 'Yes. Clear cache on \'Save Changes\'.', 'gofetch-wpjm' ),
            'tip'   => __( 'GOFJ caches some data for faster job imports and to help find duplicates. If you delete recent jobs and immediately try to import them again, the importer will usually refuse if it founds the same jobs in cache.', 'gofetch-wpjm' ) . '<br/><br/>' . __( 'Check this option to clear all cached data when you click \'Save Changes\'.', 'gofetch-wpjm' ),
            'value' => 'clear_cache',
        ) ),
        );
    }
    
    /**
     * Clears cached data.
     */
    public function maybe_clear_cache( $options )
    {
        if ( empty($_POST['_blank']) || 'clear_cache' !== $_POST['_blank'] ) {
            return;
        }
        delete_transient( 'goft_wpjm_imported_jobs_cached' );
        delete_transient( 'goft_wpjm_imported_jobs' );
    }
    
    /**
     * The admin message.
     */
    public function admin_msg( $msg = '', $class = 'updated' )
    {
        
        if ( empty($msg) ) {
            if ( !empty($_POST['_blank']) && 'clear_cache' === $_POST['_blank'] ) {
                $msg = __( 'Cache <strong>cleared</strong>!', $this->textdomain ) . '<br/><br/>';
            }
            $msg .= __( 'Settings <strong>saved</strong>.', $this->textdomain );
        }
        
        echo  scb_admin_notice( $msg, $class ) ;
    }

}
$GLOBALS['goft_wpjm']['settings'] = new GoFetch_WPJM_Admin_Settings();