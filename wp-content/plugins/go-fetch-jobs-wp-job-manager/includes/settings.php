<?php
/**
 * Contains all the options used by the plugin.
 */

$GLOBALS['goft_wpjm_options'] = new scbOptions( 'goft_wpjm_options', __FILE__, array(

	// Templates.
	'templates'               => array(),

	// General.
	'admin_jobs_filter'       => false,
	'admin_provider_filter'   => false,
	'admin_jobs_provider_col' => false,

	// Importing.
	'rss_item_check_updates' => 'ignore_all',
	'keyword_matching'       => 'all',
	'use_cors_proxy'         => false,

	// Geocode.
	'geocode_api_key'         => '',
	'geocode_rate_limit'      => 50,

	// Jobs.
	'allow_visitors_apply'       => false,
	'read_more_text'             => '[...]',
	'canonical_links'            => false,
	'post_status'                => 'publish',
	'jobs_duration'              => get_option( 'job_manager_submission_duration' ),
	'independent_listings'       => false,
	'filter_imported_jobs'       => false,
	'filter_imported_jobs_label' => 'External Jobs',
	'filter_site_jobs_label'     => 'Our Jobs',

	// Debugging.
	'debug_log'                => false,

	// Scheduler.
	'scheduler_start_time'     => '09:00',
	'scheduler_interval_sleep' => '5',

	// @todo: find a way to extend these options.

	// Indeed
	'indeed_publisher_id'         => '',
	'indeed_feature_sponsored'    => '',
	'indeed_feed_default_radius'  => 25,
	'indeed_feed_default_latlong' => true,
	'indeed_feed_default_co'      => 'us',
	'indeed_feed_default_fromage' => '',
	'indeed_feed_default_st'      => 'jobsite',
	'indeed_feed_default_chnl'    => '',
	'indeed_feed_default_sort'    => 'relevance',
	'indeed_feed_default_limit'   => 25,
	'indeed_feed_default_jt'      => '',

	// Carerjet
	'careerjet_publisher_id'                => '',
	'careerjet_feed_default_locale_code'    => 'en_GB',
	'careerjet_feed_default_contracttype'   => '',
	'careerjet_feed_default_contractperiod' => '',
	'careerjet_feed_default_sort'           => 'relevance',
	'careerjet_feed_default_pagesize'       => 50,

	// ZipRecruiter
	'ziprecruiter_api_key'                       => '',
	'ziprecruiter_feed_default_jobs_per_page'    => 50,
	'ziprecruiter_feed_default_radius'           => 25,
	'ziprecruiter_feed_default_days_ago'         => '',
	'ziprecruiter_feed_default_refine_by_salary' => '',

	// AdView
	'adview_publisher_id'             => '',
	'adview_feed_default_radius'      => 25,
	'adview_feed_default_salary_from' => '',
	'adview_feed_default_salary_to'   => '',
	'adview_feed_default_channel'     => '',
	'adview_feed_default_sort'        => 'relevance',
	'adview_feed_default_limit'       => 50,
	'adview_feed_default_job_type'    => '',
	'adview_feed_default_mode'        => 'advanced',
) );
