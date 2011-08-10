<?php

/*
	Plugin Name: Logarithmic Tag Cloud Widget
	Plugin URI: https://github.com/NoahY/q2a-log-tags
	Plugin Description: Provides a list of tags with logarithmic size indicating popularity
	Plugin Version: 0.1
	Plugin Date: 2011-08-10
	Plugin Author: NoahY
	Plugin Author URI: http://www.question2answer.org/qa/user/NoahY
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.4
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}


	qa_register_plugin_module('widget', 'qa-tag-cloud.php', 'qa_log_tag_cloud', 'Logarithmic Tag Cloud');
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
