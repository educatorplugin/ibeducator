<?php
$api = IB_Educator::get_instance();
$lesson_id = get_the_ID();

// Get entry data for the current student. Entry status must be "inprogress".
$entry = $api->get_entry( array(
	'user_id'      => get_current_user_id(),
	'course_id'    => ib_edu_get_course_id( $lesson_id ),
	'entry_status' => 'inprogress'
) );

if ( ! $entry ) {
	return;
}

$quizzes = Edr_Manager::get( 'quizzes' );
$max_attempts_number = $quizzes->get_max_attempts_number( $lesson_id );
$attempts_number = $quizzes->get_attempts_number( $entry->ID, $lesson_id );
$questions = $quizzes->get_questions( $lesson_id );
?>

<?php if ( $questions ) : ?>
	<?php
		$message = get_query_var( 'edu-message' );

		if ( ! $message ) {
			$message = ib_edu_message( 'quiz' );
		}

		if ( $message ) {
			switch ( $message ) {
				case 'empty-answers':
					echo '<div class="ib-edu-message error">' . __( 'Please answer all questions before submitting the quiz.', 'ibeducator' ) . '</div>';
					break;

				case 'quiz-submitted':
					echo '<div class="ib-edu-message success">' . __( 'Thank you. The quiz has been accepted.', 'ibeducator' ) . '</div>';
					break;
			}
		}

		$do_quiz = $attempts_number < $max_attempts_number;
		$grade = $quizzes->get_grade( $lesson_id, $entry->ID );

		if ( $grade && $do_quiz ) {
			$do_quiz = isset( $_GET['try_again'] ) && 'true' == $_GET['try_again'];
		}

		if ( $do_quiz ) {
			$answers = isset( $_POST['answers'] ) && is_array( $_POST['answers'] ) ? $_POST['answers'] : array();
		} else {
			$answers = $quizzes->get_answers( $grade->ID );
		}
	?>

	<section id="ib-edu-quiz" class="<?php echo ( $quiz_submitted ) ? 'ib-edu-quiz-complete' : 'ib-edu-quiz-inprogress'; ?>">
		<?php if ( ! $do_quiz && $grade ) : ?>
			<section class="ib-edu-quiz-grade">
				<h3><?php _e( 'Quiz Grade', 'ibeducator' ); ?></h3>
				<p class="grade">
					<?php
						if ( 'approved' == $grade->status ) {
							printf( __( 'You scored %s for this quiz.', 'ibeducator' ), '<strong>' . ib_edu_format_grade( $grade->grade ) . '</strong>' );
						} else {
							_e( 'Your grade is pending.', 'ibeducator' );
						}
					?>
				</p>
			</section>
		<?php endif; ?>

		<h3 class="ib-edu-quiz-title"><?php _e( 'Quiz', 'ibeducator' ); ?></h3>

		<div class="edr-attempts">
			<p class="attempt-num">
				<?php
					$current_attempt = ( $do_quiz ) ? $attempts_number + 1 : $attempts_number;

					printf( __( 'Attempt %1$d of %2$d', 'ibeducator' ), $current_attempt, $max_attempts_number );
				?>
			</p>
			<?php if ( ! $do_quiz && $attempts_number < $max_attempts_number ) : ?>
				<p class="try-again">
					<a href="<?php echo esc_url( add_query_arg( 'try_again', 'true', get_permalink() ) ); ?>#ib-edu-quiz"><?php _e( 'Try again', 'ibeducator' ); ?></a>
				</p>
			<?php endif; ?>
		</div>

		<form id="ib-edu-quiz-form" class="ib-edu-form" method="post" action="<?php echo esc_url( ib_edu_get_endpoint_url( 'edu-action', 'submit-quiz', get_permalink() ) ); ?>">
			<?php wp_nonce_field( 'ibedu_submit_quiz_' . $lesson_id ); ?>
			<input type="hidden" name="submit_quiz" value="1">

			<div class="ib-edu-questions">
				<?php
					$choices = $quizzes->get_choices( $lesson_id, true );

					foreach ( $questions as $question ) {
						if ( 'multiplechoice' == $question->question_type ) {
							// Multiple Choice Question.

							// Check if this question has the answer choices.
							if ( ! $choices || ! isset( $choices[ $question->ID ] ) ) {
								continue;
							}
							
							$answers_html = '';

							// Output the answers.
							if ( $do_quiz ) {
								foreach ( $choices[ $question->ID ] as $choice ) {
									$checked = '';

									if ( isset( $answers[ $question->ID ] ) && $choice->ID == $answers[ $question->ID ] ) {
										$checked = ' checked="checked"';
									}

									$answers_html .= '<li><label><input type="radio" name="answers[' . absint( $question->ID ) . ']" value="' . esc_attr( $choice->ID ) .'"' . $checked . '> ' . esc_html( $choice->choice_text ) . '</label></li>';
								}
							} else {
								$user_answer = isset( $answers[ $question->ID ] ) ? $answers[ $question->ID ] : null;

								if ( null === $user_answer ) {
									// This question was probably added after this quiz submission.
									continue;
								}

								foreach ( $choices[ $question->ID ] as $choice ) {
									$choice_class = '';
									$check = '';

									if ( 1 == $choice->correct ) {
										// Correct answer.
										$choice_class = 'correct';
										$check = '<span class="custom-radio correct checked"></span>';
									} else if ( $user_answer && $choice->ID == $user_answer->choice_id && ! $choice->correct ) {
										// The student's answer is wrong.
										$choice_class = 'wrong';
										$check = '<span class="custom-radio wrong checked"></span>';
									}

									$answers_html .= '<li' . ( ! empty( $choice_class ) ? ' class="' . $choice_class . '"' : '' ) . '><label>' . $check . esc_html( $choice->choice_text ) . '</label></li>';
								}
							}

							echo '<div class="ib-edu-question">';
							echo '<div class="label">' . esc_html( $question->question ) . '</div>';
							echo '<ul class="ib-edu-answers">' . $answers_html . '</ul></div>';
						} else if ( 'writtenanswer' == $question->question_type ) {
							// Written Answer Question.

							echo '<div class="ib-edu-question">';
							echo '<div class="label">' . esc_html( $question->question ) . '</div>';

							if ( $do_quiz ) {
								$user_answer = isset( $answers[ $question->ID ] ) ? stripslashes( $answers[ $question->ID ] ) : '';

								echo '<div class="ib-edu-question-answer">'
									. '<textarea name="answers[' . absint( $question->ID ) . ']" cols="50" rows="3">'
									. esc_textarea( $user_answer )
									. '</textarea>'
									. '</div>';
							} else {
								$user_answer = isset( $answers[ $question->ID ] ) ? $answers[ $question->ID ] : null;

								if ( $user_answer ) {
									echo '<div class="ib-edu-question-answer">' . esc_html( $user_answer->answer_text ) . '</div>';
								}
							}

							echo '</div>';
						}
					}
				?>
			</div>

			<?php if ( $do_quiz ) : ?>
				<div class="ib-edu-buttons">
					<button class="ib-edu-button" type="submit"><?php _e( 'Submit', 'ibeducator' ); ?></button>
				</div>
			<?php endif; ?>
		</form>
	</section>
<?php endif; ?>