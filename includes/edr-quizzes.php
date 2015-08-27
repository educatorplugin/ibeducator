<?php

class Edr_Quizzes {
	protected $tbl_questions;
	protected $tbl_choices;
	protected $tbl_grades;
	protected $tbl_answers;

	public function __construct() {
		$tables = ib_edu_table_names();
		$this->tbl_questions = $tables['questions'];
		$this->tbl_choices   = $tables['choices'];
		$this->tbl_grades    = $tables['grades'];
		$this->tbl_answers   = $tables['answers'];
	}

	public function get_max_attempts_number( $lesson_id ) {
		return get_post_meta( $lesson_id, '_edr_attempts', true );
	}

	public function get_attempts_number( $entry_id, $lesson_id ) {
		global $wpdb;

		$query = 'SELECT count(1) FROM ' . $this->tbl_grades
			   . ' WHERE entry_id = %d AND lesson_id = %d';

		$attempts_number = $wpdb->get_var( $wpdb->prepare( $query, $entry_id, $lesson_id ) );

		return $attempts_number;
	}

	/**
	 * !!!REPLACES IB_Educator::get_questions()
	 */
	public function get_questions( $lesson_id ) {
		global $wpdb;
		$query = 'SELECT * FROM ' . $this->tbl_questions
			   . ' WHERE lesson_id = %d'
			   . ' ORDER BY menu_order ASC';

		$questions = $wpdb->get_results( $wpdb->prepare( $query, $lesson_id ) );

		if ( $questions ) {
			$questions = array_map( array( 'IB_Educator_Question', 'get_instance' ), $questions );
		}

		return $questions;
	}

	/**
	 * !!!REPLACES IB_Educator::get_choices()
	 */
	public function get_choices( $lesson_id, $sorted = false ) {
		global $wpdb;
		$query = 'SELECT * FROM ' . $this->tbl_choices
			   . ' WHERE question_id IN (SELECT question_id FROM ' . $this->tbl_questions . ' WHERE lesson_id = %d)'
			   . ' ORDER BY menu_order ASC';
		$choices = $wpdb->get_results( $wpdb->prepare( $query, $lesson_id ) );

		if ( ! $sorted ) {
			return $choices;
		}

		if ( $choices ) {
			$sorted_arr = array();

			foreach ( $choices as $row ) {
				if ( ! isset( $sorted_arr[ $row->question_id ] ) ) {
					$sorted_arr[ $row->question_id ] = array();
				}

				$sorted_arr[ $row->question_id ][ $row->ID ] = $row;
			}

			return $sorted_arr;
		}

		return false;
	}

	/**
	 * !!!REPLACES IB_Educator::get_quiz_grade()
	 * @return array|null
	 */
	public function get_grade( $lesson_id, $entry_id ) {
		global $wpdb;
		$query = 'SELECT * FROM ' . $this->tbl_grades
			   . ' WHERE lesson_id = %d AND entry_id = %d'
			   . ' ORDER BY ID DESC';

		return $wpdb->get_row( $wpdb->prepare( $query, $lesson_id, $entry_id ) );
	}

	/**
	 * !!!REPLACES IB_Educator::add_quiz_grade( $grade_data )
	 */
	public function add_grade( $data ) {
		global $wpdb;

		$done = $wpdb->insert(
			$this->tbl_grades,
			array(
				'lesson_id' => $data['lesson_id'],
				'entry_id'  => $data['entry_id'],
				'grade'     => $data['grade'],
				'status'    => $data['status'],
			),
			array( '%d', '%d', '%f', '%s' )
		);

		if ( $done ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * !!!REPLACES IB_Educator::update_quiz_grade( $grade_data )
	 */
	public function update_grade( $grade_id, $data ) {
		global $wpdb;
		$insert_data = array();
		$insert_format = array();

		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'grade':
					$insert_data[ $key ] = $value;
					$insert_format[] = '%f';
					break;

				case 'status':
					$insert_data[ $key ] = $value;
					$insert_format[] = '%s';
					break;
			}
		}

		return $wpdb->update(
			$this->tbl_grades,
			$insert_data,
			array( 'ID' => $grade_id ),
			$insert_format,
			array( '%d' )
		);
	}

	/**
	 * !!!REPLACES IB_Educator::get_student_answers()
	 * @return array
	 */
	public function get_answers( $grade_id ) {
		global $wpdb;
		$query = 'SELECT question_id, ID, entry_id, question_id, choice_id, correct, answer_text FROM ' . $this->tbl_answers
			   . ' WHERE grade_id = %d';

		return $wpdb->get_results( $wpdb->prepare( $query, $grade_id ), OBJECT_K );
	}

	/**
	 * !!!REPLACES IB_Educator::add_student_answer( $grade_data )
	 */
	public function add_answer( $data ) {
		global $wpdb;
		$insert_data = array(
			'question_id' => $data['question_id'],
			'entry_id'    => $data['entry_id'],
			'grade_id'    => $data['grade_id'],
			'correct'     => $data['correct'],
		);
		$data_format = array( '%d', '%d', '%d', '%d' );

		if ( isset( $data['choice_id'] ) ) {
			$insert_data['choice_id'] = $data['choice_id'];
			$data_format[] = '%d';
		}
		
		if ( isset( $data['answer_text'] ) ) {
			$insert_data['answer_text'] = $data['answer_text'];
			$data_format[] = '%s';
		}

		return $wpdb->insert( $this->tbl_answers, $insert_data, $data_format );
	}
}
