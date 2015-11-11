<?php

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
	 * @param string $class_name
	 * @return string
	 */
	public function get_file_path( $class_name ) {
		return str_replace( '_', '/', $class_name ) . '.php';
	}

	/**
	 * Autoload various classes.
	 *
	 * @param string $class_name
	 */
	public function autoload( $class_name ) {
		$file = '';

		if ( 0 === strpos( $class_name, 'Edr_' ) ) {
			$file = $this->plugin_dir . 'includes/' . $this->get_file_path( $class_name );
		}

		if ( $file && is_readable( $file ) ) {
			require_once $file;
		}
	}
}
