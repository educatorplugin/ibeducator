<?php
/**
 * Plugin Name: Educator
 * Plugin URI: http://educatorplugin.com/
 * Description: Offer courses to students online.
 * Author: educatorteam
 * Author URI: http://educatorplugin.com/
 * Version: 1.7.1
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ibeducator
 *
 * @package ibeducator
*/

/*
Copyright (C) 2015 http://educatorplugin.com/ - contact@educatorplugin.com

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'IBEDUCATOR_VERSION', '1.7.1' );
define( 'IBEDUCATOR_DB_VERSION', '1.7' );
define( 'IBEDUCATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IBEDUCATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook( __FILE__, array( 'IB_Educator_Main', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'IB_Educator_Main', 'plugin_deactivation' ) );

// Setup autoloader.
require IBEDUCATOR_PLUGIN_DIR . 'includes/Edr/Autoloader.php';
new Edr_Autoloader();

require IBEDUCATOR_PLUGIN_DIR . 'includes/objects/ib-educator-payment.php';
require IBEDUCATOR_PLUGIN_DIR . 'includes/objects/ib-educator-entry.php';
require IBEDUCATOR_PLUGIN_DIR . 'includes/objects/ib-educator-question.php';
require IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator.php';
require IBEDUCATOR_PLUGIN_DIR . 'includes/formatting.php';
require IBEDUCATOR_PLUGIN_DIR . 'includes/functions.php';
require IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-main.php';
require IBEDUCATOR_PLUGIN_DIR . 'includes/shortcodes.php';
require IBEDUCATOR_PLUGIN_DIR . 'includes/filters.php';

// Register the quizzes service.
function edr_get_quizzes_service() { return new Edr_Quizzes(); }

Edr_Manager::add( 'edr_quizzes', 'edr_get_quizzes_service' );

// Setup the memberships feature.
Edr_MembershipsRun::init();

// Setup the post types and taxonomies.
Edr_PostTypes::init();

// Setup Educator.
IB_Educator_Main::init();

// Ajax actions.
Edr_AjaxActions::init();

// Setup account processing (e.g. payment form).
Edr_StudentAccount::init();

// Parse incoming requests (e.g. PayPal IPN).
Edr_RequestDispatcher::init();

if ( is_admin() ) {
	// Setup the Educator's admin.
	Edr_Admin::init();

	// Check whether to run the update script or not.
	function ib_edu_update_check() {
		if ( get_option( 'ib_educator_version' ) != IBEDUCATOR_VERSION ) {
			$install = new Edr_Install();
			$install->activate( false, false );
		}
	}
	add_action( 'init', 'ib_edu_update_check', 9 );
}
