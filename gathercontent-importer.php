<?php
/**
 * Plugin Name: GatherContent Importer
 * Plugin URI:  http://www.gathercontent.com
 * Description: Imports items from GatherContent to your wordpress site
 * Version:     3.0.0
 * Author:      GatherContent
 * Author URI:  http://www.gathercontent.com
 * Text Domain: gathercontent-import
 * Domain Path: /languages
 * License:     GPL-2.0+
 */

/**
 * Copyright (c) 2016 GatherContent (email : support@gathercontent.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using yo wp-make:plugin
 * Copyright (c) 2016 10up, LLC
 * https://github.com/10up/generator-wp-make
 */

// Useful global constants
define( 'GATHERCONTENT_VERSION', '3.0.0' );
define( 'GATHERCONTENT_URL',     plugin_dir_url( __FILE__ ) );
define( 'GATHERCONTENT_PATH',    dirname( __FILE__ ) . '/' );
define( 'GATHERCONTENT_INC',     GATHERCONTENT_PATH . 'includes/' );

// Include files
require_once GATHERCONTENT_INC . 'functions/core.php';


// Activation/Deactivation
register_activation_hook( __FILE__, '\TenUp\GatherContentImporter\Core\activate' );
register_deactivation_hook( __FILE__, '\TenUp\GatherContentImporter\Core\deactivate' );

// Bootstrap
TenUp\GatherContentImporter\Core\setup();
