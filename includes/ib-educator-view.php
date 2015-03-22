<?php

class IB_Educator_View {
	/**
	 * Get plugin templates directory.
	 *
	 * @return string
	 */
	public static function templates_dir() {
		return IBEDUCATOR_PLUGIN_DIR . 'templates';
	}

	/**
	 * Locate template file.
	 *
	 * @param array $template_name
	 * @return string
	 */
	public static function locate_template( $template_names ) {
		$located = false;

		foreach ( $template_names as $name ) {
			if ( file_exists( trailingslashit( get_stylesheet_directory() ) . 'ibeducator/' . $name ) ) {
				$located = trailingslashit( get_stylesheet_directory() ) . 'ibeducator/' . $name;
				break;
			}

			if ( file_exists( trailingslashit( get_template_directory() ) . 'ibeducator/' . $name ) ) {
				$located = trailingslashit( get_template_directory() ) . 'ibeducator/' . $name;
				break;
			}

			if ( file_exists( trailingslashit( self::templates_dir() ) . $name ) ) {
				$located = trailingslashit( self::templates_dir() ) . $name;
				break;
			}
		}

		return $located;
	}

	/**
	 * Output template.
	 *
	 * @param string $template_name
	 * @param string $suffix
	 */
	public static function template_part( $template_name, $suffix = '' ) {
		$template_names = array();

		if ( $suffix ) {
			$template_names[] = $template_name . '-' . $suffix . '.php';
		}

		$template_names[] = $template_name . '.php';

		$template = self::locate_template( $template_names );

		if ( $template ) {
			load_template( $template, false );
		}
	}
}