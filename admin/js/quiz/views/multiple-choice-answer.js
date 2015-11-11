/**
 * Multiple choice answer view.
 */
(function(exports, $) {
	'use strict';

	var MultipleChoiceAnswerView = Backbone.View.extend({
		/** @member {string} */
		className: 'answer',

		/** @member {string} */
		tagName: 'tr',

		/** @member {Function} */
		template: _.template($('#tpl-ib-edu-multichoiceanswer').html()),

		/** @member {QuestionModel|null} */
		questionModel: null,

		/** @member {Object} */
		events: {
			'click button.delete-answer': 'deleteAnswer'
		},

		/**
		 * Initialize view.
		 *
		 * @param {Object} options
		 */
		initialize: function(options) {
			this.listenTo(this.model, 'remove', this.remove);
			this.listenTo(this.model, 'updateAnswerValues', this.updateAnswerValues);
			this.listenTo(this.model, 'updateOrderFromView', this.updateOrderFromView);

			if (typeof options.questionModel !== 'undefined') {
				this.questionModel = options.questionModel;
			}
		},

		/**
		 * Render view.
		 */
		render: function() {
			this.$el.html(this.template(this.model.toJSON()));

			if (this.questionModel) {
				this.$el.find('input.answer-correct').attr('name', 'answer_' + this.questionModel.get('id'));
			}

			if (this.model.get('correct') === 1) {
				this.$el.find('input.answer-correct').attr('checked', 'checked');
			}
		},

		/**
		 * Update model from view.
		 */
		updateAnswerValues: function() {
			this.model.set('choice_text', this.$el.find('input.answer-text').val());
			this.model.set('correct', this.$el.find('input.answer-correct').is(':checked') ? 1 : 0);
		},

		/**
		 * Delete answer.
		 *
		 * @param {Object} e
		 */
		deleteAnswer: function(e) {
			if (confirm(EdrQuiz.text.confirmDelete)) {
				this.model.destroy();
			}

			e.preventDefault();
		},

		/**
		 * Update menu order from view.
		 */
		updateOrderFromView: function() {
			this.model.set('menu_order', this.$el.index());
		}
	});

	exports.MultipleChoiceAnswerView = MultipleChoiceAnswerView;
})(EdrQuiz, jQuery);
