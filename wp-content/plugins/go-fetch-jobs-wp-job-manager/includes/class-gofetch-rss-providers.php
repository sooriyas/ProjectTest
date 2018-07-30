<?php

/**
 * Sets up the write panels used by the schedules (custom post types).
 *
 * @package GoFetch/Admin/Providers
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly
}

/**
 * Schedules meta boxes base class.
 */
class GoFetch_WPJM_RSS_Providers
{
    /**
     * Retrieves a list of tags that the RSS Feed loader can import.
     *
     * Note: adding more tags trough the filter requires having a related meta field for later storing the data.
     *
     * @see GoFetch_WPJM_Specific_Import::meta_mappings()
     */
    public static function valid_item_tags()
    {
        $fields = array(
            'location',
            'geolocation',
            'latitude',
            'longitude',
            'company',
            'logo'
        );
        return apply_filters( 'goft_wpjm_providers_valid_item_tags', $fields );
    }
    
    /**
     * Similar to the 'valid_item_tags()' method but used to retrieve a list of tags found trough regular expressions.
     */
    public static function valid_regexp_tags()
    {
        $patterns = array(
            'location' => array(
            // e.g: <p>Location: London</p> OR Location : London <br/>
            '/Location.*?:(.*?)<.*?>/is',
            // e.g: <strong>Headquarters:</strong> New York, NY <br />
            '/Headquarters.*?:<.*?>(.*?)<.*?>/is',
            // e.g: <b>Location: </b><br/> San Francisco <br/>
            '/Location.*?<.*?>(.*?)<.*?>/is',
        ),
            'company'  => array(
            // e.g: Advertiser : Google <br/>
            '/Advertiser.*?:(.*?)<.*?>/is',
            // e.g: <b>Company: </b><br/> Google <br/>
            '/Company.*?<.*?>(.*?)<.*?>/is',
            // e.g: <p>Location: London</p> OR Location : London <br/>
            '/Company.*?:(.*?)<.*?>/is',
            // e.g: <b>Posted by: </b> Google </p>
            '/Posted by.*?<.*?>(.*?)<.*?>/is',
        ),
            'salary'   => array(
            // e.g: Salary : £40,000 - £55,000 per year
            '/Salary.*?:(.*?)$/is',
            // e.g: <p>Salary/Rate: 50.000 - 80.000</p>
            '/Salary\\/Rate.*?:(.*?)<.*?>/is',
        ),
        );
        return apply_filters( 'goft_wpjm_providers_valid_regexp_tags', $patterns );
    }
    
    /**
     * Retrieves a list of providers and their details.
     *
     * Weight: Higher is better.
     */
    public static function get_providers( $provider = '' )
    {
        $providers = array(
            'freelancewritinggigs.com'    => array(
            'website'     => 'http://www.freelancewritinggigs.com/',
            'logo'        => 'https://jobmob.co.il/images/articles/freelance-marketplaces/freelancewritinggigs-freelance-marketplace-logo.png',
            'description' => 'Freelance Writing Job Board',
            'feed'        => array(
            'base_url'     => 'http://www.freelancewritinggigs.com/?feed=job_feed',
            'search_url'   => 'http://www.freelancewritinggigs.com/freelance-writing-job-ads/',
            'example'      => 'http://www.freelancewritinggigs.com/?feed=job_feed&job_categories=blogging-jobs',
            'sample'       => 'http://www.freelancewritinggigs.com/?feed=job_feed&job_types=contract%2Cfreelance&[etc...]',
            'meta'         => array( 'company', 'location' ),
            'tag_mappings' => array(
            'type' => 'job_type',
        ),
            'query_args'   => array(
            'keyword'  => array(
            'search_keywords' => '',
        ),
            'location' => array(
            'search_location' => '',
        ),
        ),
            'default'      => true,
        ),
            'special'     => array(
            'scrape' => array(
            'description' => array(
            'nicename' => __( 'Job Description', 'gofetch-wpjm' ),
            'query'    => '//div[@class="job_description"]',
        ),
            'company'     => array(
            'nicename' => __( 'Company', 'gofetch-wpjm' ),
            'query'    => '//div[@class="company"]//strong[@itemprop="name"]',
        ),
            'location'    => array(
            'nicename' => __( 'Location', 'gofetch-wpjm' ),
            'query'    => '//li[@itemprop="jobLocation"]',
        ),
            'logo'        => array(
            'nicename' => __( 'Logo', 'gofetch-wpjm' ),
            'query'    => '//div[@class="company"]//img[@class="company_logo"]/@src',
        ),
        ),
        ),
            'category'    => __( 'Blogging', 'gofetch-wpjm' ),
            'weight'      => 7,
        ),
            'jobs.wordpress.net'          => array(
            'website'     => 'http://jobs.wordpress.net/',
            'logo'        => 'https://s.w.org/about/images/logos/wordpress-logo-notext-rgb.png',
            'description' => 'WordPress related Job Postings',
            'feed'        => array(
            'base_url'   => 'http://jobs.wordpress.net/feed/',
            'search_url' => 'http://jobs.wordpress.net/?s=',
            'example'    => 'http://jobs.wordpress.net/search/design/feed/rss2/',
            'sample'     => 'http://jobs.wordpress.net/search/design/feed/rss2/',
            'query_args' => array(
            'keyword' => array(
            's' => '',
        ),
        ),
            'examples'   => array(
            __( 'Latest Jobs', 'gofetch-wpjm' )                    => 'http://jobs.wordpress.net/feed/',
            __( 'Latest Design Jobs', 'gofetch-wpjm' )             => 'http://jobs.wordpress.net/job_category/design/feed/',
            __( 'Latest Plugin Development Jobs', 'gofetch-wpjm' ) => 'http://jobs.wordpress.net/job_category/plugin-development/feed/',
        ),
        ),
            'special'     => array(
            'scrape' => array(
            'description' => array(
            'nicename' => __( 'Job Description', 'gofetch-wpjm' ),
            'query'    => '//div[@class="entry-content"]',
        ),
            'company'     => array(
            'nicename' => __( 'Company', 'gofetch-wpjm' ),
            'query'    => '//div[contains(@class,"job-meta")]//dl[@class="job-company"]//dd',
        ),
            'location'    => array(
            'nicename' => __( 'Location', 'gofetch-wpjm' ),
            'query'    => '//div[contains(@class,"job-meta")]//dl[@class="job-location"]//dd',
        ),
            'logo'        => array(
            'nicename' => __( 'Logo', 'gofetch-wpjm' ),
            'query'    => '//div[@class="company"]//img[@class="company_logo"]/@src',
        ),
        ),
        ),
            'weight'      => 8,
            'category'    => __( 'WordPress', 'gofetch-wpjm' ),
        ),
            'jobs.theguardian.com'        => array(
            'website'     => 'https://jobs.theguardian.com/',
            'logo'        => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0e/The_Guardian.svg/2000px-The_Guardian.svg.png',
            'description' => 'Great jobs on the Guardian Jobs site',
            'feed'        => array(
            'base_url'        => 'https://jobs.theguardian.com/jobsrss/',
            'search_url'      => 'https://jobs.theguardian.com/jobs/',
            'example'         => 'https://jobs.theguardian.com/jobsrss/?keywords=arts&countrycode=GB',
            'sample'          => 'https://jobs.theguardian.com/jobsrss/?JobLevel=19&keywords=arts&countrycode=GB&[etc...]',
            'meta'            => array( 'logo' ),
            'regexp_mappings' => array(
            'company'  => '/(?(?!.*?:\\s.*?:\\s.*?)(.*?)-|.*?:(.*?):\\s)/is',
            'location' => '/\\.([^.]+)$/is',
        ),
            'query_args'      => array(
            'keyword'  => array(
            'keywords' => '',
        ),
            'location' => array(
            'radialtown' => '',
        ),
        ),
            'default'         => true,
        ),
            'special'     => array(
            'scrape' => array(
            'description' => array(
            'nicename' => __( 'Job Description', 'gofetch-wpjm' ),
            'query'    => '//div[@itemprop="description"]',
        ),
            'company'     => array(
            'nicename' => __( 'Company', 'gofetch-wpjm' ),
            'query'    => '//div[@itemprop="hiringOrganization"]//span[@itemprop="name"]',
        ),
            'location'    => array(
            'nicename' => __( 'Location', 'gofetch-wpjm' ),
            'query'    => '//dl[@class="grid"]//div[2]//dd',
        ),
            'logo'        => array(
            'nicename' => __( 'Logo', 'gofetch-wpjm' ),
            'query'    => '//img[contains(@class,"rec-logo--job-detail")]/@src',
        ),
        ),
        ),
            'weight'      => 9,
            'category'    => __( 'Generic', 'gofetch-wpjm' ),
        ),
            'us.jobs.com'                 => array(
            'website'     => 'http://us.jobs/',
            'logo'        => 'http://images.us.jobs/usdj/logos/usjobslogo.png',
            'description' => 'US.jobs - National Labor Exchange.',
            'feed'        => array(
            'base_url'       => 'http://us.jobs/rss.asp?si=1310658541&so=relevance',
            'search_url'     => 'http://us.jobs/results.asp',
            'example'        => 'http://us.jobs/rss.asp?si=1310632245&so=relevance',
            'sample'         => 'http://us.jobs/rss.asp?si=1310646251&so=relevance&kw=design[etc...]',
            'query_args'     => array(
            'keyword'  => array(
            'kw' => '',
        ),
            'location' => array(
            'zc' => '',
        ),
        ),
            'base-data-less' => array( 'description' ),
        ),
            'weight'      => 6,
            'category'    => __( 'Generic', 'gofetch-wpjm' ),
        ),
            'trabajos.com'                => array(
            'website'     => 'https://www.trabajos.com',
            'logo'        => 'https://trabajos.hvimg.com/img/logo_trabajos.gif',
            'description' => 'Ofertas de Trabajo, Bolsa de Empleo',
            'feed'        => array(
            'base_url' => 'https://www.trabajos.com/rss',
            'example'  => 'https://www.trabajos.com/rss/busquedas/2-0-0-0/',
            'examples' => array(
            __( 'Ventas - Comercial, Europa', 'gofetch-wpjm' ) => 'https://www.trabajos.com/rss/busquedas/90-0-110-0/',
            __( 'Ingenierías, España', 'gofetch-wpjm' )      => 'https://www.trabajos.com/rss/busquedas/2-0-0-0/',
            __( 'Diseño y Artes Gráficas', 'gofetch-wpjm' )  => 'https://www.trabajos.com/rss/busquedas/40-0-100-0/',
        ),
        ),
            'special'     => array(
            'scrape' => array(
            'description' => array(
            'nicename' => __( 'Job Description', 'gofetch-wpjm' ),
            'query'    => '//div[@class="bloqueoferta"]/*[not(@class="clasificadoen")]',
        ),
            'company'     => array(
            'nicename' => __( 'Company', 'gofetch-wpjm' ),
            'query'    => '//span[@itemprop="hiringOrganization"]//a',
        ),
            'location'    => array(
            'nicename' => __( 'Location', 'gofetch-wpjm' ),
            'query'    => '//span[@itemprop="jobLocation"]//a',
        ),
        ),
        ),
            'weight'      => 1,
            'category'    => __( 'Generic', 'gofetch-wpjm' ),
        ),
            'expressoemprego.pt'          => array(
            'website'     => 'http://expressoemprego.pt/',
            'logo'        => 'https://pbs.twimg.com/profile_images/3116193991/c5507f53c7632991b9b163b39bb3d3f3_400x400.png',
            'description' => 'A sua Carreira é o nosso Trabalho',
            'feed'        => array(
            'base_url'        => 'http://expressoemprego.pt/rss',
            'meta'            => array( 'logo' ),
            'regexp_mappings' => array(
            'company'  => '/^(.*?)\\|/is',
            'location' => '/\\|(.*?)\\|/is',
        ),
            'examples'        => array(
            __( 'Latest 50 Jobs', 'gofetch-wpjm' )        => 'http://www.expressoemprego.pt/rss/ultimas-ofertas',
            __( 'Latest Internet Jobs', 'gofetch-wpjm' )  => 'http://expressoemprego.pt/rss/internet',
            __( 'Latest Jobs in Lisbon', 'gofetch-wpjm' ) => 'http://expressoemprego.pt/rss/lisboa',
        ),
        ),
            'weight'      => 1,
            'category'    => __( 'Generic', 'gofetch-wpjm' ),
        ),
            'cargadetrabalhos.net'        => array(
            'website'     => 'http://www.cargadetrabalhos.net/',
            'logo'        => 'http://www.cargadetrabalhos.net/wp-content/themes/bluekino%28en%29/images/cdtlogosombratop.jpg',
            'description' => 'Emprego na área da comunicação',
            'feed'        => array(
            'base_url' => 'http://www.cargadetrabalhos.net/feed/',
            'default'  => true,
        ),
            'weight'      => 6,
            'category'    => __( 'Marketing', 'gofetch-wpjm' ),
        ),
            'jobs.marketinghire.com'      => array(
            'website'     => 'http://jobs.marketinghire.com/',
            'logo'        => 'http://www.marketinghire.com/templates/ja_university/images/logo.png',
            'description' => 'All Marketing jobs',
            'feed'        => array(
            'base_url'        => 'http://jobs.marketinghire.com/jobs/?display=rss',
            'search_url'      => 'http://jobs.marketinghire.com/jobs',
            'example'         => 'http://jobs.marketinghire.com/jobs/?display=rss&keywords=specialist&resultsPerPage=25',
            'sample'          => 'http://jobs.marketinghire.com/jobs/?display=rss&keywords=specialist&filter=&[etc...]',
            'meta'            => array( 'logo' ),
            'regexp_mappings' => array(
            'title'       => array(
            'company' => '/.*\\|(.*)/',
        ),
            'description' => array(
            'location' => '/^(.*?),/is',
        ),
        ),
            'query_args'      => array(
            'keyword'  => array(
            'keywords' => '',
        ),
            'location' => array(
            'place' => '',
        ),
        ),
            'default'         => true,
        ),
            'special'     => array(
            'scrape' => array(
            'description' => array(
            'nicename' => __( 'Job Description', 'gofetch-wpjm' ),
            'query'    => '//div[@class="bti-jd-description" and @itemprop="description"]',
        ),
            'company'     => array(
            'nicename' => __( 'Company', 'gofetch-wpjm' ),
            'query'    => '//span[@itemprop="hiringOrganization"]',
        ),
            'location'    => array(
            'nicename' => __( 'Location', 'gofetch-wpjm' ),
            'query'    => '//span[@itemprop="addressLocality"]',
        ),
            'logo'        => array(
            'nicename' => __( 'Logo', 'gofetch-wpjm' ),
            'query'    => '//div[@id="bti-apply-emp-logo"]//img[@itemprop="URL"]/@src',
            'base_url' => 'http://jobs.marketinghire.com',
        ),
        ),
        ),
            'weight'      => 7,
            'category'    => __( 'Marketing', 'gofetch-wpjm' ),
        ),
            __( 'Other', 'gofetch-wpjm' ) => array(
            'website'     => '#',
            'logo'        => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/46/Generic_Feed-icon.svg/500px-Generic_Feed-icon.svg.png',
            'description' => 'Use RSS feed from other provider',
            'feed'        => array(
            'base_url' => '#',
        ),
            'category'    => __( 'Other', 'gofetch-wpjm' ),
            'weight'      => 99,
        ),
        );
        $providers = apply_filters( 'goft_wpjm_providers', $providers );
        if ( $provider ) {
            
            if ( empty($providers[$provider]) ) {
                if ( $parent = self::get_provider_parent( $provider ) ) {
                    return $parent;
                }
                return array();
            } else {
                return $providers[$provider];
            }
        
        }
        return $providers;
    }
    
    /**
     * Retrieves the RSS feed setup instructions for a given provider.
     */
    public static function setup_instructions_for( $provider )
    {
        $setup = $header = $multi_region = $steps_li = $skip_copy_url = '';
        $steps = 0;
        $data = self::get_providers( $provider );
        if ( empty($data['feed']['search_url']) ) {
            $data['feed']['search_url'] = $data['feed']['base_url'];
        }
        // __Header.
        
        if ( !empty($data['feed']['base_url']) ) {
            $header = html( 'a', array(
                'href'   => esc_url( $data['website'] ),
                'target' => '_blank',
            ), html( 'img', array(
                'src'   => esc_url( $data['logo'] ),
                'class' => 'provider-logo-orig',
            ) ) );
            $header .= html( 'p', html( 'em', $data['description'] ) );
        }
        
        $header = html( 'div class="provider-header"', $header );
        // __Meta.
        $base_data = array(
            'title'       => __( 'Title', 'gofetch-wpjm' ),
            'description' => __( 'Description', 'gofetch-wpjm' ),
            'date'        => __( 'Date', 'gofetch-wpjm' ),
        );
        $meta_data = array();
        if ( !empty($data['feed']['base-data-less']) ) {
            $base_data = array_diff( array_keys( $base_data ), $data['feed']['base-data-less'] );
        }
        if ( !empty($data['feed']['meta']) ) {
            $meta_data = array_merge( $meta_data, $data['feed']['meta'] );
        }
        if ( !empty($data['feed']['meta-less']) ) {
            $meta_data = array_diff( $meta_data, $data['feed']['meta-less'] );
        }
        if ( !empty($data['feed']['tag_mappings']) ) {
            $meta_data = array_merge( $meta_data, array_keys( $data['feed']['tag_mappings'] ) );
        }
        
        if ( !empty($data['feed']['regexp_mappings']) ) {
            $regexp_keys_all = $data['feed']['regexp_mappings'];
            foreach ( $regexp_keys_all as $key => $d ) {
                
                if ( is_array( $d ) ) {
                    $regexp_keys[] = key( $d );
                } else {
                    $regexp_keys[] = $key;
                }
            
            }
            $meta_data = array_merge( $meta_data, $regexp_keys );
        }
        
        if ( !empty($data['special']['scrape']) ) {
            $meta_data = array_merge( $meta_data, array_keys( $data['special']['scrape'] ) );
        }
        $meta_li = '';
        foreach ( $base_data as $key => $field ) {
            $meta_li .= html( 'li', ucfirst( $field ) );
        }
        $multi_region = $api = $data_info = '';
        
        if ( empty($data['API']) ) {
            $data_info = html( 'p', sprintf( __( 'Data provided in RSS feeds: %s', 'gofetch-wpjm' ), html( 'ul', $meta_li ) ) );
            
            if ( !empty($meta_data) ) {
                $meta_data = array_intersect( self::valid_item_tags(), $meta_data );
                $meta_li = '';
                foreach ( $meta_data as $field ) {
                    $meta_li .= html( 'li', ucfirst( $field ) );
                }
                if ( $meta_li ) {
                    $data_info .= html( 'p', sprintf( __( 'Other possible data <small>(not always available - may be available through scraping only)</small>: %1$s %2$s', 'gofetch-wpjm' ), GoFetch_WPJM_Admin::limited_plan_warn(), html( 'ul', $meta_li ) ) );
                }
            }
            
            $data_info = html( 'div class="provider-data secondary-container"', $data_info );
            // __Multi Region.
            
            if ( isset( $data['multi-region'] ) ) {
                $multi_region = '<br/>' . __( 'This is a multi region jobs site. These instructions are meant for a specific country site but they should also work with any of the other available country sites.', 'gofetch-wpjm' );
                
                if ( !empty($data['multi-region']) ) {
                    $multi_region .= '   ' . html( 'span', html( 'a', array(
                        'href'         => '#',
                        'class'        => 'provider-expand-multi-region-details',
                        'data-child'   => 'multi-region-details',
                        'data-default' => __( 'Info', 'gofetch-wpjm' ),
                    ), __( 'Info', 'gofetch-wpjm' ) ) );
                    $multi_region .= sprintf( '<p class="multi-region-details secondary-container">%s</p>', $data['multi-region'] );
                }
            
            }
        
        } else {
            $api = '<br/>' . __( 'Jobs are pulled using the provider API using a XML feed URL.', 'gofetch-wpjm' );
            $api .= '<br/><br/>' . sprintf( __( 'For information on all the data retrieved from the API please visit your <a href="%s">Publisher Account</a>.', 'gofetch-wpjm' ), $data['API']['info'] );
            $api .= '<br/><br/>' . html( 'div class="provider-data secondary-container dashicons-before dashicons-warning"', ' ' . sprintf( __( '<em>You need a valid Publisher ID/API Key to pull jobs from this provider. Please refer to the plugin settings to setup your account.</em>', 'gofetch-wpjm' ), admin_url( 'page=go-fetch-jobs-wpjm-settings' ) ) );
        }
        
        switch ( $provider ) {
            case 'api.indeed.com':
            case 'api.careerjet.com':
                $steps_li .= html( 'li', sprintf( __( 'For detailed information on how to setup a XML feed please visit your <a href="%s">Publisher Account</a>.', 'gofetch-wpjm' ), $data['API']['info'] ) );
                ++$steps;
                break;
            case 'technojobs.co.uk':
            case 'freelancewritinggigs.com':
            case 'jobs.smashingmagazine.com':
            case 'dribbble.com':
            case 'mediabistro.com':
            case 'totaljobs.com':
            case 'authenticjobs.com':
            case 'jobs.gamasutra.com':
            case 'craigslist.org':
            case 'uk.dice.com':
            case 'sap.dice.com':
            case 'indeed.com':
            case 'careerjet.com':
            case 'jobs.marketinghire.com':
            case 'reed.co.uk':
            case 'us.jobs.com':
            case 'problogger.com':
                $steps_li .= html( 'li', sprintf( __( 'Visit the provider job search page by clicking <a href="%2$s" target="_blank">here</a>.</p>', 'gofetch-wpjm' ), ++$steps, esc_url( $data['feed']['search_url'] ) ) );
                $steps_li .= html( 'li', sprintf( __( 'Setup your jobs criteria and click the search button or \'Enter\'.</p>', 'gofetch-wpjm' ), ++$steps ) );
                $steps_li .= html( 'li', sprintf( __( '<strong>OR</strong> ... click on any existing pre-set filters (if available) like <code>Jobs by Location</code>, <code>Jobs by Title</code>, etc...</p>', 'gofetch-wpjm' ), ++$steps ) );
                switch ( $provider ) {
                    case 'freelancewritinggigs.com':
                        $steps_li .= html( 'li', sprintf( __( 'After you get the results click the <code>RSS</code> link/button on top of the search results.</p>', 'gofetch-wpjm' ), ++$steps ) );
                        break;
                    case 'jobs.gamasutra.com':
                    case 'jobs.theguardian.com':
                        $steps_li .= html( 'li', sprintf( __( 'After you get the results scroll to the bottom of the page and click the <code>Subscribe/Subscribe to RSS</code> link.</p>', 'gofetch-wpjm' ), ++$steps ) );
                        break;
                    case 'technojobs.co.uk':
                        $steps_li .= html( 'li', sprintf( __( 'After you get the results click the <code>%2$s</code> link.</p>', 'gofetch-wpjm' ), ++$steps, __( 'Subscribe to an RSS Feed for this search', 'gofetch-wpjm' ) ) );
                        $steps_li .= html( 'li', sprintf( __( 'You will be taken to a new page that contains the URL of the generated RSS feed. Look for it under the <code>%2$s</code> text.</p>', 'gofetch-wpjm' ), ++$steps, __( 'The address for your RSS feed is here:', 'gofetch-wpjm' ) ) );
                        $skip_copy_url = true;
                        break;
                    case 'authenticjobs.com':
                        $steps_li .= html( 'li', sprintf( __( 'After you get the results click the <code>Email/RSS Notifications</code> button on top of the search results.</p>', 'gofetch-wpjm' ), ++$steps ) );
                        $steps_li .= html( 'li', sprintf( __( 'Click the <code>RSS</code> option and copy & paste the URL to your browser address bar.</p>', 'gofetch-wpjm' ), ++$steps ) );
                        break;
                    case 'reed.co.uk':
                        $steps_li .= html( 'li', sprintf( __( 'After you get the results replace everything in your URL before the <code>?</code> with the feed URL <code>%2$s</code>.</p>', 'gofetch-wpjm' ), ++$steps, $data['feed']['base_url'] . '?' ) );
                        $steps_li .= html( 'li', sprintf( __( 'If you specified <code>location</code> as a criteria you need to manually add it to the URL parameters <code>%2$s</code>.</p>', 'gofetch-wpjm' ), ++$steps, add_query_arg( 'location', 'london', $data['feed']['base_url'] ) ) );
                        break;
                    case 'us.jobs.com':
                        $steps_li .= html( 'li', sprintf( __( 'After you get the results, on the right sidebar, click <code>%2$s</code>.</p>', 'gofetch-wpjm' ), ++$steps, __( 'Save as RSS feed', 'gofetch-wpjm' ) ) );
                        $steps_li .= html( 'li', sprintf( __( 'Below, you will find a <code>%2$s</code> that you can click.', 'gofetch-wpjm' ), ++$steps, 'RSS 2.0 feed' ) );
                        break;
                    case 'jobs.smashingmagazine.com':
                    case 'mediabistro.com':
                    case 'dribbble.com':
                    case 'totaljobs.com':
                    case 'indeed.com':
                    case 'careerjet.com':
                    case 'problogger.com':
                        $steps_li .= html( 'li', sprintf( __( 'After you get the results replace everything in your URL before the <code>?</code> with the feed URL <code>%2$s</code>.</p>', 'gofetch-wpjm' ), ++$steps, $data['feed']['base_url'] . '?' ) );
                        break;
                    default:
                        $steps_li .= html( 'li', sprintf( __( 'After you get the results click the <code>RSS</code> (<span class="dashicons dashicons-rss"></span>) link/button/icon on the bottom.</p>', 'gofetch-wpjm' ), ++$steps ) );
                        break;
                }
                break;
            case 'krop.com':
            case 'coroflot.com':
            case 'cargadetrabalhos.net':
                $setup .= __( '<p>Unfortunately this provider does not allow customizing the RSS feed.</p><br/>', 'gofetch-wpjm' );
                break;
            case 'jobs.wordpress.net':
                $setup .= __( '<p>Unfortunately this provider does not allow customizing the RSS feed but provides keywords and categorized feeds.</p><br/>', 'gofetch-wpjm' );
                $steps_li .= html( 'li', sprintf( __( 'Visit the provider jobs page by clicking <a href="%2$s" target="_blank">here</a>.</p>', 'gofetch-wpjm' ), ++$steps, esc_url( $data['feed']['base_url'] ) ) );
                $steps_li .= html( 'li', sprintf( __( 'Click on any of the RSS icons (<span class="dashicons dashicons-rss"></span>) over each of the job results groups.</p>', 'gofetch-wpjm' ), ++$steps ) );
                $steps_li .= html( 'li', sprintf( __( '<strong>OR</strong> ... do a search for the keywords you want to use and click on the RSS icon (<span class="dashicons dashicons-rss"></span>) on top of the search results.</p>', 'gofetch-wpjm' ), ++$steps ) );
                break;
            case 'weworkremotely.com':
                $setup .= __( '<p>Unfortunately this provider does not allow customizing the RSS feed but provides categorized feeds.</p><br/>', 'gofetch-wpjm' );
                $steps_li .= html( 'li', sprintf( __( 'Visit the provider jobs page by clicking <a href="%2$s" target="_blank">here</a>.</p>', 'gofetch-wpjm' ), ++$steps, esc_url( $data['feed']['search_url'] ) ) );
                $steps_li .= html( 'li', sprintf( __( 'Click on any of the RSS icons (<span class="dashicons dashicons-rss"></span>) over each of the job results groups.</p>', 'gofetch-wpjm' ), ++$steps ) );
                break;
            case 'expressoemprego.pt':
            case 'monster.com.hk':
            case 'dice.com':
                $setup .= __( '<p>Unfortunately this provider does not allow customizing the RSS feed but provides categorized feeds.</p><br/>', 'gofetch-wpjm' );
                $steps_li .= html( 'li', sprintf( __( 'Visit the provider RSS feeds page by clicking <a href="%2$s" target="_blank">here</a>.</p>', 'gofetch-wpjm' ), ++$steps, esc_url( $data['feed']['search_url'] ) ) );
                $steps_li .= html( 'li', sprintf( __( 'Click on any of the feeds from the RSS feed list.</p>', 'gofetch-wpjm' ), ++$steps ) );
                break;
            case 'jobs.ac.uk':
                $setup .= __( '<p>Unfortunately this provider does not allow customizing the RSS feed but provides categorized feeds.</p><br/>', 'gofetch-wpjm' );
                $steps_li .= html( 'li', sprintf( __( 'Visit the provider RSS feeds page by clicking <a href="%2$s" target="_blank">here</a>.</p>', 'gofetch-wpjm' ), ++$steps, esc_url( $data['feed']['base_url'] ) ) );
                $steps_li .= html( 'li', sprintf( __( 'Click on any of the feed categories from the list.</p>', 'gofetch-wpjm' ), ++$steps ) );
                $steps_li .= html( 'li', sprintf( __( 'You will be taken to a new page that contains all the available RSS feeds.</p>', 'gofetch-wpjm' ), ++$steps ) );
                $steps_li .= html( 'li', sprintf( __( 'Click on any of the feeds from the RSS feed list.</p>', 'gofetch-wpjm' ), ++$steps ) );
                break;
            case 'trabajos.com':
                $setup .= __( '<p>Unfortunately this provider does not allow customizing the RSS feed directly but provides their own custom builder on site.</p><br/>', 'gofetch-wpjm' );
                $steps_li .= html( 'li', sprintf( __( 'Visit the provider RSS feeds page by clicking <a href="%2$s" target="_blank">here</a>.</p>', 'gofetch-wpjm' ), ++$steps, esc_url( $data['feed']['base_url'] ) ) );
                $steps_li .= html( 'li', sprintf( __( 'From the dropdown lists choose the options that best fit the jobs you want to import and click the button.</p>', 'gofetch-wpjm' ), ++$steps ) );
                $steps_li .= html( 'li', sprintf( __( 'You will be taken to a new page that contains your custom RSS feed.</p>', 'gofetch-wpjm' ), ++$steps ) );
                break;
            case __( 'Other', 'gofetch-wpjm' ):
                $other_li = html( 'li', __( 'Visit any job site jobs search page, click \'View Source\' on your browser and search for the <code>RSS</code> word. In case you find matches look for any RSS related links near it.</p>', 'gofetch-wpjm' ) );
                $other_li .= html( 'li', __( 'Google directly for <code>my job site provider + RSS feeds</code></p>', 'gofetch-wpjm' ) );
                $other_li .= html( 'li', sprintf( __( 'Search for job sites directly from an RSS Reader like <a href="%1$s" target="_blank">Feedly</a>.</p>', 'gofetch-wpjm' ), 'https://feedly.com' ) );
                $setup .= html( 'p', sprintf( __( 'To use other job feed providers outside the providers list try the following:', 'gofetch-wpjm' ) ), html( 'ul', $other_li ) );
                $setup .= '<br/>' . html( 'p', __( 'In any case, most job sites usually offer a pre-set list of RSS feeds or a custom RSS builder based on a job search.', 'gofetch-wpjm' ) . ' ' . __( 'Just follow the instructions for similar providers and you should be ready to go.', 'gofetch-wpjm' ) );
                break;
            default:
                $steps_li .= html( 'li', sprintf( __( 'Visit the provider job search page by clicking <a href="%2$s" target="_blank">here</a>.</p>', 'gofetch-wpjm' ), ++$steps, esc_url( $data['feed']['search_url'] ) ) );
                $steps_li .= html( 'li', sprintf( __( 'Setup your jobs criteria and click the search button.</p>', 'gofetch-wpjm' ), ++$steps ) );
        }
        
        if ( $steps ) {
            if ( !$skip_copy_url ) {
                
                if ( !empty($data['API']) ) {
                    $steps_li .= html( 'li', sprintf( __( 'Use the XML feed URL from your account as reference and change any parameters you like.</p>', 'gofetch-wpjm' ), ++$steps ) );
                } else {
                    $steps_li .= html( 'li', sprintf( __( 'Copy the feed URL from your browser address bar.</p>', 'gofetch-wpjm' ), ++$steps ) );
                }
            
            }
            
            if ( !empty($data['feed']['sample']) ) {
                $samples = '';
                foreach ( (array) $data['feed']['sample'] as $sample ) {
                    $samples .= ( $samples ? ' ' . __( 'OR', 'gofetch-wpjm' ) . ' ' : '' );
                    $samples .= sprintf( '<a href="%1$s" target="_blank">%1$s</a>', $sample );
                }
                $steps_li .= html( 'li', sprintf( __( 'You should have an URL similar to this %2$s (depending on your criteria).</p>', 'gofetch-wpjm' ), ++$steps, $samples ) );
            }
            
            $steps_li .= html( 'li', sprintf( __( 'Paste your new RSS/XML feed URL on the <code>URL</code> input field below.', 'gofetch-wpjm' ), ++$steps ) );
            $setup .= html( 'ol', $steps_li );
        }
        
        // Display single example.
        
        if ( !empty($data['feed']['example']) ) {
            $setup .= '<br/>';
            $setup .= html( 'p', html( 'strong', __( 'Example', 'gofetch-wpjm' ) ) . self::copy_paste() );
            $setup .= html( 'p class="provider-other-feeds"', sprintf( '<span class="dashicons dashicons-rss"></span> <a href="%1$s" class="provider-rss" target="_blank">%1$s</a></p>', esc_url( $data['feed']['example'] ) ) );
        }
        
        if ( !empty($data['feed']['default']) ) {
            $data['feed']['fixed'] = array(
                __( 'Latest Jobs', 'gofetch-wpjm' ) => $data['feed']['base_url'],
            );
        }
        // Display the fixed RSS feeds.
        
        if ( !empty($data['feed']['fixed']) ) {
            $setup .= '<br/>';
            
            if ( count( $data['feed']['fixed'] ) === 1 ) {
                
                if ( $steps ) {
                    $setup .= sprintf( __( '<p><strong>OR</strong> ... use the default RSS feed from the provider %1$s:</p>', 'gofetch-wpjm' ), self::copy_paste(), ++$steps );
                } else {
                    $setup .= sprintf( __( 'Click on your preferred RSS feed from the provider pre-set list:</p>', 'gofetch-wpjm' ), ++$steps );
                }
            
            } else {
                $setup .= sprintf( __( 'Choose your preferred RSS feed from the provider list %1$s:</p>', 'gofetch-wpjm' ), self::copy_paste(), ++$steps );
            }
            
            $setup .= '<br/>';
            foreach ( $data['feed']['fixed'] as $desc => $url ) {
                $setup .= html( 'p class="provider-other-feeds"', sprintf( '<span class="dashicons dashicons-rss"></span> <a href="%2$s" class="provider-rss" target="_blank">%1$s</a></p>', $desc, esc_url( $url ) ) );
            }
            $setup .= '<br/>';
        }
        
        // Display examples.
        
        if ( !empty($data['feed']['examples']) ) {
            $setup .= '<br/><br/>';
            $setup .= html( 'p', sprintf( __( 'Here are some quick ready to use examples: %1$s', 'gofetch-wpjm' ), self::copy_paste(), ++$steps ) );
            $setup .= '<br/>';
            foreach ( $data['feed']['examples'] as $desc => $url ) {
                $setup .= html( 'p class="provider-other-feeds"', sprintf( '<span class="dashicons dashicons-rss"></span> <a href="%2$s" class="provider-rss" target="_blank">%1$s</a></p>', $desc, esc_url( $url ) ) );
            }
            $setup .= '<br/>';
        }
        
        if ( !empty($notes) ) {
            $setup .= '<br/>' . $notes;
        }
        // Wrap the manual setup.
        $manual_setup = html( 'p', html( 'a', array(
            'href'         => '#',
            'class'        => 'provider-expand-feed-manual-setup',
            'data-child'   => 'feed-manual-setup',
            'data-default' => __( 'Manual Setup Instructions', 'gofetch-wpjm' ),
        ), __( 'Manual Setup Instructions', 'gofetch-wpjm' ) ) );
        $setup = $manual_setup . html( 'div class="feed-manual-setup"', $setup );
        $setup = $header . $api . $multi_region . $data_info . $setup;
        // Wrap the builder, if available.
        if ( !empty($data['feed']['query_args']) ) {
            
            if ( !gfjwjm_fs()->is_not_paying() ) {
                $feed_builder = html( 'p', html( 'a', array(
                    'href'         => '#',
                    'class'        => 'provider-expand-feed-builder',
                    'data-child'   => 'feed-builder',
                    'data-default' => __( 'Use Feed Builder', 'gofetch-wpjm' ),
                ), __( 'Use RSS Feed Builder', 'gofetch-wpjm' ) ) );
                $setup .= $feed_builder . html( 'div class="feed-builder"', self::output_rss_feed_builder( $provider, $data ) );
            } else {
                $setup .= html( 'p', html( 'a', array(
                    'href'  => '#',
                    'class' => '',
                ), __( 'Use Feed Builder', 'gofetch-wpjm' ) ), ' ' . GoFetch_WPJM_Admin::limited_plan_warn() );
            }
        
        }
        return apply_filters( 'goft_wpjm_setup_instructions_for', $setup, $provider );
    }
    
    /**
     * Outputs a dropdown will all the available RSS providers.
     */
    public static function output_providers_dropdown( $atts = array() )
    {
        $choices = $choices_n = array();
        foreach ( self::get_providers() as $name => $data ) {
            $weight = ( !empty($data['weight']) ? $data['weight'] : 1 );
            $choices_n[$weight][] = $name;
        }
        krsort( $choices_n );
        // Retrieve choices sorted by provider weight.
        foreach ( $choices_n as $providers ) {
            foreach ( $providers as $provider ) {
                $data = self::get_providers( $provider );
                $defaults = array(
                    'description' => '',
                    'category'    => __( 'Other', 'gofetch-wpjm' ),
                );
                $data = wp_parse_args( $data, $defaults );
                $categories[$data['category']][$provider] = sprintf(
                    '%1$s %2$s %3$s',
                    ( isset( $data['API'] ) ? '<em>[API]</em>' : '' ),
                    ( isset( $data['multi-region'] ) ? __( '<em>[Multi-Region]</em>', 'gofetch-wpjm' ) : '' ),
                    ( !empty($data['description']) ? $data['description'] : '' )
                );
            }
        }
        ksort( $categories );
        $options = $optgroup = '';
        // Iterate through the categories.
        foreach ( $categories as $category => $providers ) {
            foreach ( $providers as $provider => $desc ) {
                $options .= html( 'option', array(
                    'value'     => esc_attr( $provider ),
                    'data-desc' => esc_attr( $desc ),
                ), $provider . ' ' . $desc );
            }
            $optgroup .= html( 'optgroup', array(
                'label' => esc_attr( ucwords( $category ) ),
            ), $options );
            $options = '';
        }
        $optgroup = html( 'option', array(
            'value' => '',
        ), __( 'Choose a Job Provider . . .', 'gofetch-wpjm' ) ) . $optgroup;
        $defaults = array(
            'name' => 'goftj_rss_providers',
            'size' => '20',
        );
        $atts = wp_parse_args( $atts, $defaults );
        return html( 'select', $atts, $optgroup );
    }
    
    /**
     * Output the main builder markup.
     */
    public static function output_rss_feed_builder( $provider, $data )
    {
        $output = '';
        return $output;
    }
    
    /**
     * Retrieves a copy&paste HTML message.
     */
    public static function copy_paste()
    {
        return '<code class="copy-paste-info"><span class="icon icon-goft-paste"></span> click the link(s) to copy&paste</code>';
    }
    
    /**
     * Check for a provider
     */
    public static function get_provider_parent( $provider_id )
    {
        // Check for a parent provider if this is a multi-region provider.
        foreach ( self::get_providers() as $id => $data ) {
            
            if ( !empty($data['multi_region_match']) && false !== strpos( $provider_id, $data['multi_region_match'] ) ) {
                $provider = self::get_providers( $id );
                $provider['inherit'] = true;
                return $provider;
            }
        
        }
        return false;
    }
    
    /**
     * Try to locate a provider on a URL string and retrieve it on success.
     *
     * @since 1.3.1.
     */
    public static function find_provider_in_url( $url, $provider_match = '' )
    {
        $providers = self::get_providers();
        foreach ( $providers as $provider_id => $meta ) {
            if ( false !== strpos( $url, $provider_id ) ) {
                if ( !$provider_match || $provider_match && false !== strpos( $provider_id, $provider_match ) ) {
                    return $provider_id;
                }
            }
            if ( false !== strpos( $url, $meta['feed']['base_url'] ) ) {
                return $provider_id;
            }
        }
        return false;
    }
    
    /**
     * Retrieve a smaller URL given a provider URL.
     *
     * @since 1.3.1
     */
    public static function simple_url( $url )
    {
        $source = ( $url ? str_replace( array( 'http://', 'https://' ), '', untrailingslashit( $url ) ) : '-' );
        return str_replace( 'www.', '', $source );
    }

}