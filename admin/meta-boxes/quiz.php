<?php
	$lesson_id = (int) $post->ID;
?>
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
<input type="hidden" id="ibedu_quiz_nonce" value="<?php echo wp_create_nonce( 'ibedu_quiz_' . $lesson_id ); ?>">

<!-- Template: Multiple Choice Question Answer -->
<script type="text/template" id="tpl-ib-edu-multichoiceanswer">
<td class="column1"><div class="handle dashicons dashicons-sort"></div></td>
<td class="column2"><input class="answer-correct" type="radio"></td>
<td class="column3"><input class="answer-text" type="text" class="regular-text" value="<%= choice_text %>"></td>
<td class="column4"><button class="delete-answer button button-secondary">&times;</button></td>
</script>

<!-- Template: Multiple Choice Question -->
<script type="text/template" id="tpl-ib-edu-multiplechoicequestion">
<a class="question-header" href="#">
	<span class="text"><%= question %></span>
	<span class="question-trigger"></span>
</a>
<div class="question-body">
	<div class="question-text">
		<label><?php _e( 'Question', 'ibeducator' ); ?></label>
		<input type="text" class="question-text" value="<%= question %>">
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
	<span class="text"><%= question %></span>
	<span class="question-trigger"></span>
</a>
<div class="question-body">
	<div class="question-text">
		<label><?php _e( 'Question', 'ibeducator' ); ?></label>
		<input type="text" class="question-text" value="<%= question %>">
	</div>
	<div class="quiz-buttons-group">
		<button class="save-question button button-primary"><?php _e( 'Save Question', 'ibeducator' ); ?></button>
		<a class="delete-question" href="#"><?php _e( 'Delete', 'ibeducator' ); ?></a>
	</div>
</div>
</script>

<?php
	$api = IB_Educator::get_instance();

	// Create questions JSON.
	$questions_js = '[';
	$questions = $api->get_questions( array( 'lesson_id' => $lesson_id ) );

	if ( $questions ) {
		foreach ( $questions as $question ) {
			$questions_js .= "{id: " . absint( $question->ID ) . ", "
						   . "question: '" . esc_js( $question->question ) . "', "
						   . "question_type: '" . esc_js( $question->question_type ) . "', "
						   . "menu_order: " . absint( $question->menu_order ) . '},';
		}
	}

	$questions_js .= ']';

	// Create answers (choices) JSON.
	$choices_json = '{';
	$choices = $api->get_choices( $lesson_id, true );

	if ( $choices ) {
		foreach ( $choices as $question_id => $question ) {
			$choices_json .= 'question_' . absint( $question_id ) . ':[';
			
			foreach ( $question as $choice ) {
				$choices_json .= "{choice_id: " . absint( $choice->ID ) . ", "
							   . "question_id: " . absint( $choice->question_id ) . ", "
							   . "choice_text: '" . esc_js( $choice->choice_text ) . "', "
							   . "correct: " . absint( $choice->correct ) . ", "
							   . "menu_order: " . absint( $choice->menu_order ) . "},";
			}

			$choices_json .= '],';
		}
	}

	$choices_json .= '}';
?>
<script>
var educatorQuizQuestions = <?php echo $questions_js; ?>;
var educatorQuizChoices = <?php echo $choices_json; ?>;
</script>