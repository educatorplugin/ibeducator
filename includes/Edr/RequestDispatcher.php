<?php

class Edr_RequestDispatcher {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'parse_request', array( __CLASS__, 'process_request' ) );
	}

	/**
	 * Get URL.
	 *
	 * @param string $request
	 * @return string
	 */
	public static function get_url( $request ) {
		$scheme = parse_url( get_option( 'home' ), PHP_URL_SCHEME );

		return esc_url_raw( add_query_arg( array( 'edu-request' => $request ), home_url( '/', $scheme ) ) );
	}

	/**
	 * Process request.
	 *
	 * @param WP $wp
	 */
	public static function process_request( $wp ) {
		if ( ! isset( $wp->query_vars['edu-request'] ) ) {
			return;
		}

		$request = $wp->query_vars['edu-request'];

		/**
		 * @since 1.0.0
		 */
		do_action( 'ib_educator_request_' . sanitize_title( $request ) );

		/**
		 * @since 0.9.0
		 * @deprecated 1.0.0 Use ib_educator_request_{request}
		 * @param string $request
		 */
		do_action( 'ibedu_process_request', $request );

		exit;
	}
}
