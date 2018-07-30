<?php

/*
* @Author 		pickplugins
* Copyright: 	2015 pickplugins
*/

if ( ! defined('ABSPATH')) exit;  // if direct access 

class class_job_bm_shortcodes_my_jobs{
	
    public function __construct(){
		
		add_shortcode( 'job_bm_my_jobs', array( $this, 'job_bm_my_jobs_display' ) );

   		}

	public function job_bm_my_jobs_display($atts, $content = null ) {
		
		$atts = shortcode_atts(
			array(
				//'themes' => 'flat',
				'display_edit' => 'yes',
				'display_delete' => 'yes',
				), $atts);

		
		//$job_bm_themes = $atts['themes'];
		$display_edit = $atts['display_edit'];
		$display_delete = $atts['display_delete'];		
		

		ob_start();
		
		include( job_bm_plugin_dir . 'templates/my-jobs/my-jobs.php');

		return ob_get_clean();
		
		}
	
			
	}
	
new class_job_bm_shortcodes_my_jobs();