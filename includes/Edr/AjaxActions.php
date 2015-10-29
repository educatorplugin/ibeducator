<?php

class Edr_AjaxActions {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'wp_ajax_ib_edu_calculate_tax', array( __CLASS__, 'ajax_calculate_tax' ) );
		add_action( 'wp_ajax_nopriv_ib_edu_calculate_tax', array( __CLASS__, 'ajax_calculate_tax' ) );
		add_action( 'wp_ajax_ib_edu_get_states', array( __CLASS__, 'ajax_get_states' ) );
		add_action( 'wp_ajax_nopriv_ib_edu_get_states', array( __CLASS__, 'ajax_get_states' ) );
	}

	/**
	 * Calculate tax.
	 */
	public static function ajax_calculate_tax() {
		if ( ! isset( $_GET['country'] ) || ! isset( $_GET['object_id'] ) ) {
			exit;
		}

		$object = get_post( intval( $_GET['object_id'] ) );

		if ( ! $object || ! in_array( $object->post_type, array( 'ib_educator_course', 'ib_edu_membership' ) ) ) {
			exit;
		}

		$args = array();
		$args['country'] = $_GET['country'];
		$args['state'] = isset( $_GET['state'] ) ? $_GET['state'] : '';

		echo Edr_StudentAccount::payment_info( $object, $args );
		exit;
	}

	/**
	 * Get states.
	 */
	public static function ajax_get_states() {
		if ( empty( $_GET['country'] ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ib_edu_get_states' ) ) {
			exit;
		}

		$country = preg_replace( '/[^a-z]+/i', '', $_GET['country'] );
		$edu_countries = Edr_Countries::get_instance();
		$states = $edu_countries->get_states( $country );

		$json = '[';
		$i = 0;

		foreach ( $states as $scode => $sname ) {
			if ( $i > 0 ) $json .= ',';
			$json .= '{"code": ' . json_encode( esc_html( $scode ) ) . ',"name":' . json_encode( esc_html( $sname ) ) . '}';
			++$i;
		}

		$json .= ']';

		echo $json;
		exit;
	}
}
