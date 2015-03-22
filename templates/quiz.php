<?php
$api = IB_Educator::get_instance();

// Get entry data for the current student. Entry status must be "inprogress".
$entry = $api->get_entry( array(
	'user_id'      => get_current_user_id(),
	'course_id'    => ib_edu_get_course_id( get_the_ID() ),
	'entry_status' => 'inprogress'
) );

if ( ! $entry ) {
	return;
}

$lesson_id = get_the_ID();
$questions = $api->get_questions( array( 'lesson_id' => $lesson_id ) );
?>

<?php if ( $questions ) : ?>
	<?php
		$message = get_query_var( 'edu-message' );
		if ( ! $message ) $message = ib_edu_message( 'quiz' );

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

		$quiz_submitted = $api->is_quiz_submitted( $lesson_id, $entry->ID );
	?>

	<section id="ib-edu-quiz" class="<?php echo ( $quiz_submitted ) ? 'ib-edu-quiz-complete' : 'ib-edu-quiz-inprogress'; ?>">
		<?php if ( $quiz_submitted ) : ?>
		<section class="ib-edu-quiz-grade">
			<h3><?php _e( 'Quiz Grade', 'ibeducator' ); ?></h3>
			<?php
				$grade = $api->get_quiz_grade( $lesson_id, $entry->ID );
			?>
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

		<?php
			if ( ! $quiz_submitted ) {
				$answers = isset( $_POST['answers'] ) && is_array( $_POST['answers'] ) ? $_POST['answers'] : array();
			} else {
				$answers = $api->get_student_answers( $lesson_id, $entry->ID );
				if ( ! $answers ) $answers = array();
			}
		?>

		<?php //if ( ! $quiz_submitted ) : ?>
		<h3 class="ib-edu-quiz-title"><?php _e( 'Quiz', 'ibeducator' ); ?></h3>

		<form id="ib-edu-quiz-form" class="ib-edu-form" method="post" action="<?php echo esc_url( ib_edu_get_endpoint_url( 'edu-action', 'submit-quiz', get_permalink() ) ); ?>">
			<?php wp_nonce_field( 'ibedu_submit_quiz_' . $lesson_id ); ?>
			<input type="hidden" name="submit_quiz" value="1">

			<div class="ib-edu-questions">
			<?php
				$choices = $api->get_choices( $lesson_id, true );

				foreach ( $questions as $question ) {
					if ( 'multiplechoice' == $question->question_type ) {
						// Multiple Choice Question.

						// Check if this question has the answer choices.
						if ( ! $choices || ! isset( $choices[ $question->ID ] ) ) {
							continue;
						}
						
						$answers_html = '';

						// Output the answers.
						if ( ! $quiz_submitted ) {
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

						if ( ! $quiz_submitted ) {
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

			<?php if ( ! $quiz_submitted ) : ?>
				<div class="ib-edu-buttons">
					<button class="ib-edu-button" type="submit"><?php _e( 'Submit', 'ibeducator' ); ?></button>
				</div>
			<?php endif; ?>
		</form>
		<?php //endif; ?>
	</section>
<?php endif; ?>