<?php

class IB_Educator {
	private static $instance = null;
	private $payments;
	private $entries;
	private $questions;
	private $choices;
	private $answers;
	private $grades;

	/**
	 * Constructor.
	 *
	 * @access private
	 */
	private function __construct() {
		$tables = edr_db_tables();
		$this->payments  = $tables['payments'];
		$this->entries   = $tables['entries'];
		$this->questions = $tables['questions'];
		$this->choices   = $tables['choices'];
		$this->answers   = $tables['answers'];
		$this->grades    = $tables['grades'];
	}

	/**
	 * Get instance.
	 *
	 * @return IB_Educator
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function get_access_status( $course_id, $user_id ) {
		edr_deprecated_function( 'IB_Educator::get_access_status', '1.8.0', 'Edr_Access::get_instance()->get_course_access_status()' );

		return Edr_Access::get_instance()->get_course_access_status( $course_id, $user_id );
	}

	/**
	 * @deprecated 1.3.0
	 */
	public function user_can_pay( $course_id, $user_id ) {
		edr_deprecated_function( 'IB_Educator::user_can_pay', '1.8.0' );

		return in_array( $this->get_access_status( $course_id, $user_id ), array( 'forbidden', 'course_complete' ) );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function add_payment( $data ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->add_payment', '1.8.0' );

		$payment = edr_get_payment();

		if ( ! empty( $data['course_id'] ) ) {
			$payment->course_id = $data['course_id'];
		}
		
		$payment->user_id = $data['user_id'];
		
		if ( ! empty( $data['object_id'] ) ) {
			$payment->object_id = $data['object_id'];
		}
		
		$payment->payment_type = $data['payment_type'];
		$payment->payment_gateway = $data['payment_gateway'];
		$payment->payment_status = $data['payment_status'];
		$payment->amount = $data['amount'];
		$payment->currency = $data['currency'];

		if ( ! empty( $data['tax'] ) ) {
			$payment->tax = $data['tax'];
		}
		
		$payment->save();

		return $payment;
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function get_entry( $args ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->get_entry', '1.8.0', 'Edr_Entries::get_instance()->get_entry' );

		return Edr_Entries::get_instance()->get_entry( $args );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function get_entries( $args, $output_type = 'OBJECT' ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->get_entries', '1.8.0', 'Edr_Entries::get_instance()->get_entries' );

		return Edr_Entries::get_instance()->get_entries( $args, $output_type );
	}

	/**
	 * @deprecated 1.3.0
	 */
	public function get_entries_count( $args = array() ) {
		global $wpdb;

		$sql = "SELECT entry_status, count(1) as num_rows FROM {$this->entries} WHERE 1";

		// Filter by course_id.
		if ( isset( $args['course_id'] ) ) {
			$course_id = array();

			if ( is_array( $args['course_id'] ) ) {
				foreach ( $args['course_id'] as $id ) {
					$course_id[] = absint( $id );
				}
			} else {
				$course_id[] = absint( $args['course_id'] );
			}

			if ( ! empty( $course_id ) ) {
				$sql .= ' AND course_id IN (' . implode( ',', $course_id ) . ')';
			}
		}

		$sql .= ' GROUP BY entry_status';

		return $wpdb->get_results( $sql, OBJECT_K );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function get_student_courses( $user_id ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->get_student_courses', '1.8.0', 'Edr_Courses::get_instance()->get_student_courses' );

		return Edr_Courses::get_instance()->get_student_courses( $user_id );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function get_pending_courses( $user_id ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->get_pending_courses', '1.8.0', 'Edr_Courses::get_instance()->get_pending_courses' );

		return Edr_Courses::get_instance()->get_pending_courses( $user_id );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function get_lessons( $course_id ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->get_lessons', '1.8.0', 'Edr_Courses::get_instance()->get_lessons' );

		return Edr_Courses::get_instance()->get_lessons( $course_id );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function get_num_lessons( $course_id ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->get_num_lessons', '1.8.0', 'Edr_Courses::get_instance()->get_num_lessons' );

		return Edr_Courses::get_instance()->get_num_lessons( $course_id );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function get_payments( $args, $output_type = null ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->get_payments', '1.8.0', 'Edr_Payments::get_instance()->get_payments' );

		return Edr_Payments::get_instance()->get_payments( $args, $output_type );
	}

	/**
	 * @deprecated 1.3.0
	 */
	public function get_payments_count() {
		global $wpdb;

		return $wpdb->get_results( "SELECT payment_status, count(1) as num_rows FROM {$this->payments} GROUP BY payment_status", OBJECT_K );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function get_lecturer_courses( $user_id ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->get_lecturer_courses', '1.8.0', 'Edr_Courses::get_instance()->get_lecturer_courses' );

		return Edr_Courses::get_instance()->get_lecturer_courses( $user_id );
	}

	/**
	 * @deprecated 1.6
	 */
	public function get_questions( $args ) {
		edr_deprecated_function( 'IB_Educator::get_questions', '1.6', 'Edr_Quizzes::get_questions' );

		return Edr_Manager::get( 'edr_quizzes' )->get_questions( $args['lesson_id'] );
	}

	/**
	 * @deprecated 1.6
	 */
	public function get_choices( $lesson_id, $sorted = false ) {
		edr_deprecated_function( 'IB_Educator::get_choices', '1.6', 'Edr_Quizzes::get_choices' );

		return Edr_Manager::get( 'edr_quizzes' )->get_choices( $lesson_id, $sorted );
	}

	/**
	 * @deprecated 1.6
	 */
	public function get_question_choices( $question_id ) {
		edr_deprecated_function( 'IB_Educator::get_question_choices', '1.6', 'Edr_Quizzes::get_question_choices' );

		return Edr_Manager::get( 'edr_quizzes' )->get_question_choices( $question_id );
	}

	/**
	 * @deprecated 1.6
	 */
	public function add_choice( $data ) {
		edr_deprecated_function( 'IB_Educator::add_choice', '1.6', 'Edr_Quizzes::add_choice' );

		return Edr_Manager::get( 'edr_quizzes' )->add_choice( $data );
	}

	/**
	 * @deprecated 1.6
	 */
	public function update_choice( $choice_id, $data ) {
		edr_deprecated_function( 'IB_Educator::update_choice', '1.6', 'Edr_Quizzes::update_choice' );

		return Edr_Manager::get( 'edr_quizzes' )->update_choice( $choice_id, $data );
	}

	/**
	 * @deprecated 1.6
	 */
	public function delete_choice( $choice_id ) {
		edr_deprecated_function( 'IB_Educator::delete_choice', '1.6', 'Edr_Quizzes::delete_choice' );

		return Edr_Manager::get( 'edr_quizzes' )->delete_choice( $choice_id );
	}

	/**
	 * @deprecated 1.6
	 */
	public function delete_choices( $question_id ) {
		edr_deprecated_function( 'IB_Educator::delete_choices', '1.6', 'Edr_Quizzes::delete_choices' );

		return Edr_Manager::get( 'edr_quizzes' )->delete_choices( $question_id );
	}

	/**
	 * @deprecated 1.6
	 */
	public function add_student_answer( $data ) {
		edr_deprecated_function( 'IB_Educator::add_student_answer', '1.6', 'Edr_Quizzes::add_answer' );

		return Edr_Manager::get( 'edr_quizzes' )->add_answer( $data );
	}

	/**
	 * @deprecated 1.6
	 */
	public function get_student_answers( $lesson_id, $entry_id ) {
		edr_deprecated_function( 'IB_Educator::get_student_answers', '1.6', 'Edr_Quizzes::get_answers( int $grade_id )' );

		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT question_id, ID, entry_id, question_id, choice_id, correct, answer_text "
				. "FROM {$this->answers} "
				. "WHERE entry_id = %d AND question_id IN (SELECT question_id FROM {$this->questions} WHERE lesson_id = %d)",
				$entry_id,
				$lesson_id
			),
			OBJECT_K
		);
	}

	/**
	 * @deprecated 1.6
	 */
	public function add_quiz_grade( $data ) {
		edr_deprecated_function( 'IB_Educator::add_quiz_grade', '1.6', 'Edr_Quizzes::add_grade' );

		return Edr_Manager::get( 'edr_quizzes' )->add_grade( $data );
	}

	/**
	 * @deprecated 1.6
	 */
	public function update_quiz_grade( $grade_id, $data ) {
		edr_deprecated_function( 'IB_Educator::update_quiz_grade', '1.6', 'Edr_Quizzes::update_grade' );

		return Edr_Manager::get( 'edr_quizzes' )->update_grade( $grade_id, $data );
	}

	/**
	 * @deprecated 1.6
	 */
	public function is_quiz_submitted( $lesson_id, $entry_id ) {
		edr_deprecated_function( 'IB_Educator::is_quiz_submitted', '1.6', 'Edr_Quizzes::get_grade( int $lesson_id, int $entry_id )' );

		global $wpdb;

		$submitted = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(1) FROM {$this->grades} WHERE lesson_id=%d AND entry_id=%d LIMIT 1",
			$lesson_id,
			$entry_id
		) );

		return ( 1 == $submitted );
	}

	/**
	 * @deprecated 1.6
	 */
	public function get_quiz_grade( $lesson_id, $entry_id ) {
		edr_deprecated_function( 'IB_Educator::get_quiz_grade', '1.6', 'Edr_Quizzes::get_grade' );

		return Edr_Manager::get( 'edr_quizzes' )->get_grade( $lesson_id, $entry_id );
	}

	/**
	 * @deprecated 1.6
	 */
	public function check_quiz_pending( $ids ) {
		edr_deprecated_function( 'IB_Educator::check_quiz_pending', '1.6', 'Edr_Quizzes::check_for_pending_quizzes' );

		return Edr_Manager::get( 'edr_quizzes' )->check_for_pending_quizzes( $ids );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function get_prerequisites( $course_id ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->get_prerequisites', '1.8.0', 'Edr_Courses::get_instance()->get_course_prerequisites' );

		return Edr_Courses::get_instance()->get_course_prerequisites( $course_id );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function check_prerequisites( $course_id, $user_id ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->check_prerequisites', '1.8.0', 'Edr_Courses::get_instance()->check_course_prerequisites' );

		return Edr_Courses::get_instance()->check_course_prerequisites( $course_id, $user_id );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function setup_payment_item( $payment ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->setup_payment_item', '1.8.0', 'Edr_Payments::get_instance()->setup_payment_item' );

		return Edr_Payments::get_instance()->setup_payment_item( $payment );
	}

	/**
	 * @deprecated 1.8.0
	 */
	public function get_billing_data( $user_id ) {
		edr_deprecated_function( 'IB_Educator::get_instance()->get_billing_data', '1.8.0', 'Edr_Payments::get_instance()->get_billing_data' );

		return Edr_Payments::get_instance()->get_billing_data( $user_id );
	}
}

class IBEdu_API {
	public static function get_instance() {
		edr_deprecated_function( 'IBEdu_API::get_instance()', '0.9.0', 'IB_Educator::get_instance()' );

		return IB_Educator::get_instance();
	}
}
