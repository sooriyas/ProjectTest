<?php

/**
 * Contains the all the core import functionality.
 *
 * @package GoFetchJobs/Importer
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}

/**
 * The core importer class.
 */
class GoFetch_WPJM_Importer
{
    /**
     * List of used taxonomies objects.
     *
     * @var object
     */
    protected static  $used_taxonomies ;
    /**
     * Property for forcing a feed to load.
     *
     * @var boolean
     */
    protected static  $goft_wpjm_force_feed = false ;
    /**
     * __construct.
     */
    public function __construct()
    {
        add_filter(
            'goft_wpjm_prepare_item',
            array( __CLASS__, 'get_item_logo' ),
            11,
            2
        );
        add_filter( 'goft_wpjm_format_description', array( __CLASS__, 'strip_tags' ), 10 );
    }
    
    /**
     * Set additional feed options.
     */
    public static function set_feed_options( $feed, $url )
    {
        $feed->set_timeout( 30 );
        // Force feed if the user asks it.
        if ( self::$goft_wpjm_force_feed ) {
            $feed->force_feed( true );
        }
    }
    
    /**
     * Get item logo using opengraph if item is flagged with 'try_og_logo'.
     *
     * @since 1.3.
     */
    public static function get_item_logo( $item, $params )
    {
        
        if ( !empty($item['logo']) || empty($params['logos']) ) {
            // Remove any existing logo in the item if the user doesn't want it.
            
            if ( empty($params['logos']) && empty($item['other']['scrape']['logo']) ) {
                
                if ( !empty($item['logo']) ) {
                    $item['logo'] = null;
                    unset( $item['logo'] );
                }
                
                
                if ( !empty($item['logo_html']) ) {
                    $item['logo_html'] = null;
                    unset( $item['logo_html'] );
                }
            
            }
            
            if ( !empty($item['logo']) && empty($item['logo_html']) ) {
                $item['logo_html'] = html( 'img', array(
                    'src'   => $item['logo'],
                    'class' => 'goft-og-image',
                ) );
            }
            return $item;
        }
        
        $og = self::load_open_graph( $item['link'] );
        
        if ( !empty($og) ) {
            $image = $og->image;
            
            if ( $image ) {
                $item['logo'] = $image;
                $item['logo_html'] = html( 'img', array(
                    'src'   => $image,
                    'class' => 'goft-og-image',
                ) );
            }
        
        }
        
        // Clear memory.
        $item['try_og_logo'] = null;
        unset( $item['try_og_logo'] );
        return $item;
    }
    
    /**
     * Iteratively imports RSS feeds considering pagination, if supported by the provider.
     */
    public static function import_feed( $orig_url, $params = array(), $cache = true )
    {
        global  $goft_wpjm_options ;
        $provider = array();
        // Fix URL's with double forward slashes like http://somedomain.com//some_page.
        $url = preg_replace( '/(?<!:)\\/\\//', '/', $orig_url );
        // Remove last '&' from the URL.
        $url = preg_replace( '/(&)$/is', '', $url );
        $url = esc_url_raw( trim( $url ) );
        if ( false === strpos( $url, 'https' ) ) {
            $url = set_url_scheme( wp_specialchars_decode( $url ), 'http' );
        }
        $provider_match = ( strpos( $url, '//api.' ) ? 'api.' : '' );
        if ( $provider_id = GoFetch_WPJM_RSS_Providers::find_provider_in_url( $url, $provider_match ) ) {
            $provider = GoFetch_WPJM_RSS_Providers::get_providers( $provider_id );
        }
        $pages = 1;
        $limit = 0;
        
        if ( !empty($provider['feed']['pagination']) ) {
            parse_str( $url, $query_string_parts );
            $limit_qarg = $provider['feed']['pagination']['params']['limit'];
            $page_qarg = $provider['feed']['pagination']['params']['page'];
            $max_results = $provider['feed']['pagination']['results'];
            $pagination_type = ( !empty($provider['feed']['pagination']['type']) ? $provider['feed']['pagination']['type'] : '' );
            // Limit passed as a parameter (not added through the feed URL).
            
            if ( !empty($params['limit']) ) {
                $limit = (int) $params['limit'];
                // Limit passed through the feed URL directly.
            } elseif ( !empty($query_string_parts[$limit_qarg]) ) {
                $limit = (int) $query_string_parts[$limit_qarg];
            }
            
            if ( $limit > $max_results ) {
                $pages = (int) ceil( $limit / max( 1, $max_results ) );
            }
        }
        
        // __LOG.
        $fetch_start_time = current_time( 'timestamp' );
        $vars = array(
            'context'  => 'GOFT :: FETCHING FEED',
            'orig_url' => $orig_url,
            'url'      => $url,
            'pages'    => $pages,
        );
        BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
        $results = array();
        for ( $i = 1 ;  $i <= $pages ;  $i++ ) {
            // Append the pagination and limit query args to the URL to paginate results.
            
            if ( $pages > 1 ) {
                $page_qarg_val = $i;
                // Use pagination based on offset.
                if ( 'offset' === $pagination_type && $i > 1 ) {
                    $page_qarg_val *= $max_results + 1;
                }
                $url = add_query_arg( array(
                    $page_qarg  => $page_qarg_val,
                    $limit_qarg => $max_results,
                ), $url );
            }
            
            $results_temp = self::_import_feed( $url, $provider_id, $provider );
            
            if ( 1 === $i ) {
                $results = $results_temp;
            } elseif ( !is_wp_error( $results_temp ) && !empty($results_temp['items']) ) {
                $new_items = $results_temp['items'];
                /*
                				$unique_items = wp_list_pluck( $results['items'], 'link' );
                
                				// Exclude duplicates when paginating.
                				$new_items = array_filter( $results_temp['items'], function( $item ) use ( $unique_items ) {
                					return ( ! in_array( $item['link'], $unique_items ) );
                				} );
                */
                $results['items'] = array_merge( $results['items'], $new_items );
            }
            
            // Clear memory.
            $results_temp = null;
        }
        if ( is_wp_error( $results ) || empty($results['items']) ) {
            return $results;
        }
        // For paginated feeds make sure the final items array count is not superior to user specified limit.
        if ( $pages > 1 && count( $results['items'] ) > $limit ) {
            array_splice( $results['items'], $limit );
        }
        // __LOG.
        $vars = array(
            'context'  => 'GOFT :: FEED FETCHED SUCCESFULLY!',
            'duration' => date( 'i:s', current_time( 'timestamp' ) - $fetch_start_time ),
            'results'  => ( !empty($results['items']) ? count( $results['items'] ) : 'No results!' ),
        );
        BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
        // If we're caching the items split the list in chunks to avoid DB errors.
        
        if ( $cache && !empty($results['items']) ) {
            $chunked_items = self::maybe_chunkify_list( $results['items'] );
            self::cache_feed_items( $chunked_items );
            // Clear memory.
            $chunked_items = null;
        }
        
        return $results;
    }
    
    /**
     * Imports RSS feed items from a given URL.
     */
    protected static function _import_feed( $url, $provider_id, &$provider = '' )
    {
        $import_callback = array( __CLASS__, 'fetch_feed_items' );
        
        if ( $provider_id && (0 === strpos( $provider_id, 'api.' ) || false !== strpos( $provider_id, '/api' )) ) {
            $provider['id'] = $provider_id;
            
            if ( $provider && !empty($provider['API']['callback']['fetch_feed']) ) {
                $feed = call_user_func( $provider['API']['callback']['fetch_feed'], $url, $provider );
                if ( is_wp_error( $feed ) ) {
                    return $feed;
                }
                $import_callback = $provider['API']['callback']['fetch_feed_items'];
            } else {
                return new WP_Error( 'unknown_api', __( 'Invalid/Unknown API feed ', 'gofetch-wpjm' ) );
            }
            
            $host = $provider['website'];
            $feed_title = $provider['description'];
            $feed_desc = $provider['description'];
            $feed_image_url = $provider['logo'];
            // Make sure we have an array of arrays.
            if ( !empty($feed) && empty($feed[0]) ) {
                $feed = array( $feed );
            }
        } else {
            $feed = self::fetch_feed( $url, $provider );
            if ( is_wp_error( $feed ) ) {
                return $feed;
            }
            $feed_title = $feed->get_title();
            $feed_desc = $feed->get_description();
            $feed_image_url = $feed->get_image_url();
            $parsed_url = wp_parse_url( $feed->get_permalink() );
            // If the host URL is empty on the feed try to locate it from the user feed URL.
            if ( empty($parsed_url['host']) ) {
                $parsed_url['host'] = GoFetch_WPJM_RSS_Providers::find_provider_in_url( $url );
            }
            
            if ( !empty($parsed_url['host']) ) {
                $provider_id = ( $provider_id ? $provider_id : str_replace( 'www.', '', $parsed_url['host'] ) );
                $host = $parsed_url['host'];
            } else {
                $provider_id = ( $provider_id ? $provider_id : 'unknown' );
                $host = __( 'Unknown', 'gofetch-wpjm' );
            }
            
            if ( !$provider ) {
                $provider = GoFetch_WPJM_RSS_Providers::get_providers( $provider_id );
            }
            $provider['id'] = $provider_id;
        }
        
        // Set provider data.
        $defaults = array(
            'id'          => $provider_id,
            'title'       => $feed_title,
            'website'     => $host,
            'description' => $feed_desc,
            'logo'        => '',
        );
        $provider = wp_parse_args( (array) $provider, $defaults );
        // If this is a multi-site child provider skip part of the base data from the parent and use the defaults.
        
        if ( !empty($provider['inherit']) ) {
            $provider['title'] = $defaults['title'];
            $provider['website'] = $defaults['website'];
            $provider['description'] = $defaults['description'];
        }
        
        // Try to get the provider logo through the feed or using Open Graph.
        
        if ( empty($provider['logo']) && ($logo = $feed_image_url) ) {
            $graph = self::load_open_graph( $provider['website'] );
            if ( $graph ) {
                $logo = $graph->image;
            }
            // Check if the provider logo is valid. Skip it if we get a 404.
            
            if ( !empty($logo) ) {
                $response = wp_remote_get( $logo );
                $http_code = wp_remote_retrieve_response_code( $response );
                if ( 404 !== $http_code ) {
                    $provider['logo'] = $logo;
                }
            }
        
        }
        
        $results = call_user_func(
            $import_callback,
            $feed,
            $url,
            $provider
        );
        // Clear memory.
        $feed = null;
        return $results;
    }
    
    /**
     * Fetch and return items from the RSS feed.
     */
    public static function fetch_feed_items( $feed, $url, $provider )
    {
        global  $goft_wpjm_options ;
        $new_items = $sample_item = array();
        // Get all the valid item tags for the providers.
        $valid_item_tags = GoFetch_WPJM_RSS_Providers::valid_item_tags();
        $valid_regexp_tags = GoFetch_WPJM_RSS_Providers::valid_regexp_tags();
        // Always set a default 'base' namespace with the valid item tags.
        $namespaces = self::get_namespaces_for_feed( $url );
        $namespaces['base'] = 'base';
        $items = $feed->get_items();
        $custom_tags = array();
        // __LOG.
        // Maybe log import info.
        $import_start_time = current_time( 'timestamp' );
        $vars = array(
            'context'  => 'GOFT :: STARTING RSS FEED IMPORT PROCESS',
            'provider' => $provider['id'],
        );
        BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
        // __END LOG.
        // Make sure we have an array of arrays.
        if ( !empty($items) && empty($items[0]) ) {
            $items = array( $items );
        }
        foreach ( (array) $items as $item ) {
            // Get the XML main meta data.
            $new_item = array();
            $image = '';
            $new_item['provider_id'] = $provider['id'];
            $new_item['title'] = wp_strip_all_tags( $item->get_title() );
            $new_item['link'] = esc_url_raw( html_entity_decode( $item->get_permalink() ) );
            $new_item['jobkey'] = md5( $new_item['link'] );
            $new_item['date'] = self::get_valid_date( $item );
            $new_item['description'] = self::format_description( $item->get_description() );
            
            if ( empty($new_item['logo']) ) {
                if ( $enclosure = $item->get_enclosure() ) {
                    $image = $enclosure->get_link();
                }
                
                if ( $image ) {
                    $new_item['logo'] = $image;
                    $new_item['logo_html'] = html( 'img', array(
                        'src'   => $image,
                        'class' => 'goft-og-image',
                    ) );
                }
            
            }
            
            if ( !empty($og->site_name) && empty($provider['name']) ) {
                $provider['name'] = $og->site_name;
            }
            // Find the item with the most attributes to use as sample.
            
            if ( count( array_keys( $new_item ) ) > count( array_keys( $sample_item ) ) ) {
                $sample_item = $new_item;
                $sample_item['description'] = wp_strip_all_tags( $item->get_description() );
                $sample_item['jobkey'] = null;
                unset( $sample_item['jobkey'] );
                $sample_item['link'] = null;
                unset( $sample_item['link'] );
            }
            
            $new_item = apply_filters( 'goft_wpjm_rss_item', $new_item, $provider );
            // __LOG.
            // Maybe log import info.
            $vars = array(
                'context'  => 'GOFT :: IMPORTING ITEM FROM RSS FEED',
                'new_item' => $new_item,
            );
            BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
            // __END LOG.
            $new_items[] = $new_item;
        }
        // __LOG.
        $vars = array(
            'context'  => 'GOFT :: FINISHED RSS FEED IMPORT PROCESS',
            'duration' => date( 'i:s', current_time( 'timestamp' ) - $import_start_time ),
        );
        BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
        // __END LOG.
        // Additional provider attributes.
        if ( empty($provider['name']) && !empty($provider['title']) ) {
            $provider['name'] = $provider['title'];
        }
        if ( empty($provider['name']) && !empty($provider['id']) ) {
            $provider['name'] = $provider['id'];
        }
        if ( empty($custom_tags) ) {
            $provider['custom_tags'] = $custom_tags;
        }
        // Clear memory.
        $items = $namespaces = $feed = null;
        // __LOG.
        // Maybe log import info.
        $vars = array(
            'context' => 'GOFT :: ITEMS COLLECTED FROM FEED',
            'items'   => count( $new_items ),
        );
        BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
        // __END LOG.
        return array(
            'provider'    => $provider,
            'items'       => $new_items,
            'sample_item' => $sample_item,
        );
    }
    
    /**
     * The public wrapper method for the import process.
     */
    public static function import( $items, $params )
    {
        global  $goft_wpjm_options, $wpdb ;
        define( 'WP_IMPORTING', true );
        
        if ( apply_filters( 'goft_wpjm_import_force_no_limits', true ) ) {
            ini_set( 'memory_limit', -1 );
            set_time_limit( 0 );
        }
        
        $import_start_time = current_time( 'timestamp' );
        // __LOG.
        // Maybe log import info.
        $vars = array(
            'context' => 'GOFT :: IMPORTING FEED',
            'params'  => $params,
        );
        BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
        
        if ( empty($items) ) {
            return array(
                'imported'    => 0,
                'duplicates'  => 0,
                'updated'     => 0,
                'in_rss_feed' => 0,
                'limit'       => 0,
            );
        } else {
            self::$used_taxonomies = get_object_taxonomies( GoFetch_WPJM()->parent_post_type, 'objects' );
            $items = apply_filters( 'goft_wpjm_import_items_before_filter', $items );
            $defaults = array(
                'post_type'        => GoFetch_WPJM()->parent_post_type,
                'post_author'      => 1,
                'tax_input'        => array(),
                'smart_tax_input'  => '',
                'meta'             => array(),
                'from_date'        => '',
                'to_date'          => '',
                'limit'            => '',
                'keywords'         => '',
                'keywords_exclude' => '',
                'import_images'    => true,
                'special'          => '',
            );
            $params = apply_filters( 'goft_wpjm_import_items_params', wp_parse_args( $params, $defaults ), $items );
            $results = self::filter_items( $items, array(
                'post_type' => $params['post_type'],
            ), $params );
            list( $unique_items, $excluded_items, $duplicate_items, $updated_items ) = array_values( $results );
            $items_process = array(
                'insert' => apply_filters( 'goft_wpjm_import_items_after_filter', $unique_items, $params ),
                'update' => $updated_items,
            );
            $stats = array(
                'insert' => 0,
                'update' => 0,
            );
            $post_items = array();
            // Bulk performance optimization.
            wp_defer_term_counting( true );
            wp_defer_comment_counting( true );
            $wpdb->query( 'SET autocommit = 0;' );
            //
            foreach ( $items_process as $operation => $_items ) {
                $params['operation'] = $operation;
                // Iterate through all the items in the list.
                foreach ( $_items as $item ) {
                    
                    if ( !empty($item['date']) ) {
                        if ( !empty($params['from_date']) ) {
                            
                            if ( 'insert' === $operation && date( 'Y-m-d', strtotime( $item['date'] ) ) < date( 'Y-m-d', strtotime( $params['from_date'] ) ) ) {
                                $excluded_items[] = $item;
                                continue;
                            }
                        
                        }
                        if ( 'insert' === $operation && !empty($params['to_date']) ) {
                            
                            if ( date( 'Y-m-d', strtotime( $item['date'] ) ) > date( 'Y-m-d', strtotime( $params['to_date'] ) ) ) {
                                $excluded_items[] = $item;
                                continue;
                            }
                        
                        }
                    }
                    
                    
                    if ( !apply_filters(
                        'goft_wpjm_import_rss_item',
                        true,
                        $item,
                        $params
                    ) ) {
                        $excluded_items[] = $item;
                        continue;
                    }
                    
                    
                    if ( self::_import( $item, $params ) ) {
                        $stats[$operation]++;
                    } else {
                        // Failed to insert.
                        $excluded_items[] = $item;
                    }
                
                }
            }
            // Restore. Deferred database process are commmited at this time.
            $wpdb->query( 'COMMIT;' );
            $wpdb->query( 'SET autocommit = 1;' );
            wp_defer_term_counting( false );
            wp_defer_comment_counting( false );
            //
            $results = array(
                'in_rss_feed' => count( $items ),
                'imported'    => $stats['insert'],
                'limit'       => ( !empty($params['limit']) && $params['limit'] < abs( count( $items ) ) ? abs( count( $items ) - $params['limit'] ) : 0 ),
                'duplicates'  => count( $duplicate_items ),
                'updated'     => $stats['update'],
                'excluded'    => count( $excluded_items ),
                'duration'    => current_time( 'timestamp' ) - $import_start_time,
            );
        }
        
        // __LOG.
        // Maybe log import info.
        $vars = array(
            'context'  => 'GOFT :: FINISHED IMPORTING FEED',
            'results'  => $results,
            'duration' => date( 'i:s', current_time( 'timestamp' ) - $import_start_time ),
        );
        BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
        // Clear memory.
        $items_process = $_items = null;
        return $results;
    }
    
    // __Private.
    /**
     * The main import method.
     * Creates a new post for each imported job and adds any related meta data.
     */
    private static function _import( $item, $params )
    {
        global  $goft_wpjm_options ;
        
        if ( !empty($params['test']) ) {
            // __LOG.
            $vars = array(
                'context' => 'GOFT :: IMPORT TESTING MODE - SKIPPING INSERT',
                'item'    => $item,
            );
            BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
            // __END LOG.
            return true;
        }
        
        $original_item = $item;
        $params = apply_filters( 'goft_wpjm_import_item_params', $params, $item );
        $item = apply_filters( 'goft_wpjm_prepare_item', $original_item, $params );
        do_action( 'goft_wpjm_before_insert_job', $item, $params );
        $meta = array();
        // Insert the main post with the core data taken from the feed item.
        
        if ( $post_id = self::_insert_post( $item, $params ) ) {
            // Prepare meta before adding it to the post.
            $meta = self::_prepare_item_meta(
                $item,
                $original_item,
                $params['meta'],
                $post_id,
                $params
            );
            // Add any existing meta to the new post.
            self::_add_meta( $post_id, $meta );
            do_action(
                'goft_wpjm_after_insert_job',
                $post_id,
                $item,
                $params
            );
            return true;
        }
        
        do_action(
            'goft_wpjm_insert_job_failed',
            $item,
            $params,
            $meta
        );
        return false;
    }
    
    /**
     * Insert a post given an item and related parameters.
     */
    private static function _insert_post( $item, $params )
    {
        global  $goft_wpjm_options ;
        $post_content = apply_filters(
            'goft_wpjm_post_content',
            wp_kses_post( $item['description'] ),
            $item,
            $params
        );
        // Use smart taxonomies terms assignment.
        
        if ( !empty($params['smart_tax_input']) ) {
            $content = $item['title'] . ' ' . $post_content;
            if ( method_exists( 'GoFetch_WPJM_Premium_Pro_Features', 'smart_tax_terms_input' ) ) {
                $params['tax_input'] = GoFetch_WPJM_Premium_Pro_Features::smart_tax_terms_input(
                    $params['tax_input'],
                    $item,
                    $content,
                    self::$used_taxonomies,
                    $params['smart_tax_input']
                );
            }
        }
        
        $post_arr = array(
            'post_title'   => $item['title'],
            'post_content' => $post_content,
            'post_status'  => apply_filters( 'goft_wpjm_job_status', $goft_wpjm_options->post_status ),
            'post_type'    => $params['post_type'],
            'post_author'  => (int) $params['post_author'],
            'post_date'    => apply_filters( 'goft_wpjm_job_date', date( 'Y-m-d', strtotime( $item['date'] ) ) ),
        );
        // Check if the post should be updated.
        if ( !empty($params['operation']) && 'update' === $params['operation'] ) {
            
            if ( !empty($item['post_id']) ) {
                // Post will be updated.
                $post_arr['ID'] = $item['post_id'];
            } else {
                // __LOG.
                $vars = array(
                    'context' => 'GOFT :: WARNING - POST NEEDS UPDATING BUT COULD NOT FIND DB ITEM POST ID',
                    'item'    => $item,
                );
                BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
                // __END LOG.
                return;
            }
        
        }
        $post_arr = apply_filters( 'goft_wpjm_post_insert', $post_arr, $item );
        $post_id = wp_insert_post( $post_arr );
        if ( $post_id && !empty($params['tax_input']) ) {
            self::_add_taxonomies( $post_id, $params['tax_input'] );
        }
        self::update_imported_jobs_cache( $post_id, $post_arr, $item );
        return $post_id;
    }
    
    /**
     * Updates/caches the imported jobs list to speed up import filtering.
     */
    protected static function update_imported_jobs_cache( $post_id, $post_arr, $item )
    {
        
        if ( !($external_posts = get_transient( 'goft_wpjm_imported_jobs' )) ) {
            set_transient( 'goft_wpjm_imported_jobs_cached', current_time( 'timestamp' ) );
            $external_posts = array();
        }
        
        $external_posts[$item['jobkey']] = array(
            'post_id'       => $post_id,
            'title'         => wp_strip_all_tags( $post_arr['post_title'] ),
            'date'          => date( 'Ymd', strtotime( $post_arr['post_date'] ) ),
            'external_link' => $item['link'],
        );
        set_transient( 'goft_wpjm_imported_jobs', $external_posts, DAY_IN_SECONDS );
    }
    
    /**
     * Adds meta data to a given post ID.
     */
    private static function _add_taxonomies( $post_id, $tax_input )
    {
        foreach ( $tax_input as $tax => $terms ) {
            wp_set_object_terms( $post_id, $terms, $tax );
        }
    }
    
    /**
     * Adds meta data to a given post ID.
     */
    private static function _add_meta( $post_id, $meta )
    {
        foreach ( $meta as $meta_key => $meta_value ) {
            if ( apply_filters(
                'goft_wpjm_update_meta',
                false,
                $meta_key,
                $meta_value,
                $post_id
            ) ) {
                continue;
            }
            update_post_meta( $post_id, $meta_key, $meta_value );
        }
    }
    
    /**
     * Prepares all the meta for a given item before it is saved in the database.
     *
     * @uses apply_filters() Calls 'goft_wpjm_item_meta_value'.
     */
    private static function _prepare_item_meta(
        $item,
        $original_item,
        $meta,
        $post_id,
        $params
    )
    {
        // Add the feed URL to the source data.
        $params['source']['feed_url'] = $params['rss_feed_import'];
        $meta['_goft_wpjm_is_external'] = 1;
        $meta['_goft_external_link'] = $item['link'];
        $meta['_goft_jobkey'] = $item['jobkey'];
        $meta['_goft_source_data'] = $params['source'];
        if ( !empty($item['other']) ) {
            $meta['_goft_wpjm_other'] = $item['other'];
        }
        // If we have modified item store it to use it later for comparisons.
        if ( $item !== $original_item ) {
            $meta['_goft_wpjm_original_item'] = $original_item;
        }
        // Get the custom field mappings.
        require_once 'wpjm/admin/class-gofetch-wpjm-importer.php';
        $cust_field_mappings = GoFetch_WPJM_Specific_Import::meta_mappings();
        $final_meta = array();
        foreach ( $meta as $meta_key => $meta_value ) {
            // If any of the custom fields is found on items being imported get the value and override the defaults.
            
            if ( isset( $cust_field_mappings[$meta_key] ) ) {
                $known_field = $cust_field_mappings[$meta_key];
                if ( !empty($item[$known_field]) ) {
                    $meta_value = sanitize_text_field( $item[$known_field] );
                }
            }
            
            /**
             * @todo: maybe use a placeholder featured image and use a filter to override the featured image SRC image.
             */
            if ( !apply_filters(
                'goft_wpjm_item_skip_meta',
                false,
                $meta_value,
                $meta_key,
                $item,
                $post_id,
                $params
            ) ) {
                $final_meta[$meta_key] = apply_filters(
                    'goft_wpjm_item_meta_value',
                    $meta_value,
                    $meta_key,
                    $item,
                    $post_id,
                    $params
                );
            }
        }
        return $final_meta;
    }
    
    /**
     * Filter the items and return a list of results.
     */
    private static function filter_items( $items, $args = array(), $params = array() )
    {
        global  $goft_wpjm_options ;
        $new_posts = array();
        $defaults = array(
            'limit'            => 0,
            'keywords'         => '',
            'keywords_exclude' => '',
        );
        $params = wp_parse_args( $params, $defaults );
        $limit = $params['limit'];
        $keywords = $params['keywords'];
        $keywords_exclude = $params['keywords_exclude'];
        $now = current_time( 'timestamp' );
        $cache_time = get_transient( 'goft_wpjm_imported_jobs_cached' );
        $datediff = $now - $cache_time;
        $days = floor( $datediff / (60 * 60 * 24) );
        // Clear the cache every 5 days to keep it fresh.
        if ( $days >= apply_filters( 'goft_wpjm_imported_jobs_cache', 5 ) ) {
            delete_transient( 'goft_wpjm_imported_jobs' );
        }
        
        if ( !($external_posts = get_transient( 'goft_wpjm_imported_jobs' )) ) {
            $defaults = array(
                'post_type'        => 'post',
                'post_status'      => array( 'publish', 'pending', 'draft' ),
                'meta_key'         => '_goft_wpjm_is_external',
                'suppress_filters' => true,
                'nopaging'         => true,
            );
            $args = apply_filters( 'goft_wpjm_filter_items_query_args', wp_parse_args( $args, $defaults ) );
            $results = new WP_Query( $args );
            
            if ( $results->posts ) {
                // Get existing external posts.
                foreach ( $results->posts as $post ) {
                    $external_link = get_post_meta( $post->ID, '_goft_external_link', true );
                    if ( !($jobkey = get_post_meta( $post->ID, '_goft_jobkey', true )) ) {
                        $jobkey = md5( $external_link );
                    }
                    // Make sure we grab the original data to check for updates.
                    $original_item = get_post_meta( $post->ID, '_goft_wpjm_original_item', true );
                    
                    if ( !empty($original_item) ) {
                        $original_item_def = array(
                            'title'       => '',
                            'description' => '',
                            'date'        => '',
                        );
                        $original_item = wp_parse_args( $original_item, $original_item_def );
                        $title = $original_item['title'];
                        $description = $original_item['description'];
                        $date = $original_item['date'];
                    } else {
                        $title = $post->post_title;
                        $description = $post->post_content;
                        $date = $post->post_date;
                    }
                    
                    $external_posts[$jobkey] = array(
                        'post_id'       => $post->ID,
                        'title'         => wp_strip_all_tags( $title ),
                        'description'   => wp_strip_all_tags( $description ),
                        'date'          => date( 'Ymd', strtotime( $date ) ),
                        'external_link' => $external_link,
                        'from_db'       => true,
                    );
                    $external_posts_cache[$jobkey] = $external_posts[$jobkey];
                    // Don't cache the post content/description.
                    $external_posts_cache[$jobkey]['description'] = null;
                }
                set_transient( 'goft_wpjm_imported_jobs', $external_posts_cache, DAY_IN_SECONDS );
                set_transient( 'goft_wpjm_imported_jobs_cached', current_time( 'timestamp' ) );
                $external_posts_cache = null;
            } else {
                $external_posts = array();
            }
        
        }
        
        $unique_items = $updated_items = $duplicate_items = $excluded_items = array();
        
        if ( $keywords ) {
            $keywords = explode( ',', $keywords );
            $keywords = stripslashes_deep( $keywords );
        }
        
        
        if ( $keywords_exclude ) {
            $keywords_exclude = explode( ',', $keywords_exclude );
            $keywords_exclude = stripslashes_deep( $keywords_exclude );
        }
        
        $compare_fields = array( 'date', 'description', 'title' );
        // @todo: check if the provider item data is being added to the list of items (it shouldn't!').
        foreach ( $items as $item ) {
            $content = '';
            if ( 'title' !== $goft_wpjm_options->keyword_matching ) {
                $content .= $item['description'];
            }
            if ( 'content' !== $goft_wpjm_options->keyword_matching ) {
                $content .= $item['title'];
            }
            $jobkey = $item['jobkey'];
            $is_feed_dup = false;
            $new_and_db_posts = array_merge( $external_posts, $new_posts );
            // Look for existing jobs with the same title. Consider it duplicate and override the jobkey if there's a title match for the same provider.
            
            if ( $dup_items = wp_list_filter( $new_and_db_posts, array(
                'title' => wp_strip_all_tags( $item['title'] ),
            ) ) ) {
                $curr_item_host = parse_url( $item['link'], PHP_URL_HOST );
                foreach ( $dup_items as $dup_item ) {
                    $host = parse_url( $dup_item['external_link'], PHP_URL_HOST );
                    
                    if ( $curr_item_host === $host ) {
                        $is_feed_dup = true;
                        break;
                    }
                
                }
                if ( $is_feed_dup ) {
                    $new_posts[$jobkey] = $dup_item;
                }
            }
            
            // Check for unique external posts using the unique job key.
            // Consider cases where the jobkey can be different but the job title being the same - if from same provider, consider it duplicate.
            
            if ( !isset( $external_posts[$jobkey] ) && !$is_feed_dup ) {
                // Match keywords.
                
                if ( ($keywords || $keywords_exclude) && $content ) {
                    $exclude = false;
                    // Positive keywords.
                    if ( $keywords && !GoFetch_WPJM_Helper::match_keywords( $content, $keywords ) ) {
                        $exclude = true;
                    }
                    // Negative keywords.
                    if ( $keywords_exclude && GoFetch_WPJM_Helper::match_keywords( $content, $keywords_exclude ) ) {
                        $exclude = true;
                    }
                    // Allow overriding the keywords matching result.
                    $exclude = apply_filters(
                        'goft_wpjm_exclude_item',
                        $exclude,
                        $item,
                        $keywords,
                        $keywords_exclude
                    );
                    
                    if ( $exclude ) {
                        $excluded_items[] = $item;
                        continue;
                    } else {
                        $unique_items[] = $item;
                    }
                
                } else {
                    $unique_items[] = $item;
                }
                
                // Build the list of new items to check for duplicates.
                $new_posts[$jobkey] = array(
                    'title'         => $item['title'],
                    'external_link' => $item['link'],
                );
                // Limit the results if requested by the user.
                if ( $limit && count( $unique_items ) >= $limit ) {
                    break;
                }
            } else {
                $update = false;
                if ( 'update' === apply_filters( 'goft_wpjm_rss_item_check_updates', $goft_wpjm_options->rss_item_check_updates, $item ) ) {
                    foreach ( $compare_fields as $field ) {
                        $new_item_value = wp_strip_all_tags( $item[$field] );
                        $db_item_value = '';
                        if ( !empty($external_posts[$jobkey][$field]) ) {
                            $db_item_value = $external_posts[$jobkey][$field];
                        }
                        
                        if ( 'date' === $field ) {
                            $new_item_value = date( 'Ymd', strtotime( $item['date'] ) );
                        } elseif ( 'description' === $field ) {
                            // If we're iterating through the '$external_posts' cached list the content will be empty.
                            
                            if ( empty($external_posts[$jobkey]['description']) ) {
                                $post_id = $external_posts[$jobkey]['post_id'];
                                // Make sure we grab the original data.
                                $original_item = get_post_meta( $post_id, '_goft_wpjm_original_item', true );
                                if ( !empty($original_item['description']) ) {
                                    $db_item_value = $original_item['description'];
                                }
                            }
                        
                        }
                        
                        
                        if ( $new_item_value !== $db_item_value ) {
                            // __LOG.
                            // Maybe log import info.
                            $vars = array(
                                'context'   => 'GOFT :: ITEM IN RSS FEED WILL BE UPDATED',
                                'field'     => $field,
                                'item'      => $item,
                                'db_value'  => $db_item_value,
                                'rss_value' => $new_item_value,
                            );
                            BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
                            // __END LOG.
                            // Skip immediately to speed up the comparison process.
                            $update = true;
                            break;
                        }
                    
                    }
                }
                if ( !empty($external_posts[$jobkey]['post_id']) ) {
                    $item['post_id'] = $external_posts[$jobkey]['post_id'];
                }
                
                if ( $update ) {
                    $updated_items[] = $item;
                } else {
                    $duplicate_items[] = $item;
                }
            
            }
        
        }
        // __LOG.
        $vars = array(
            'context'         => 'GOFT :: FILTER ITEMS COUNTS',
            'unique_items'    => count( $unique_items ),
            'excluded_items'  => count( $excluded_items ),
            'duplicate_items' => count( $duplicate_items ),
            'updated_items'   => count( $updated_items ),
        );
        BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
        // __END LOG.
        $results = array(
            'unique_items'    => $unique_items,
            'excluded_items'  => $excluded_items,
            'duplicate_items' => $duplicate_items,
            'updated_items'   => $updated_items,
        );
        wp_reset_postdata();
        return $results;
    }
    
    /**
     * Mirors WP 'fetch_feed()' but uses raw data instead on an URL.
     *
     * @since 1.3.2
     */
    protected static function fetch_feed_raw_data( $data, $url )
    {
        if ( !class_exists( 'SimplePie', false ) ) {
            require_once ABSPATH . WPINC . '/class-simplepie.php';
        }
        require_once ABSPATH . WPINC . '/class-wp-feed-cache.php';
        require_once ABSPATH . WPINC . '/class-wp-feed-cache-transient.php';
        require_once ABSPATH . WPINC . '/class-wp-simplepie-file.php';
        require_once ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php';
        $feed = new SimplePie();
        $feed->set_sanitize_class( 'WP_SimplePie_Sanitize_KSES' );
        // We must manually overwrite $feed->sanitize because SimplePie's
        // constructor sets it before we have a chance to set the sanitization class
        $feed->sanitize = new WP_SimplePie_Sanitize_KSES();
        $feed->set_cache_class( 'WP_Feed_Cache' );
        $feed->set_file_class( 'WP_SimplePie_File' );
        $feed->set_raw_data( $data );
        /** This filter is documented in wp-includes/class-wp-feed-cache-transient.php */
        $feed->set_cache_duration( apply_filters( 'wp_feed_cache_transient_lifetime', 12 * HOUR_IN_SECONDS, $url ) );
        /**
         * Fires just before processing the SimplePie feed object.
         *
         * @since 3.0.0
         *
         * @param object &$feed SimplePie feed object, passed by reference.
         * @param mixed  $url   URL of feed to retrieve. If an array of URLs, the feeds are merged.
         */
        do_action_ref_array( 'wp_feed_options', array( &$feed, $url ) );
        $feed->init();
        $feed->set_output_encoding( get_option( 'blog_charset' ) );
        if ( $feed->error() ) {
            return new WP_Error( 'simplepie-error', $feed->error() );
        }
        return $feed;
    }
    
    /**
     * Defaults to 'fetch_feed()' to load the feed but provides fallback fetch feed alternatives in case of errors.
     *
     * @since 1.3.2
     */
    public static function fetch_feed( $url, $provider )
    {
        global  $goft_wpjm_options ;
        add_action(
            'wp_feed_options',
            array( __CLASS__, 'set_feed_options' ),
            10,
            2
        );
        $feed = fetch_feed( $url );
        $valid_feed = true;
        if ( is_wp_error( $feed ) ) {
            
            if ( apply_filters( 'goft_wpjm_fetch_feed_simplexml', false !== stripos( $feed->get_error_message(), 'feed could not be found at' ) ) ) {
                $context = stream_context_create( array(
                    'http' => array(
                    'header' => 'Accept: application/xml',
                ),
                ) );
                $xml = file_get_contents( $url, false, $context );
                $feed = self::fetch_feed_raw_data( $xml, $url );
            } elseif ( apply_filters( 'goft_wpjm_fetch_feed_crossorigin', $goft_wpjm_options->use_cors_proxy && is_wp_error( $feed ) && false !== strpos( $url, 'https' ) ) ) {
                // Try again with CORS proxy.
                $url = 'http://crossorigin.me/' . $url;
                // Use 'file_get_contents()' to check for error and avoiding 'fetch_feed' to hang.
                $context = stream_context_create( array(
                    'http' => array(
                    'header' => 'Accept: application/xml\\r\\nOrigin: ' . home_url(),
                ),
                ) );
                $valid_feed = file_get_contents( $url, false, $context );
                if ( $valid_feed ) {
                    $feed = fetch_feed( $url );
                }
            }
        
        }
        
        if ( apply_filters( 'goft_wpjm_fetch_feed_force', is_wp_error( $feed ) && $valid_feed ) ) {
            self::$goft_wpjm_force_feed = true;
            $feed = fetch_feed( $url );
        }
        
        remove_action(
            'wp_feed_options',
            array( __CLASS__, 'set_feed_options' ),
            10,
            2
        );
        return $feed;
    }
    
    /**
     * Split the list in chunks for a given array count.
     */
    protected static function maybe_chunkify_list( $list, $max = 10 )
    {
        if ( count( $list ) <= $max ) {
            return $list;
        }
        // Separate the items list in chunks to avoid DB errors with big RSS feeds.
        return array_chunk( $list, $max );
    }
    
    /**
     * Cache the RSS feed in the database.
     */
    protected static function cache_feed_items( $items, $expiration = 3600 )
    {
        global  $_wp_using_ext_object_cache, $goft_wpjm_options ;
        // Temporarily turn off the object cache while we deal with transients since
        // some caching plugins like W3 Total Cache conflicts with our work.
        $_wp_using_ext_object_cache_previous = $_wp_using_ext_object_cache;
        $_wp_using_ext_object_cache = false;
        // If items are not separated in chunks make sure we have an array of arrays.
        
        if ( empty($items[0][0]) ) {
            $chunks[] = $items;
        } else {
            $chunks = $items;
        }
        
        $skip_chunks = false;
        foreach ( $chunks as $key => $chunk ) {
            delete_transient( "_goft-rss-feed-{$key}" );
            $result = set_transient( "_goft-rss-feed-{$key}", $chunk, $expiration );
            
            if ( !$result ) {
                $skip_chunks = true;
                break;
            }
        
        }
        
        if ( !$skip_chunks ) {
            set_transient( '_goft-rss-feed-chunks', count( $chunks ), $expiration );
        } else {
            delete_transient( '_goft-rss-feed-chunks' );
            // __LOG.
            $vars = array(
                'context' => 'GOFT :: SITE DOES NOT SUPPORT TRANSIENTS! SKIPPED!',
            );
            BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
            // __END LOG.
        }
        
        // Clear memory.
        $chunks = null;
        // Restore the caching values.
        $_wp_using_ext_object_cache = $_wp_using_ext_object_cache_previous;
    }
    
    // __Helpers.
    /**
     * Retrieves external links considering custom user arguments.
     */
    public static function add_query_args( $params, $link )
    {
        if ( empty($params['source']['args']) && empty($params['args']) ) {
            return add_query_arg( apply_filters( 'goft_wpjm_external_link_qargs', array(), $params ), $link );
        }
        $args = ( !empty($params['source']['args']) ? $params['source']['args'] : $params['args'] );
        $qargs = array();
        $query_args = explode( ',', $args );
        foreach ( $query_args as $arg ) {
            $temp_qargs = explode( '=', $arg );
            $qargs = array_merge( $qargs, array(
                trim( $temp_qargs[0] ) => trim( $temp_qargs[1] ),
            ) );
        }
        return add_query_arg( apply_filters( 'goft_wpjm_external_link_qargs', $qargs, $params ), $link );
    }
    
    /**
     * Retrieves a pre-set list of key/value Open Graph tags/values from a given URL.
     */
    private static function load_open_graph( $url )
    {
        return OpenGraph::fetch( $url );
    }
    
    /**
     * Retrieves parts from an item text using regex patterns.
     */
    private static function get_item_regex_parts( $text, $patterns )
    {
        $parts = array();
        foreach ( (array) $patterns as $key => $pattern ) {
            preg_match( $pattern, html_entity_decode( $text ), $matches );
            end( $matches );
            $last_index = key( $matches );
            // Skip anything longer then 50 chars as it's probably fetching wrong data.
            if ( !empty($matches[$last_index]) && strlen( trim( $matches[$last_index] ) ) < 50 ) {
                $parts[$key] = trim( $matches[$last_index] );
            }
        }
        return $parts;
    }
    
    /**
     * Try to retrieve all the namespaces within an RSS feed.
     */
    private static function get_namespaces_for_feed( $url, $convert = false )
    {
        $namespaces = array();
        $feed = @file_get_contents( $url );
        
        if ( $convert && function_exists( 'iconv' ) ) {
            // Ignore errors with some UTF-8 feed.
            $feed = iconv( 'UTF-8', 'UTF-8//IGNORE', $feed );
        } elseif ( !function_exists( 'iconv' ) ) {
            // __LOG.
            $fetch_start_time = current_time( 'timestamp' );
            $vars = array(
                'context' => 'GOFT :: GET NAMESPACES SKIPPED :: ICONV() NOT INSTALLED',
                'url'     => $url,
            );
            BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
        }
        
        try {
            libxml_use_internal_errors( true );
            $xml = new SimpleXmlElement( $feed );
            $feed = null;
            if ( empty($xml->channel->item) ) {
                return $namespaces;
            }
            foreach ( $xml->channel->item as $entry ) {
                $curr_namespaces = $entry->getNameSpaces( true );
                $namespaces = array_merge( $namespaces, $curr_namespaces );
            }
            libxml_clear_errors();
        } catch ( Exception $e ) {
            if ( !$convert ) {
                self::get_namespaces_for_feed( $url, true );
            }
        }
        // Clear memory.
        $feed = $xml = null;
        return $namespaces;
    }
    
    /**
     * Imports an image to the DB.
     */
    private static function sideload_import_image( $url, $post_id )
    {
        $image = media_sideload_image( $url, $post_id );
        return $image;
    }
    
    /**
     * Try to retrieve a valid formatted date from the feed item.
     *
     * @since 1.3.1
     */
    public static function get_valid_date( $item, $type = 'rss' )
    {
        
        if ( 'rss' === $type ) {
            $date = $item->get_date( 'Y-m-d' );
            // If the date is empty try to get the data directly checking the most common date tags.
            if ( !$date ) {
                foreach ( array( 'pubdate', 'date' ) as $tag ) {
                    $date = $item->get_item_tags( '', $tag );
                    
                    if ( !empty($date[0]['data']) ) {
                        $date = date( 'Y-m-d', strtotime( html_entity_decode( $date[0]['data'] ) ) );
                        break;
                    }
                
                }
            }
        } else {
            $date = $item;
        }
        
        // Automatically default invalid dates like '1970-01-01' to the current date.
        if ( strtotime( $date ) < strtotime( '2000-01-01' ) ) {
            $date = current_time( 'Y-m-d' );
        }
        return wp_strip_all_tags( $date );
    }
    
    /**
     * Formats a given RSS feed description.
     *
     * @since 1.3
     */
    public static function format_description( $description )
    {
        $formatted_description = wpautop( $description );
        return apply_filters( 'goft_wpjm_format_description', trim( $formatted_description ), $description );
    }
    
    /**
     * Strips tags from text.
     */
    public static function strip_tags( $text )
    {
        $allowed_tags = wp_kses_allowed_html( 'post' );
        $allowed_tags = apply_filters( 'goft_wpjm_allowed_tags', $allowed_tags );
        $text = wp_kses( $text, $allowed_tags );
        // @todo: make sure links are not stripped of the 'href'.
        // Remove all attributes from all tags.
        return preg_replace( '/<([a-z][a-z0-9]*)[^>]*?(\\/?)>/i', '<$1$2>', $text );
    }

}
new GoFetch_WPJM_Importer();