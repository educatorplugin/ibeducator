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
	'course_id'    => Edr_Courses::get_instance()->get_course_id( $lesson_id ),
	'entry_status' => 'inprogress',
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
		// Get the maximum number of times a user can complete this quiz.
		$max_attempts_number = $quizzes->get_max_attempts_number( $lesson_id );

		if ( ! is_numeric( $max_attempts_number ) ) {
			$max_attempts_number = 1;
		}

		// Get the number of times a user attempted to complete this quiz..
		$attempts_number = $quizzes->get_attempts_number( $lesson_id, $entry_id );

		// Check if a user has enough attempts to complete this quiz.
		$can_attempt = $attempts_number < $max_attempts_number;

		// Is the student is in the process of doing a quiz?
		$do_quiz = false;

		// Get current grade.
		$grade = $quizzes->get_grade( $lesson_id, $entry_id );

		// Determine the form action.
		$form_action = edr_get_endpoint_url( 'edu-action', 'submit-quiz', get_permalink() );

		if ( $can_attempt ) {
			if ( isset( $_GET['try_again'] ) && 'true' == $_GET['try_again'] ) {
				$do_quiz = true;
				$form_action = add_query_arg( 'try_again', 'true', $form_action );

				if ( $grade && 'draft' != $grade->status ) {
					$grade = null;
				}
			} elseif ( ! $grade || 'draft' == $grade->status ) {
				$do_quiz = true;
			}
		}

		// Scroll the page to where the quiz form is displayed, after it is submitted.
		$form_action .= '#ib-edu-quiz';
	?>

	<section id="ib-edu-quiz" class="<?php echo ( $grade ) ? 'ib-edu-quiz-complete' : 'ib-edu-quiz-inprogress'; ?>">
		<?php
			$messages = edr_internal_message( 'quiz' );
			$error_codes = is_wp_error( $messages ) ? $messages->get_error_codes() : null;

			if ( ! empty( $error_codes ) ) {
				foreach ( $error_codes as $code ) {
					echo '<div class="ib-edu-message error">' . $messages->get_error_message( $code ) . '</div>';
				}
			} else {
				switch ( get_query_var( 'edu-message' ) ) {
					case 'quiz-submitted':
						echo '<div class="ib-edu-message success">' . __( 'Thank you. the quiz has been accepted.', 'ibeducator' ) . '</div>';
						break;
				}
			}
		?>

		<?php if ( ! $do_quiz && $grade ) : ?>
			<section class="ib-edu-quiz-grade">
				<h3><?php _e( 'Quiz Grade', 'ibeducator' ); ?></h3>
				<p class="grade">
					<?php
						if ( 'approved' == $grade->status ) {
							printf( __( 'You scored %s for this quiz.', 'ibeducator' ), '<strong>' . edr_format_grade( $grade->grade ) . '</strong>' );
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
					$current_attempt = $attempts_number;

					// Increment current attempt number if a user is editing the quiz.
					if ( $do_quiz ) {
						$current_attempt += 1;
					}

					printf( __( 'Attempt %1$d of %2$d', 'ibeducator' ), $current_attempt, $max_attempts_number );
				?>
			</p>

			<?php if ( $can_attempt && ! $do_quiz ) : ?>
				<p class="try-again">
					<a href="<?php echo esc_url( add_query_arg( 'try_again', 'true', get_permalink() ) ); ?>#ib-edu-quiz"><?php _e( 'Try again', 'ibeducator' ); ?></a>
				</p>
			<?php endif; ?>
		</div>

		<form id="ib-edu-quiz-form" class="ib-edu-form" method="post" action="<?php echo esc_url( $form_action ); ?>"
			enctype="multipart/form-data">
			<?php wp_nonce_field( 'edr_submit_quiz_' . $lesson_id ); ?>
			<input type="hidden" name="submit_quiz" value="1">

			<div class="ib-edu-questions">
				<?php
					$posted_answers = array();
					$current_answers = array();
					$choices = null;

					if ( isset( $_POST['answers'] ) && is_array( $_POST['answers'] ) ) {
						$posted_answers = $_POST['answers'];
					}

					if ( $grade ) {
						$current_answers = $quizzes->get_answers( $grade->ID );
					}

					foreach ( $questions as $question ) {
						$answer = null;
						$editable = true;

						if ( isset( $current_answers[ $question->ID ] ) ) {
							$answer = $current_answers[ $question->ID ];

							if ( 'draft' != $grade->status ) {
								$editable = false;
							}
						} elseif ( isset( $posted_answers[ $question->ID ] ) ) {
							$answer = $posted_answers[ $question->ID ];
						}

						switch ( $question->question_type ) {
							// Multiple choice question.
							case 'multiplechoice':
								if ( is_null( $choices ) ) {
									$choices = $quizzes->get_choices( $lesson_id, true );
								}

								if ( isset( $choices[ $question->ID ] ) ) {
									edr_question_multiple_choice( $question, $answer, $editable, $choices[ $question->ID ] );
								}

								break;

							// Written answer question.
							case 'writtenanswer':
								if ( is_string( $answer ) ) {
									$answer = stripslashes( $answer );
								}

								edr_question_written_answer( $question, $answer, $editable );

								break;

							// File upload question.
							case 'fileupload':
								edr_question_file_upload( $question, $answer, $editable, $grade );

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
