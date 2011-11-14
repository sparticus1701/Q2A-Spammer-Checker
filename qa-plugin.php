<?php

/*
	Plugin Name: Spammer Checker
	Plugin URI: 
	Plugin Description: Provides options for checking whether an IP or email is a known spammer
	Plugin Version: 1.0
	Plugin Date: 2011-11-14
	Plugin Author: Walter Williams
	Plugin Author URI: 
	Plugin License: 
	Plugin Minimum Question2Answer Version: 1.4
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}


	qa_register_plugin_module('widget', 'spammer-check-widget.php', 'spammer_check_widget', 'Spammer Checker');
	qa_register_plugin_module('event', 'spammer-check-event.php', 'spammer_check_event', 'Spammer Checker');


/*
	Omit PHP closing tag to help avoid accidental output
*/