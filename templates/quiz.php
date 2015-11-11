<?php
/**
 * This template renders a quiz.
 *
 * @version 1.1.0
 */

$user_id = get_current_user_id();

if ( $user_id == 0 ) {
	echo '<p>';
	printf( __( 'You must be <a href="%s">logged in</a> to take the quiz.', 'ibeducator' ),
		esc_url( wp_login_url( get_permalink() ) ) );
	echo '</p>';

	return;
}

$lesson_id = get_the_ID();

// Get entry data for the current student. Entry status must be "inprogress".
$entry = IB_Educator::get_instance()->get_entry( array(
	'user_id'      => $user_id,
	'course_id'    => ib_edu_get_course_id( $lesson_id ),
	'entry_status' => 'inprogress'
) );

$entry_id = ( $entry ) ? $entry->ID : 0;

if ( ! $entry_id && 'ib_educator_lesson' == get_post_type() ) {
	return;
}

$quizzes = Edr_Manager::get( 'edr_quizzes' );
$questions = $quizzes->get_questions( $lesson_id );
?>

<?php if ( ! empty( $questions ) ) : ?>
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

		$max_attempts_number = $quizzes->get_max_attempts_number( $lesson_id );

		if ( ! is_numeric( $max_attempts_number ) ) {
			$max_attempts_number = 1;
		}

		$grade = $quizzes->get_grade( $lesson_id, $entry_id );
		$attempts_number = $quizzes->get_attempts_number( $lesson_id, $entry_id );
		$do_quiz = $attempts_number < $max_attempts_number;
		$form_action = ib_edu_get_endpoint_url( 'edu-action', 'submit-quiz', get_permalink() );

		if ( $grade && $do_quiz ) {
			$do_quiz = isset( $_GET['try_again'] ) && 'true' == $_GET['try_again'];

			if ( $do_quiz ) {
				$form_action = add_query_arg( 'try_again', 'true', $form_action );
			}
		}

		$form_action .= '#ib-edu-quiz';
	?>

	<section id="ib-edu-quiz" class="<?php echo ( $grade ) ? 'ib-edu-quiz-complete' : 'ib-edu-quiz-inprogress'; ?>">
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

		<form id="ib-edu-quiz-form" class="ib-edu-form" method="post" action="<?php echo esc_url( $form_action ); ?>">
			<?php wp_nonce_field( 'edr_submit_quiz_' . $lesson_id ); ?>
			<input type="hidden" name="submit_quiz" value="1">

			<div class="ib-edu-questions">
				<?php
					if ( $do_quiz ) {
						$answers = ( isset( $_POST['answers'] ) && is_array( $_POST['answers'] ) )
							? $_POST['answers'] : array();
					} elseif ( $grade ) {
						$answers = $quizzes->get_answers( $grade->ID );
					} else {
						$answers = array();
					}

					$choices = null;

					foreach ( $questions as $question ) {
						$answer = isset( $answers[ $question->ID ] ) ? $answers[ $question->ID ] : null;

						switch ( $question->question_type ) {
							// Multiple choice question.
							case 'multiplechoice':
								if ( is_null( $choices ) ) {
									$choices = $quizzes->get_choices( $lesson_id, true );
								}

								if ( isset( $choices[ $question->ID ] ) ) {
									edr_question_multiple_choice( $question, $answer, $do_quiz, $choices[ $question->ID ] );
								}

								break;

							// Written answer question.
							case 'writtenanswer':
								if ( is_string( $answer ) ) {
									$answer = stripslashes( $answer );
								}

								edr_question_written_answer( $question, $answer, $do_quiz );

								break;
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
