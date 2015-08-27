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
			self::init_by_key( $key );
		}

		return self::$data[ $key ];
	}

	/**
	 * Initialize data or service by key.
	 *
	 * @param string $key
	 */
	protected static function init_by_key( $key ) {
		switch ( $key ) {
			case 'quizzes':
				self::$data['quizzes'] = new Edr_Quizzes();
				break;

			default:
				self::$data[ $key ] = null;
		}
	}
}
