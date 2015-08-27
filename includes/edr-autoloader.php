<?php

if ( ! defined( 'ABSPATH' ) ) exit();

class Edr_Autoloader {
	public function __construct() {
		$this->plugin_dir = IBEDUCATOR_PLUGIN_DIR;

		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}

		spl_autoload_register( array( $this, 'autoload' ) );
	}

	public function get_file_name( $class ) {
		return str_replace( '_', '-', strtolower( $class ) ) . '.php';
	}

	public function autoload( $class ) {
		$file = '';

		if ( 0 === strpos( $class, 'Edr_' ) ) {
			$file = $this->plugin_dir . 'includes/' . $this->get_file_name( $class );
		}

		if ( $file && is_readable( $file ) ) {
			include_once $file;
		}
	}
}

new Edr_Autoloader();
