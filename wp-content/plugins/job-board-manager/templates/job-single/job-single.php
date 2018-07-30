<?php
/*
* @Author 		PickPlugins
* Copyright: 	2015 PickPlugins.com
*/

if ( ! defined('ABSPATH')) exit;  // if direct access 


	$job_id = get_the_ID();

    do_action('job_bm_action_before_single_job');

    ?>
    <div itemscope itemtype="http://schema.org/JobPosting" id="job-<?php $job_id; ?>" <?php post_class('job-single entry-content'); ?>>

    <?php do_action('job_bm_action_single_job_main'); ?>

    <div class="clear"></div>
    </div>
    <?php

    do_action('job_bm_action_after_single_job');



