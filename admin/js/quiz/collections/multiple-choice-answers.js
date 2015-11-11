/**
 * Multiple choice answers collection.
 */
(function(exports, $) {
	'use strict';

	var MultipleChoiceAnswersCollection = Backbone.Collection.extend({
		/** @member {MultipleChoiceAnswerModel} */
		model: EdrQuiz.MultipleChoiceAnswerModel,

		/**
		 * Comparator for sorting.
		 *
		 * @param {MultipleChoiceAnswerModel}
		 * @return {Number}
		 */
		comparator: function(model) {
			return model.get('menu_order');
		}
	});

	exports.MultipleChoiceAnswersCollection = MultipleChoiceAnswersCollection;
}(EdrQuiz, jQuery));
