<?php

/**
 * Plugin Name: Go Fetch Jobs (for WP Job Manager)
 * Version:     1.4.6
 * Description: Instantly populate your WP Job Manager database using RSS job feeds from the most popular job sites.
 * Author:      SebeT
 * Author URI:  http://bruno-carreco.com
 * Plugin URI:  http://bruno-carreco.com/wpuno/demo/wp/go-fetch-jobs-wp-job-manager/?demo=1
 * Other:       Icons by freepik 'http://www.freepik.com'
 *
 * @fs_premium_only /includes/premium/
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}

// Begin Freemius.

if ( !function_exists( 'gfjwjm_fs' ) ) {
    /**
     * Create a helper function for easy SDK access.
     */
    function gfjwjm_fs()
    {
        global  $gfjwjm_fs ;
        
        if ( !isset( $gfjwjm_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/includes/freemius/start.php';
            $gfjwjm_fs = fs_dynamic_init( array(
                'id'         => '192',
                'slug'       => 'gofetch-wpjm',
                'public_key' => 'pk_d8c021486da49f69324049b5736a3',
                'menu'       => array(
                'slug'    => 'go-fetch-jobs-wp-job-manager',
                'support' => false,
            ),
                'is_live'    => true,
                'is_premium' => false,
            ) );
        }
        
        return $gfjwjm_fs;
    }
    
    gfjwjm_fs();
    gfjwjm_fs()->add_action( 'after_uninstall', array( 'GoFetch_WPJM', 'uninstall' ) );
    // End Freemius.
    register_activation_hook( __FILE__, array( 'GoFetch_WPJM', 'activate' ) );
    // Initialize the plugin after all plugins are loaded.
    add_action( 'plugins_loaded', 'GoFetch_WPJM', 998 );
    if ( !class_exists( 'GoFetch_WPJM' ) ) {
        /**
         * Core class.
         */
        final class GoFetch_WPJM
        {
            /**
             * @var The plugin version.
             */
            public  $version = '1.4.6' ;
            /**
             * @var The plugin slug.
             */
            public  $slug = 'go-fetch-jobs-wp-job-manager' ;
            /**
             * @var The plugin post type.
             */
            public  $post_type = 'goft_wpjm_schedule' ;
            /**
             * @var The expected theme post type.
             */
            public  $parent_post_type = 'job_listing' ;
            /**
             * @var The log messages limit.
             */
            public  $log_limit = 10 ;
            /**
             * @var The single instance of the class.
             */
            protected static  $_instance = null ;
            /**
             * Main 'Go Fetch Jobs' Instance.
             *
             * Ensures only one instance is loaded or can be loaded.
             *
             * @see GoFetch_Jobs()
             *
             * @return GoFetch_Jobs - Main instance
             */
            public static function instance()
            {
                if ( is_null( self::$_instance ) ) {
                    self::$_instance = new self();
                }
                return self::$_instance;
            }
            
            /**
             * The Constructor.
             */
            public function __construct()
            {
                $this->define_constants();
                $this->includes();
                $this->init_hooks();
                do_action( 'gofetch_wpjm_loaded' );
            }
            
            /**
             * Include required core files used in admin and on the frontend.
             */
            public function includes()
            {
                // Framework Core.
                require_once 'includes/framework/load.php';
                // Common dependencies.
                require_once 'includes/settings.php';
                require_once 'includes/class-gofetch-html-table.php';
                require_once 'includes/class-gofetch-helper.php';
                require_once 'includes/class-gofetch-importer.php';
                // RSS providers.
                require_once 'includes/class-gofetch-rss-providers.php';
                // Specific.
                if ( !$this->is_request( 'admin' ) ) {
                    // Frontend.
                    require_once 'includes/wpjm/class-gofetch-wpjm.php';
                }
                // External.
                if ( !class_exists( 'OpenGraph' ) ) {
                    require_once 'includes/external/OpenGraph.php';
                }
                
                if ( $this->is_request( 'any' ) ) {
                    require_once 'includes/admin/class-gofetch-admin.php';
                    require_once 'includes/wpjm/admin/class-gofetch-wpjm-importer.php';
                    if ( class_exists( 'GoFetch_WPJM_Admin_Settings' ) ) {
                        require_once 'includes/wpjm/admin/class-gofetch-wpjm-settings.php';
                    }
                }
            
            }
            
            /**
             * Define Constants.
             */
            private function define_constants()
            {
                $this->define( 'GOFT_WPJM_PLUGIN_FILE', __FILE__ );
            }
            
            /**
             * Hook into actions and filters.
             */
            private function init_hooks()
            {
                register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
                add_action( 'init', array( $this, 'init' ), 0 );
            }
            
            /**
             * Init plugin when WordPress Initializes.
             */
            public function init()
            {
                add_action( 'init', array( $this, 'maybe_install' ) );
                // Before init action.
                do_action( 'before_gofetch_wpjm_init' );
                // Set up localization.
                $this->load_plugin_textdomain();
                // Init action.
                do_action( 'gofetch_wpjm_init' );
            }
            
            /**
             * Load Localization files.
             *
             * Note: the first-loaded translation file overrides any following ones if the same translation is present.
             *
             * Locales are found in:
             * - WP_LANG_DIR/plugins/gofetch-wpjm-LOCALE.mo
             */
            public function load_plugin_textdomain()
            {
                $locale = apply_filters( 'plugin_locale', get_locale(), 'gofetch-wpjm' );
                load_textdomain( 'gofetch-wpjm', WP_LANG_DIR . '/plugins/gofetch-wpjm-' . $locale . '.mo' );
                load_plugin_textdomain( 'gofetch-wpjm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
            }
            
            /**
             * Define constant if not already set.
             *
             * @param  string      $name
             * @param  string|bool $value
             */
            private function define( $name, $value )
            {
                if ( !defined( $name ) ) {
                    define( $name, $value );
                }
            }
            
            /**
             * What type of request is this?
             *
             * string $type ajax, frontend or admin
             *
             * @return bool
             */
            private function is_request( $type )
            {
                switch ( $type ) {
                    case 'admin':
                        return is_admin();
                    case 'ajax':
                        return defined( 'DOING_AJAX' );
                    case 'cron':
                        return defined( 'DOING_CRON' );
                    case 'frontend':
                        return (!is_admin() || defined( 'DOING_AJAX' )) && !defined( 'DOING_CRON' );
                    default:
                        return is_admin() || defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' );
                }
            }
            
            /*
             * On deactivation, remove plugin related stuff.
             */
            public function deactivate()
            {
                wp_clear_scheduled_hook( 'gofetch_wpjm_importer' );
            }
            
            /*
             * On activate, set default stuff.
             */
            public static function activate()
            {
                add_option( 'goft-wpjm-activated', 1 );
            }
            
            /*
             * On uninstall, do stuff.
             */
            public static function uninstall()
            {
            }
            
            /**
             * Install any sample options.
             */
            public function maybe_install()
            {
                global  $goft_wpjm_options ;
                
                if ( !class_exists( 'WP_Job_Manager' ) ) {
                    add_option( 'goft-wpjm-error', 1 );
                    return;
                } else {
                    delete_option( 'goft-wpjm-error' );
                }
                
                $example = 'rss-example-theguardian-wordpress-fulltime-jobs';
                if ( !is_admin() || get_option( 'goft-wpjm-samples-installed' ) || !get_option( 'goft-wpjm-activated' ) ) {
                    return;
                }
                add_option( 'goft-wpjm-samples-installed', 1 );
                delete_option( 'goft-wpjm-activated' );
                // Skip sample install if there are already templates set.
                if ( !empty($goft_wpjm_options->templates) ) {
                    return;
                }
                // Pre configure a default template.
                // Provider.
                $feed_url = 'https://jobs.theguardian.com/jobsrss/?keywords=wordpress%20full-time&radialtown=&';
                // Taxonomy.
                $job_type = apply_filters(
                    'goft_wpjm_default_value_for_taxonomy',
                    '',
                    'job_listing_type',
                    'full-time'
                );
                // Meta.
                $expires = GoFetch_WPJM_Specific_Import::get_expire_date();
                $templates = $goft_wpjm_options->templates;
                $templates[$example] = array(
                    'rss_feed_import' => $feed_url,
                    'tax_input'       => array(
                    'job_listing_type' => $job_type,
                ),
                    'meta'            => array(
                    '_job_expires' => $expires,
                ),
                    'source'          => array(
                    'name'    => 'Guardian Jobs',
                    'website' => 'jobs.theguardian.com',
                    'logo'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0e/The_Guardian.svg/2000px-The_Guardian.svg.png',
                ),
                );
                $goft_wpjm_options->templates = $templates;
                // Add a sample schedule.
                $post = array(
                    'post_title'  => 'Daily Import Example [WordPress Full-Time Jobs] (theguardian.com)',
                    'post_type'   => $this->post_type,
                    'post_status' => 'pending',
                    'meta_input'  => array(
                    '_goft_wpjm_template'      => $example,
                    '_goft_wpjm_period'        => 'custom',
                    '_goft_wpjm_period_custom' => 15,
                    '_goft_wpjm_limit'         => 5,
                    '_goft_wpjm_cron'          => 'daily',
                ),
                );
                wp_insert_post( $post );
            }
            
            /**
             * Get the plugin URL.
             */
            public function plugin_url()
            {
                return untrailingslashit( plugins_url( '/', __FILE__ ) );
            }
        
        }
    }
    /**
     * Returns the main instance of 'Go Fetch Jobs'.
     *
     * @return GoFetch_Jobs instance.
     */
    function GoFetch_WPJM()
    {
        return GoFetch_WPJM::instance();
    }

}
