(function($) {
	'use strict';

	// Current lesson id.
	EdrQuiz.lessonId = parseInt(document.getElementById('ib-edu-quiz-lesson-id').value, 10);

	// Nonce.
	EdrQuiz.nonce = $('#edr-quiz-nonce').val();

	// Question type select element.
	EdrQuiz.questionType = $('#ib-edu-question-type');

	// Attach nonce to quiz related ajax requests.
	$.ajaxPrefilter(function(options) {
		var data = null;

		if (options.url.indexOf('edr_quiz_question') !== -1) {
			if (options.data) {
				data = JSON.parse(options.data);
			} else {
				data = {};
			}

			if (typeof data === 'object') {
				data._wpnonce = EdrQuiz.nonce;
			}

			options.data = JSON.stringify(data);
		}
	});

	// Initialize the quiz.
	var quizView = new EdrQuiz.QuizView();

	// Make the questions sortable.
	$('#ib-edu-questions').sortable({
		axis: 'y',
		handle: '.question-header',
		placeholder: 'question-placeholder',
		start: function(e, ui) {
			ui.placeholder.height(ui.item.height() - 2);
		},
		update: function(e, ui) {
			$(this).trigger('updateQuestionsOrder');
		}
	});
}(jQuery));
