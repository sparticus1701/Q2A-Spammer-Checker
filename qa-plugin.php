<?php

/*
	Spammer Checker 1.0.0 (c) 2011, Sawtooth Software, Inc.

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
*/

/*
	Plugin Name: Spammer Checker
	Plugin URI: 
	Plugin Description: Provides options for checking whether an IP or email is a known spammer
	Plugin Version: 1.0
	Plugin Date: 2011-11-14
	Plugin Author: Walter Williams
	Plugin Author URI: 
	Plugin License: GPLv2
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