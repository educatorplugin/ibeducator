<?php

class Edr_Quizzes {
	/**
	 * @var string
	 */
	protected $tbl_questions;

	/**
	 * @var string
	 */
	protected $tbl_choices;

	/**
	 * @var string
	 */
	protected $tbl_grades;

	/**
	 * @var string
	 */
	protected $tbl_answers;

	/**
	 * Constructor
	 */
	public function __construct() {
		$tables = ib_edu_table_names();
		$this->tbl_questions = $tables['questions'];
		$this->tbl_choices   = $tables['choices'];
		$this->tbl_grades    = $tables['grades'];
		$this->tbl_answers   = $tables['answers'];
	}

	/**
	 * Get the maximum number of attempts per quiz.
	 *
	 * @param int $lesson_id
	 * @return int
	 */
	public function get_max_attempts_number( $lesson_id ) {
		return get_post_meta( $lesson_id, '_edr_attempts', true );
	}

	/**
	 * Get the number of attempts per quiz per entry.
	 *
	 * @param int $entry_id
	 * @param int $lesson_id
	 * @return int
	 */
	public function get_attempts_number( $lesson_id, $entry_id = null ) {
		global $wpdb;
		$query = 'SELECT count(1) FROM ' . $this->tbl_grades . ' WHERE lesson_id = %d';
		$values = array();
		$values[] = $lesson_id;

		if ( $entry_id ) {
			$query .= ' AND entry_id = %d';
			$values[] = $entry_id;
		} else {
			$query .= ' AND user_id = %d AND entry_id = 0';
			$values[] = get_current_user_id();
		}

		$attempts_number = $wpdb->get_var( $wpdb->prepare( $query, $values ) );

		return $attempts_number;
	}

	/**
	 * Get quiz questions.
	 *
	 * @param int $lesson_id
	 * @return array
	 */
	public function get_questions( $lesson_id ) {
		global $wpdb;
		$query = 'SELECT * FROM ' . $this->tbl_questions
			   . ' WHERE lesson_id = %d'
			   . ' ORDER BY menu_order ASC';

		$questions = $wpdb->get_results( $wpdb->prepare( $query, $lesson_id ) );

		if ( ! empty( $questions ) ) {
			$questions = array_map( 'edr_get_question', $questions );
		}

		return $questions;
	}

	/**
	 * Add a question answer choice.
	 *
	 * @param array $data
	 * @return false|int Inserted ID or false.
	 */
	public function add_choice( $data ) {
		global $wpdb;

		$done = $wpdb->insert(
			$this->tbl_choices,
			array(
				'question_id' => $data['question_id'],
				'choice_text' => $data['choice_text'],
				'correct'     => $data['correct'],
				'menu_order'  => $data['menu_order']
			),
			array( '%d', '%s', '%d', '%d' )
		);

		if ( $done ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update a question answer choice.
	 *
	 * @param int $choice_id
	 * @param array $data
	 * @return false|int Number of updated rows (0 if no rows were updated) or false on error.
	 */
	public function update_choice( $choice_id, $data ) {
		global $wpdb;

		return $wpdb->update(
			$this->tbl_choices,
			array(
				'choice_text' => $data['choice_text'],
				'correct'     => $data['correct'],
				'menu_order'  => $data['menu_order']
			),
			array( 'ID' => $choice_id ),
			array( '%s', '%d', '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Delete question answer choice.
	 *
	 * @param int $choice_id
	 * @return false|int Number of rows deleted or false on error.
	 */
	public function delete_choice( $choice_id ) {
		global $wpdb;

		return $wpdb->delete(
			$this->tbl_choices,
			array( 'ID' => $choice_id ),
			array( '%d' )
		);
	}

	/**
	 * Delete question answer choices given a question ID.
	 *
	 * @param int $question_id
	 * @return false|int Number of rows deleted or false on error.
	 */
	public function delete_choices( $question_id ) {
		global $wpdb;

		return $wpdb->delete(
			$this->tbl_choices,
			array( 'question_id' => $question_id ),
			array( '%d' )
		);
	}

	/**
	 * Get available choices for a multiple choice question.
	 *
	 * @param int $question_id
	 * @return array
	 */
	public function get_question_choices( $question_id ) {
		global $wpdb;

		$query = 'SELECT ID, choice_text, correct, menu_order'
			   . ' FROM ' . $this->tbl_choices
			   . ' WHERE question_id = %d'
			   . ' ORDER BY menu_order ASC';

		return $wpdb->get_results( $wpdb->prepare( $query, $question_id ), OBJECT_K );
	}

	/**
	 * Get all choices for a given lesson.
	 *
	 * @param int $lesson_id
	 * @return array
	 */
	public function get_choices( $lesson_id, $sorted = false ) {
		global $wpdb;
		$query = 'SELECT * FROM ' . $this->tbl_choices
			   . ' WHERE question_id IN (SELECT question_id FROM ' . $this->tbl_questions . ' WHERE lesson_id = %d)'
			   . ' ORDER BY menu_order ASC';
		$choices = $wpdb->get_results( $wpdb->prepare( $query, $lesson_id ) );

		if ( $sorted && ! empty( $choices ) ) {
			$sorted_arr = array();

			foreach ( $choices as $row ) {
				if ( ! isset( $sorted_arr[ $row->question_id ] ) ) {
					$sorted_arr[ $row->question_id ] = array();
				}

				$sorted_arr[ $row->question_id ][ $row->ID ] = $row;
			}

			return $sorted_arr;
		}

		return $choices;
	}

	/**
	 * Get the latest grade for a given quiz.
	 *
	 * @param int $lesson_id
	 * @param int $entry_id
	 * @return object
	 */
	public function get_grade( $lesson_id, $entry_id = null ) {
		global $wpdb;
		$query = 'SELECT * FROM ' . $this->tbl_grades . ' WHERE lesson_id = %d';
		$values = array();
		$values[] = $lesson_id;

		if ( $entry_id ) {
			$query .= ' AND entry_id = %d';
			$values[] = $entry_id;
		} else {
			$query .= ' AND user_id = %d AND entry_id = 0';
			$values[] = get_current_user_id();
		}

		$query .= ' ORDER BY ID DESC';

		return $wpdb->get_row( $wpdb->prepare( $query, $values ) );
	}

	/**
	 * Add grade for a quiz.
	 *
	 * @param array $data
	 * @return false|int Grade ID or false on error.
	 */
	public function add_grade( $data ) {
		global $wpdb;

		$done = $wpdb->insert(
			$this->tbl_grades,
			array(
				'lesson_id' => $data['lesson_id'],
				'entry_id'  => $data['entry_id'],
				'user_id'   => isset( $data['user_id'] ) ? $data['user_id'] : 0,
				'grade'     => $data['grade'],
				'status'    => $data['status'],
			),
			array( '%d', '%d', '%d', '%f', '%s' )
		);

		if ( $done ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update quiz grade.
	 *
	 * @param int $grade_id
	 * @param array $data
	 * @return false|int Number of updated rows or false on error.
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
	 * Get a student's answers to a given quiz.
	 *
	 * @param int $grade_id
	 * @return array
	 */
	public function get_answers( $grade_id ) {
		global $wpdb;
		$query = 'SELECT question_id, ID, grade_id, entry_id, question_id, choice_id, correct, answer_text'
			   . ' FROM ' . $this->tbl_answers
			   . ' WHERE grade_id = %d';

		return $wpdb->get_results( $wpdb->prepare( $query, $grade_id ), OBJECT_K );
	}

	/**
	 * Add an answer to a question.
	 *
	 * @param array $data
	 * @return false|int Answer ID or false on error.
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

		$done = $wpdb->insert( $this->tbl_answers, $insert_data, $data_format );

		if ( $done ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get the entries with ungraded quizzes.
	 *
	 * @param array $ids
	 * @return array
	 */
	public function check_for_pending_quizzes( $ids ) {
		global $wpdb;

		if ( empty( $ids ) ) {
			return array();
		}

		$ids = implode( ',', array_map( 'absint', $ids ) );

		$entries = $wpdb->get_col( "SELECT entry_id FROM $this->tbl_grades WHERE status = 'pending' AND entry_id IN ($ids) GROUP BY entry_id" );

		return $entries;
	}
}
