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

	/**
	 * Add a service.
	 *
	 * @param string $key
	 * @param mixed $service
	 */
	public static function add( $key, $service ) {
		self::$data[ $key ] = $service;
	}
}
