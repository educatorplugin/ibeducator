<?php
if ( ! defined( 'ABSPATH' ) ) exit();

$entry_id = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;

if ( ! $entry_id ) {
	return;
}

$entry = edr_get_entry( $entry_id );

if ( ! $entry->ID ) {
	return;
}

// Verify capabilities.
if ( ! current_user_can( 'edit_ib_educator_course', $entry->course_id ) ) {
	echo '<p>' . __( 'Access denied', 'ibeducator' ) . '</p>';

	exit();
}

$quizzes = Edr_Manager::get( 'edr_quizzes' );
$quizzes_query = new WP_Query( array(
	'post_type' => 'ib_educator_lesson',
	'posts_per_page' => -1,
	'meta_query' => array(
		'relation' => 'AND',
		array( 'key' => '_ibedu_quiz', 'value' => 1, 'compare' => '=' ),
		array( 'key' => '_ibedu_course', 'value' => $entry->course_id, 'compare' => '=' )
	),
	'orderby' => 'menu_order',
	'order' => 'ASC',
) );
$suggested_grade = 0;
$num_quizzes = $quizzes_query->found_posts;
$student = get_user_by( 'id', $entry->user_id );
$course = get_post( $entry->course_id );
?>
<div id="ib-edu-progress" class="wrap">
	<h2><?php _e( 'Progress', 'ibeducator' ); ?></h2>

	<div class="entry-details">
		<h3><?php _e( 'Entry Details', 'ibeducator' ); ?></h3>
		<div class="form-row">
			<div class="label"><?php _e( 'Student', 'ibeducator' ); ?></div>
			<div class="field">
				<?php
					if ( $student ) {
						echo esc_html( $student->display_name );
					}
				?>
			</div>
		</div>
		<div class="form-row">
			<div class="label"><?php _e( 'Course', 'ibeducator' ); ?></div>
			<div class="field">
				<?php
					if ( $course ) {
						echo esc_html( $course->post_title );
					}
				?>
			</div>
		</div>
	</div>

	<?php if ( $quizzes_query->have_posts() ) : ?>
		<div class="quizzes">
			<h3><?php _e( 'Quizzes', 'ibeducator' ); ?></h3>
			<?php while ( $quizzes_query->have_posts() ) : $quizzes_query->the_post(); ?>
			<div class="quiz">
				<div class="quiz-title"><?php the_title(); ?><div class="handle"></div></div>
				<?php
					$lesson_id = get_the_ID();
					$questions = $quizzes->get_questions( $lesson_id );
					$grade = $quizzes->get_grade( $lesson_id, $entry_id );

					// Get answers.
					if ( $grade ) {
						$answers = $quizzes->get_answers( $grade->ID );
					} else {
						$answers = array();
					}

					if ( ! empty( $questions ) ) {
						?>
						<table class="questions">
							<thead>
								<tr>
									<th><?php _e( 'Correct?', 'ibeducator' ); ?></th>
									<th><?php _e( 'Question', 'ibeducator' ); ?></th>
								</tr>
							</thead>
							<tbody>
							<?php
								foreach ( $questions as $question ) {
									$answer = null;

									if ( array_key_exists( $question->ID, $answers ) ) {
										$answer = $answers[ $question->ID ];
									}
									?>
									<tr class="question">
										<td class="check-answer">
											<div><?php
												if ( $answer ) {
													if ( 1 == $answer->correct ) echo '<span class="dashicons dashicons-yes"></span>';
													elseif ( -1 == $answer->correct ) echo '<span class="dashicons dashicons-editor-help"></span>';
													else echo '<span class="dashicons dashicons-no-alt"></span>';
												} else {
													echo '<span class="dashicons dashicons-editor-help"></span>';
												}
											?></div>
										</td>
										<td class="question-body">
										<?php
											echo '<div class="question-text">' . esc_html( $question->question ) . '</div>';

											// Answer(s).
											if ( 'multiplechoice' == $question->question_type ) {
												// Output the answer.
												if ( $answer ) {
													if ( 1 == $answer->correct ) {
														echo '<div class="answer">' . __( 'Correct', 'ibeducator' ) . '</div>';
													} else {
														echo '<div class="answer">' . __( 'Wrong', 'ibeducator' ) . '</div>';
													}
												} else {
													echo '<div class="answer">' . __( 'Not answered yet.', 'ibeducator' ) . '</div>';
												}
											} elseif ( 'writtenanswer' == $question->question_type ) {
												if ( $answer ) {
													echo '<div class="answer">' . esc_html( $answer->answer_text ) . '</div>';
												} else {
													echo '<div class="answer">' . __( 'Not answered yet.', 'ibeducator' ) . '</div>';
												}
											}
										?>
										</td>
									</tr>
									<?php
								}
							?>
							</tbody>
						</table>
						<?php
					}

					if ( $grade ) {
						$suggested_grade += $grade->grade;
					}
				?>
				<div class="quiz-grade">
					<input type="hidden" name="lesson_id" value="<?php echo absint( $lesson_id ); ?>">

					<div class="form-row">
						<div class="label"><?php _e( 'Grade', 'ibeducator' ); ?></div>
						<div class="field">
							<input type="text" name="grade" value="<?php echo ( $grade ) ? floatval( $grade->grade ) : ''; ?>"<?php if ( ! $grade ) echo ' disabled="disabled"'; ?> autocomplete="off">
							<div class="description"><?php
								_e( 'Please enter a number between 0 and 100.', 'ibeducator' );
								echo ' ';
								_e( 'The student will receive a notification email.', 'ibeducator' );
							?></div>
						</div>
					</div>

					<div class="form-buttons">
						<button class="save-quiz-grade button-secondary"<?php if ( ! $grade ) echo ' disabled="disabled"'; ?>><?php _e( 'Save Grade', 'ibeducator' ); ?></button>
					</div>
				</div>
			</div>
			<?php endwhile; ?>

			<?php wp_reset_postdata(); ?>

			<div class="summary">
				<h3><?php _e( 'Summary', 'ibeducator' ); ?></h3>
				<div class="form-row">
					<div class="label"><?php _e( 'Average Grade', 'ibeducator' ); ?></div>
					<div class="field">
						<?php echo ib_edu_format_grade( $suggested_grade / $num_quizzes ); ?>
					</div>
				</div>
				<div class="form-row">
					<div class="label"><?php _e( 'Final Grade', 'ibeducator' ); ?></div>
					<div class="field">
						<?php echo ib_edu_format_grade( $entry->grade ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_entries&edu-action=edit-entry&entry_id=' . $entry_id ) ); ?>">
							<?php _e( 'Edit', 'ibeducator' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>

		<script>
			(function($) {
				'use strict';

				var nonce = '<?php echo wp_create_nonce( "edr_edit_progress_{$entry->ID}" ); ?>';

				$('div.quiz-title').on('click', function() {
					$(this).parent().toggleClass('open');
				});

				$('button.save-quiz-grade').on('click', function() {
					var button = $(this);

					if (button.attr('disabled')) return;
					button.attr('disabled', 'disabled');

					var form = button.closest('div.quiz-grade');
					var grade = form.find('input[name="grade"]:first').val();
					var lessonId = form.find('input[name="lesson_id"]:first').val();

					$.ajax({
						cache: false,
						method: 'post',
						dataType: 'json',
						url: ajaxurl + '?action=edr_quiz_grade',
						data: {
							entry_id: <?php echo intval( $entry_id ); ?>,
							lesson_id: lessonId,
							grade: grade,
							_wpnonce: nonce
						},
						success: function(response) {
							var overlayHtml = '',
								overlay = null;

							if (response && response.status && response.status === 'success') {
								overlayHtml = '<div class="ib-edu-overlay ib-edu-saved"></div>';
							} else {
								overlayHtml = '<div class="ib-edu-overlay ib-edu-error"></div>';
							}

							overlay = $(overlayHtml).hide();
							form.append(overlay);
							overlay.fadeIn(200, function() {
								setTimeout(function() {
									overlay.fadeOut(200, function() {
										button.get(0).removeAttribute('disabled');
										$(this).remove();
									});
								}, 500);
							});
						},
						error: function() {
							button.get(0).removeAttribute('disabled');
						}
					});
				});
			})(jQuery);
		</script>
	<?php endif; ?>
</div>
