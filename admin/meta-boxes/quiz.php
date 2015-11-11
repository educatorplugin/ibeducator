<?php
	$quizzes = Edr_Manager::get( 'edr_quizzes' );
	$lesson_id = (int) $post->ID;
	$attempts_number = get_post_meta( $lesson_id, '_edr_attempts', true );

	if ( ! $attempts_number ) {
		$attempts_number = 1;
	}
?>

<h2><?php _e( 'Settings', 'ibeducator' ); ?></h2>

<div class="ib-edu-field">
	<div class="ib-edu-label">
		<label for="edr-attempts-number"><?php _e( 'Number of attempts', 'ibeducator' ); ?></label>
	</div>
	<div class="ib-edu-control">
		<input type="number" id="edr-attempts-number" name="_edr_attempts" value="<?php echo intval( $attempts_number ); ?>">
	</div>
</div>

<h2><?php _e( 'Questions', 'ibeducator' ); ?></h2>

<div id="ib-edu-quiz">
	<div id="ib-edu-questions"></div>
	<div id="ib-edu-quiz-buttons">
		<div id="ib-edu-add-question">
			<?php
				printf(
					__( 'Add %s question.', 'ibeducator' ),
					'<select id="ib-edu-question-type">' .
					'<option value="multiplechoice">' . __( 'Multiple Choice', 'ibeducator' ) . '</option>' .
					'<option value="writtenanswer">' . __( 'Written Answer', 'ibeducator' ) . '</option>' .
					'</select>'
				);
			?>
			<button class="add-question button button-secondary"><?php _e( 'Go', 'ibeducator' ); ?></button>
		</div>
	</div>
</div>

<input type="hidden" id="ib-edu-quiz-lesson-id" value="<?php echo $lesson_id; ?>">
<input type="hidden" id="edr-quiz-nonce" value="<?php echo wp_create_nonce( 'edr_quiz_' . $lesson_id ); ?>">

<!-- Template: Multiple Choice Question Answer -->
<script type="text/template" id="tpl-ib-edu-multichoiceanswer">
<td class="column1"><div class="handle dashicons dashicons-sort"></div></td>
<td class="column2"><input class="answer-correct" type="radio"></td>
<td class="column3"><input class="answer-text" type="text" class="regular-text" value="<%- choice_text %>"></td>
<td class="column4"><button class="delete-answer button button-secondary">&times;</button></td>
</script>

<!-- Template: Multiple Choice Question -->
<script type="text/template" id="tpl-ib-edu-multiplechoicequestion">
<a class="question-header" href="#">
	<span class="text"><%- question %></span>
	<span class="question-trigger"></span>
</a>
<div class="question-body">
	<div class="question-text">
		<label><?php _e( 'Question', 'ibeducator' ); ?></label>
		<input type="text" class="question-text" value="<%- question %>">
	</div>
	<div class="question-content">
		<label><?php _e( 'Content', 'ibeducator' ); ?></label>
		<textarea class="question-content-input"><%- question_content %></textarea>
	</div>
	<div class="question-answers">
		<label><?php _e( 'Answers', 'ibeducator' ); ?></label>
		<p class="no-answers"><?php _e( 'No answers yet.', 'ibeducator' ); ?></p>
		<table>
			<thead>
				<tr>
					<th></th>
					<th><?php _e( 'Correct?', 'ibeducator' ); ?></th>
					<th><?php _e( 'Answer', 'ibeducator' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div class="quiz-buttons-group">
		<button class="save-question button button-primary"><?php _e( 'Save Question', 'ibeducator' ); ?></button>
		<button class="add-answer button button-secondary"><?php _e( 'Add Answer', 'ibeducator' ); ?></button>
		<a class="delete-question" href="#"><?php _e( 'Delete', 'ibeducator' ); ?></a>
	</div>
</div>
</script>

<!-- Template: Written Answer Question -->
<script type="text/template" id="tpl-ib-edu-writtenanswerquestion">
<a class="question-header" href="#">
	<span class="text"><%- question %></span>
	<span class="question-trigger"></span>
</a>
<div class="question-body">
	<div class="question-text">
		<label><?php _e( 'Question', 'ibeducator' ); ?></label>
		<input type="text" class="question-text" value="<%- question %>">
	</div>
	<div class="question-content">
		<label><?php _e( 'Content', 'ibeducator' ); ?></label>
		<textarea class="question-content-input"><%- question_content %></textarea>
	</div>
	<div class="quiz-buttons-group">
		<button class="save-question button button-primary"><?php _e( 'Save Question', 'ibeducator' ); ?></button>
		<a class="delete-question" href="#"><?php _e( 'Delete', 'ibeducator' ); ?></a>
	</div>
</div>
</script>

<?php
// Create questions JSON.
$questions_js = '[';
$questions = $quizzes->get_questions( array( 'lesson_id' => $lesson_id ) );

foreach ( $questions as $question ) {
	$questions_js .= "{id: " . intval( $question->ID ) . ","
		. "question: " . json_encode( $question->question ) . ","
		. "question_type: '" . esc_js( $question->question_type ) . "',"
		. 'question_content: ' . json_encode( apply_filters( 'edr_edit_question_form_content', $question->question_content ) ) . ','
		. "menu_order: " . intval( $question->menu_order ) . '},';
}

$questions_js .= ']';

// Create answers (choices) JSON.
$choices_json = '{';
$choices = $quizzes->get_choices( $lesson_id, true );

foreach ( $choices as $question_id => $question ) {
	$choices_json .= 'question_' . intval( $question_id ) . ':[';

	foreach ( $question as $choice ) {
		$choices_json .= "{choice_id: " . intval( $choice->ID ) . ", "
			. "question_id: " . intval( $choice->question_id ) . ", "
			. "choice_text: " . json_encode( $choice->choice_text ) . ", "
			. "correct: " . intval( $choice->correct ) . ", "
			. "menu_order: " . intval( $choice->menu_order ) . "},";
	}

	$choices_json .= '],';
}

$choices_json .= '}';
?>
<script>
	var educatorQuizQuestions = <?php echo $questions_js; ?>;
	var educatorQuizChoices = <?php echo $choices_json; ?>;
</script>
