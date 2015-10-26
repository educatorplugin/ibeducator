<?php

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	// Set plugin options.
	update_option( 'ib_educator_learning', array(
		'lesson_comments' => 1,
	) );

	require dirname( __FILE__ ) . '/../../ibeducator.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

/* CUSTOM */
require dirname( __FILE__ ) . '/ib-educator-tests.php';

// Activate and setup the plugin.
activate_plugin( 'ibeducator/ibeducator.php' );
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/edr-install.php';
$ibe_install = new Edr_Install();
$ibe_install->activate();