/**
 * Questions collection.
 */
(function(exports, $) {
	'use strict';

	var Questions = Backbone.Collection.extend({
		/** @member {QuestionModel} */
		model: EdrQuiz.QuestionModel,

		/**
		 * Comparator for sorting.
		 *
		 * @param {QuestionModel}
		 * @return {Number}
		 */
		comparator: function(model) {
			return model.get('menu_order');
		}
	});

	exports.Questions = Questions;
}(EdrQuiz, jQuery));
