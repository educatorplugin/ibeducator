<?php

class Edr_View {
	/**
	 * Get plugin templates directory.
	 *
	 * @return string
	 */
	public static function templates_dir() {
		return IBEDUCATOR_PLUGIN_DIR . 'templates';
	}

	public static function locate_template_path( $name ) {
		$file_path = trailingslashit( get_stylesheet_directory() ) . 'ibeducator/' . $name;

		if ( file_exists( $file_path ) ) {
			return $file_path;
		} else {
			$file_path = trailingslashit( get_template_directory() ) . 'ibeducator/' . $name;

			if ( file_exists( $file_path ) ) {
				return $file_path;
			} else {
				$file_path = trailingslashit( self::templates_dir() ) . $name;

				if ( file_exists( $file_path ) ) {
					return $file_path;
				}
			}
		}

		return '';
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

	public static function the_template( $template_name, $vars = null ) {
		$template_path = self::locate_template_path( $template_name . '.php' );

		if ( '' != $template_path ) {
			if ( is_array( $vars ) ) {
				extract( $vars );
			}

			include $template_path;
		}
	}
}
