/**
 * Multiple choice answer model.
 */
(function(exports, $) {
	'use strict';

	var MultipleChoiceAnswerModel = Backbone.Model.extend({
		/** @member {Object} */
		defaults: {
			choice_id:   0,
			question_id: 0,
			choice_text: '',
			correct:     0,
			menu_order:  0
		}
	});

	exports.MultipleChoiceAnswerModel = MultipleChoiceAnswerModel;
}(EdrQuiz, jQuery));
