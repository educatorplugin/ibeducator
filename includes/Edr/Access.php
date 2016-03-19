<?php

class Edr_Access {
	protected static $instance = null;
	protected $payments;
	protected $entries;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct() {
		$tables = edr_db_tables();

		$this->payments = $tables['payments'];
		$this->entries = $tables['entries'];
	}

	public function get_lesson_access( $lesson_id ) {
		return get_post_meta( $lesson_id, '_ib_educator_access', true );
	}

	public function get_course_access_status( $course_id, $user_id ) {
		global $wpdb;
		$status = '';
		$sql = "SELECT course_id, user_id, entry_status FROM $this->entries WHERE course_id = %d AND user_id = %d";
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $course_id, $user_id ) );

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( 'complete' == $result->entry_status ) {
					$status = 'course_complete';
				} elseif ( 'pending' == $result->entry_status ) {
					$status = 'pending_entry';
				} elseif ( 'inprogress' == $result->entry_status ) {
					$status = 'inprogress';
				}
			}
		}

		if ( empty( $status ) ) {
			$status = 'forbidden';
		}

		return apply_filters( 'ib_educator_access_status', $status, $course_id, $user_id );
	}

	public function can_study_lesson( $lesson_id ) {
		$lesson_access = $this->get_lesson_access( $lesson_id );
		$user_id = get_current_user_id();
		$access = false;

		if ( 'public' == $lesson_access ) {
			$access = true;
		} elseif ( $user_id ) {
			if ( 'logged_in' == $lesson_access ) {
				$access = true;
			} else {
				$course_id = Edr_Courses::get_instance()->get_course_id( $lesson_id );

				if ( $course_id ) {
					$access_status = $this->get_course_access_status( $course_id, $user_id );

					if ( in_array( $access_status, array( 'inprogress', 'course_complete' ) ) ) {
						$access = true;
					}
				}
			}
		}

		return $access;
	}
}
