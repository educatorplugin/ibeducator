<?php

/**
 * Test Edr_Quizzes class.
 */
class Test_Edr_Quizzes extends IB_Educator_Tests {
	public function setUp() {
		parent::setUp();
		$this->basicSetUp();

		// Add questions.
		$this->addQuestion( array(
			'lesson_id'     => $this->lessons[0],
			'question'      => 'Question #1',
			'question_type' => 'multiplechoice',
			'menu_order'    => 0
		) );

		$this->addQuestion( array(
			'lesson_id'     => $this->lessons[0],
			'question'      => 'Question #2',
			'question_type' => 'multiplechoice',
			'menu_order'    => 1
		) );

		// Set attempts number to 3.
		update_post_meta( $this->lessons[0], '_edr_attempts', 3 );

		// Add 1 attempt.
		$this->addAttempt();
	}

	public function addQuestion( $data ) {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );
		$question = IB_Educator_Question::get_instance();

		$question->lesson_id = $data['lesson_id'];
		$question->question = $data['question'];
		$question->question_type = $data['question_type'];
		$question->menu_order = $data['menu_order'];

		$question->save();

		if ( 'multiplechoice' == $question->question_type ) {
			$choices = array(
				array(
					'question_id' => $question->ID,
					'choice_text' => 'Choice #1',
					'correct'     => 1,
					'menu_order'  => 0,
				),
				array(
					'question_id' => $question->ID,
					'choice_text' => 'Choice #2',
					'correct'     => 0,
					'menu_order'  => 1,
				),
				array(
					'question_id' => $question->ID,
					'choice_text' => 'Choice #3',
					'correct'     => 0,
					'menu_order'  => 2,
				)
			);

			foreach ( $choices as $choice ) {
				$quizzes->add_choice( $choice );
			}
		}

		return $question->ID;
	}

	public function addAttempt() {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );
		$grade_id = $quizzes->add_grade( array(
			'lesson_id' => $this->lessons[0],
			'entry_id'  => $this->entries['inprogress'],
			'grade'     => 0,
			'status'    => 'pending',
		) );
		$questions = $quizzes->get_questions( $this->lessons[0] );
		$choices = $quizzes->get_choices( $this->lessons[0], true );

		foreach ( $questions as $question ) {
			$choice = reset( $choices[ $question->ID ] );

			$quizzes->add_answer( array(
				'question_id' => $question->ID,
				'grade_id'    => $grade_id,
				'entry_id'    => $this->entries['inprogress'],
				'correct'     => $choice->correct,
				'choice_id'   => $choice->ID,
			) );
		}
	}

	public function testGetMaxAttemptsNumber() {
		$max_attempts_number = Edr_Manager::get( 'edr_quizzes' )->get_max_attempts_number( $this->lessons[0] );

		$this->assertEquals( 3, $max_attempts_number );
	}

	public function testGetAttemptsNumber() {
		$attempts_number = Edr_Manager::get( 'edr_quizzes' )->get_attempts_number( $this->entries['inprogress'], $this->lessons[0] );

		$this->assertEquals( 1, $attempts_number );
	}

	public function testGetQuestions() {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );
		$questions = $quizzes->get_questions( $this->lessons[0] );

		$this->assertEquals( array(
			'count'         => 2,
			'lesson_id'     => $this->lessons[0],
			'question'      => 'Question #1',
			'question_type' => 'multiplechoice',
			'menu_order'    => 0,
		), array(
			'count'         => count( $questions ),
			'lesson_id'     => $questions[0]->lesson_id,
			'question'      => $questions[0]->question,
			'question_type' => $questions[0]->question_type,
			'menu_order'    => $questions[0]->menu_order,
		) );
	}

	public function testUpdateChoice() {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );
		$choices = $quizzes->get_choices( $this->lessons[0] );
		$choice1 = reset( $choices );

		$affected_rows = $quizzes->update_choice( $choice1->ID, array(
			'choice_text' => 'Choice #1 (Updated)',
			'correct'     => 1,
			'menu_order'  => 0,
		) );

		$this->assertEquals( 1, $affected_rows );
	}

	public function testAddDeleteChoice() {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );
		$questions = $quizzes->get_questions( $this->lessons[0] );
		$question1 = reset( $questions );

		$choice_id = $quizzes->add_choice( array(
			'question_id' => $question1->ID,
			'choice_text' => 'This choice should be deleted',
			'correct'     => 0,
			'menu_order'  => 3,
		) );

		$question_choices = $quizzes->get_question_choices( $question1->ID );
		
		// Was the choice added?
		$this->assertTrue( array_key_exists( $choice_id, $question_choices ) );

		$quizzes->delete_choice( $choice_id );

		$question_choices = $quizzes->get_question_choices( $question1->ID );

		// Was the choice deleted?
		$this->assertFalse( array_key_exists( $choice_id, $question_choices ) );
	}

	public function testDeleteChoices() {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );

		$new_question_id = $this->addQuestion( array(
			'lesson_id'     => $this->lessons[0],
			'question'      => 'Another question?',
			'question_type' => 'multiplechoice',
			'menu_order'    => 1
		) );

		$question_choices = $quizzes->get_question_choices( $new_question_id );

		$this->assertTrue( count( $question_choices ) > 0 );

		$quizzes->delete_choices( $new_question_id );

		$question_choices = $quizzes->get_question_choices( $new_question_id );

		$this->assertTrue( count( $question_choices ) == 0 );
	}

	public function testGetGrade() {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );
		$grade = $quizzes->get_grade( $this->lessons[0], $this->entries['inprogress'] );

		$this->assertEquals( array(
			'lesson_id' => $this->lessons[0],
			'entry_id'  => $this->entries['inprogress'],
			'grade'     => 0.00,
			'status'    => 'pending',
		), array(
			'lesson_id' => $grade->lesson_id,
			'entry_id'  => $grade->entry_id,
			'grade'     => $grade->grade,
			'status'    => $grade->status,
		) );
	}

	public function testCheckForPendingQuizzes() {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );
		$pending_entry_ids = $quizzes->check_for_pending_quizzes( array( $this->entries['inprogress'] ) );

		$this->assertTrue( in_array( $this->entries['inprogress'], $pending_entry_ids ) );
	}

	public function testUpdateGrade() {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );
		$grade = $quizzes->get_grade( $this->lessons[0], $this->entries['inprogress'] );

		$quizzes->update_grade( $grade->ID, array(
			'grade'  => 99.99,
			'status' => 'approved',
		) );

		$grade = $quizzes->get_grade( $this->lessons[0], $this->entries['inprogress'] );

		$this->assertEquals( array(
			'grade'  => 99.99,
			'status' => 'approved',
		), array(
			'grade' => $grade->grade,
			'status' => $grade->status,
		) );
	}

	public function testGetAnswers() {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );
		$grade = $quizzes->get_grade( $this->lessons[0], $this->entries['inprogress'] );
		$answers = $quizzes->get_answers( $grade->ID );

		$this->assertTrue( count( $answers ) == 2 );
	}

	public function testAddAnswer() {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );
		$questions = $quizzes->get_questions( $this->lessons[0] );
		$question1 = reset( $questions );
		$choices = $quizzes->get_choices( $this->lessons[0], true );
		$choice1 = reset( $choices[ $question1->ID ] );

		$grade_id = $quizzes->add_grade( array(
			'lesson_id' => $this->lessons[0],
			'entry_id'  => $this->entries['inprogress'],
			'grade'     => 80.29,
			'status'    => 'approved',
		) );

		$answer_data = array(
			'question_id' => $question1->ID,
			'grade_id'    => $grade_id,
			'entry_id'    => $this->entries['inprogress'],
			'correct'     => $choice1->correct,
			'choice_id'   => $choice1->ID,
			'answer_text' => 'abc',
		);

		$quizzes->add_answer( $answer_data );

		// Get original answer data as object.
		$original_answer_object = new stdClass();
		foreach ( $answer_data as $key => $value ) {$original_answer_object->$key = $value;}

		// Get the created answer.
		$answers = $quizzes->get_answers( $grade_id );
		$answer = reset( $answers );
		unset( $answer->ID );

		$this->assertEquals( $original_answer_object, $answer );
	}
}
