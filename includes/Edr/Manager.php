<?php

class Edr_Manager {
	/**
	 * @var array
	 */
	protected static $data = array();

	/**
	 * Get data or service by key.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function get( $key ) {
		if ( ! array_key_exists( $key, self::$data ) ) {
			throw new Exception( 'This service does not exist.' );
		}

		if ( is_string( self::$data[ $key ] ) || is_array( self::$data[ $key ] ) ) {
			self::$data[ $key ] = call_user_func( self::$data[ $key ] );
		}

		return self::$data[ $key ];
	}

	public static function add( $key, $service ) {
		self::$data[ $key ] = $service;
	}
}

function edr_get_quizzes_service() {
	return new Edr_Quizzes();
}

Edr_Manager::add( 'edr_quizzes', 'edr_get_quizzes_service' );
