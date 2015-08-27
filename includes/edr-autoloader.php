<?php

if ( ! defined( 'ABSPATH' ) ) exit();

class Edr_Autoloader {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->plugin_dir = IBEDUCATOR_PLUGIN_DIR;

		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}

		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Get file name given a class name.
	 *
	 * @param string $class
	 * @return string
	 */
	public function get_file_name( $class ) {
		return str_replace( '_', '-', strtolower( $class ) ) . '.php';
	}

	/**
	 * Autoload various classes.
	 *
	 * @param string $class
	 */
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
