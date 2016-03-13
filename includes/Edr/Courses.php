<?php

class Edr_Courses {
	protected static $instance = null;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct() {}

	/**
	 * Get course id.
	 *
	 * @param int $lesson_id
	 * @return int
	 */
	public function get_course_id( $lesson_id ) {
		return intval( get_post_meta( $lesson_id, '_ibedu_course', true ) );
	}
}
