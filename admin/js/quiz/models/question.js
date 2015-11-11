/**
 * Question model.
 */
(function(exports, $) {
	'use strict';

	var QuestionModel = Backbone.Model.extend({
		/** @member {Object} */
		defaults: {
			lesson_id: 0,
			question: '',
			question_type: '',
			question_content: '',
			is_true: null,
			menu_order: 0
		},

		/**
		 * Synchronize question with the server.
		 * Defines URLs for synchronization.
		 *
		 * @param {string} method
		 * @param {QuestionModel} model
		 * @param {Object} options
		 */
		sync: function(method, model, options) {
			options || (options = {});
			options.url = ajaxurl;

			switch (method) {
				case 'read':
					break;

				case 'create':
					options.url += '?action=edr_quiz_question';
					break;

				case 'update':
					options.url += '?action=edr_quiz_question&id=' + this.id;
					break;

				case 'delete':
					options.url += '?action=edr_quiz_question&id=' + this.id;
					break;
			}

			return Backbone.sync.apply(this, arguments);
		}
	});

	exports.QuestionModel = QuestionModel;
}(EdrQuiz, jQuery));
