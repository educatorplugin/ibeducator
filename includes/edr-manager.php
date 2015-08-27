<?php

class Edr_Manager {
	protected static $data = array();

	public static function get( $key ) {
		if ( ! array_key_exists( $key, self::$data ) ) {
			self::init_by_key( $key );
		}

		return self::$data[ $key ];
	}

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
