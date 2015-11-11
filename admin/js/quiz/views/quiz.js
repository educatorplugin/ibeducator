/**
 * Quiz view.
 */
(function(exports, $) {
	'use strict';

	var QuizView = Backbone.View.extend({
		/** @member {jQuery} */
		el: $('#ib-edu-quiz'),

		/** @member {Object} */
		events: {
			'click button.add-question': 'addQuestion',
			'updateQuestionsOrder': 'updateQuestionsOrder'
		},

		/**
		 * Initialize view.
		 */
		initialize: function() {
			this.collection = new EdrQuiz.Questions(educatorQuizQuestions);

			this.render();
		},

		/**
		 * Render view.
		 */
		render: function() {
			var that = this;

			_.each(this.collection.models, function(question) {
				that.renderQuestion(question);
			}, this);
		},

		/**
		 * Render a question.
		 *
		 * @param {QuestionModel} question
		 */
		renderQuestion: function(question) {
			var questionType = question.get('question_type');
			var view, meta, answer, i;

			question.set('lesson_id', EdrQuiz.lessonId);

			switch (question.get('question_type')) {
				case 'multiplechoice':
					view = new EdrQuiz.MultipleChoiceQuestionView({model: question});
					break;

				case 'writtenanswer':
					view = new EdrQuiz.WrittenAnswerQuestionView({model: question});
					break;
			}

			view.render();

			$('#ib-edu-questions').append(view.$el);
		},

		/**
		 * Add a new question.
		 *
		 * @param {Object} e
		 */
		addQuestion: function(e) {
			var view;
			var question = new EdrQuiz.QuestionModel();
			var question_type = EdrQuiz.questionType.val();

			question.set('lesson_id', EdrQuiz.lessonId);
			question.set('question_type', question_type);

			switch (question_type) {
				case 'multiplechoice':
					view = new EdrQuiz.MultipleChoiceQuestionView({model: question});
					break;

				case 'writtenanswer':
					view = new EdrQuiz.WrittenAnswerQuestionView({model: question});
					break;
			}

			this.collection.add(question);

			view.render();
			view.openQuestion();

			$('#ib-edu-questions').append(view.el);

			e.preventDefault();
		},

		/**
		 * Update the menu order of the questions.
		 */
		updateQuestionsOrder: function() {
			var question_id = [];
			var order = [];

			// Update models and setup questions order data for AJAX.
			_.each(this.collection.models, function(question) {
				question.trigger('updateOrderFromView');
				question_id.push(question.get('id'));
				order.push(question.get('menu_order'));
			});

			// Sort questions collection.
			this.collection.sort();

			// Disable sortable.
			$('#ib-edu-questions').sortable('disable');

			// Send.
			$.ajax({
				type: 'post',
				url: ajaxurl + '?action=edr_sort_questions',
				data: {
					lesson_id:   EdrQuiz.lessonId,
					question_id: question_id,
					order:       order,
					_wpnonce:    EdrQuiz.nonce
				},
				complete: function() {
					$('#ib-edu-questions').sortable('enable');
				}
			});
		}
	});

	exports.QuizView = QuizView;
})(EdrQuiz, jQuery);
