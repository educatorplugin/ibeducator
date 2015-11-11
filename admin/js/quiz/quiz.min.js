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

/**
 * Question view.
 */
(function(exports, $) {
	'use strict';

	var QuestionView = Backbone.View.extend({
		/** @member {string} */
		tagName: 'div',

		/** @member {Object} */
		events: {
			'click a.question-header': 'triggerQuestion',
			'click button.save-question': 'saveQuestion',
			'keypress': 'onKeyPress',
			'click a.delete-question': 'deleteQuestion'
		},

		/**
		 * Initilize view.
		 */
		initialize: function() {
			// Setup model listeners.
			this.model.on('change:question', this.updateQuestionText, this);
			this.model.on('remove', this.remove, this);
			this.model.on('updateOrderFromView', this.updateOrderFromView, this);
			this.model.on('sync', this.afterSave, this);
		},

		/**
		 * Render view.
		 */
		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
		},

		/**
		 * Update question heading.
		 */
		updateQuestionText: function() {
			this.$el.find('> .question-header > .text').text(this.model.get('question'));
		},

		/**
		 * Update the question's menu order from view.
		 */
		updateOrderFromView: function() {
			this.model.set('menu_order', this.$el.index());
		},

		/**
		 * Open/close the question.
		 *
		 * @param {Object} e
		 */
		triggerQuestion: function(e) {
			if (this.isOpen()) {
				this.closeQuestion();
			} else {
				this.openQuestion();
			}

			e.preventDefault();
		},

		/**
		 * Open the question.
		 */
		openQuestion: function() {
			this.$el.addClass('open');
		},

		/**
		 * Close the question.
		 */
		closeQuestion: function() {
			this.$el.removeClass('open');
		},

		/**
		 * Check if the question is open.
		 */
		isOpen: function() {
			return this.$el.hasClass('open');
		},

		/**
		 * Delete the question.
		 *
		 * @param {Object} e
		 */
		deleteQuestion: function(e) {
			if (confirm(EdrQuiz.text.confirmDelete)) {
				this.model.destroy({wait: true});
			}

			e.preventDefault();
		},

		/**
		 * Show the "success" message when the question is saved.
		 *
		 * @param {QuestionModel} model
		 * @param {Object} response
		 * @param {Object} options
		 */
		afterSave: function(model, response, options) {
			this.showMessage('saved', 800);

			this.$el.find('button.save-question').removeAttr('disabled');
		},

		/**
		 * Disable the "Save Question" button when saving the question.
		 */
		saveQuestion: function() {
			this.$el.find('button.save-question').attr('disabled', 'disabled');
		},

		/**
		 * Save the question when the "enter" key is pressed.
		 *
		 * @param {Object} e
		 */
		onKeyPress: function(e) {
			if (e.which === 13 && e.target.nodeName !== 'TEXTAREA') {
				e.stopPropagation();

				if (!this.$el.find('button.save-question').attr('disabled')) {
					this.saveQuestion(e);
				}
			}
		},

		/**
		 * Show a message.
		 *
		 * @param {string} type
		 * @param {number} timeout
		 */
		showMessage: function(type, timeout) {
			timeout || (timeout = 0);
			var that = this;
			var classes = 'ib-edu-overlay';

			switch (type) {
				case 'saved':
					classes += ' ib-edu-saved';
					break;

				case 'error':
					classes += ' ib-edu-error';
					break;
			}

			var show = function() {
				var message = $('<div class="' + classes + '"></div>');

				message.hide();

				that.$el.find('> .question-body').append(message);
				
				message.fadeIn(200);

				if (timeout) {
					setTimeout(function() {
						that.hideMessage();
					}, timeout);
				}
			};

			if (this.$el.find('.question-message').length) {
				this.hideMessage(show);
			} else {
				show();
			}
		},

		/**
		 * Hide the message.
		 *
		 * @param {Function} cb
		 */
		hideMessage: function(cb) {
			this.$el.find('.ib-edu-overlay').fadeOut(200, function() {
				$(this).remove();

				if (typeof cb === 'function') {
					cb.apply();
				}
			});
		}
	});

	exports.QuestionView = QuestionView;
})(EdrQuiz, jQuery);

/**
 * Multiple choice question view.
 */
(function(exports, $) {
	'use strict';

	var MultipleChoiceQuestionView = EdrQuiz.QuestionView.extend({
		/** @member {string} */
		className: 'question question-multiple',

		/** @member {Function} */
		template: _.template($('#tpl-ib-edu-multiplechoicequestion').html()),

		/**
		 * Initialize view.
		 */
		initialize: function() {
			// Initialize the parent object.
			EdrQuiz.QuestionView.prototype.initialize.apply(this);

			// Create collection.
			this.collection = new EdrQuiz.MultipleChoiceAnswersCollection();

			// Render a choice when its added to the collection.
			this.listenTo(this.collection, 'add', this.renderChoice);

			// Remove a choice from the view when its removed from the collection.
			this.listenTo(this.collection, 'remove', this.onChoiceRemove);
		},

		/**
		 * Render view.
		 */
		render: function() {
			var choice;
			var i;
			var question_id = this.model.get('id');

			EdrQuiz.QuestionView.prototype.render.apply(this);

			// Populate question with existing choices.
			if (educatorQuizChoices['question_' + question_id]) {
				for (i = 0; i < educatorQuizChoices['question_' + question_id].length; ++i) {
					choice = educatorQuizChoices['question_' + question_id][i];

					this.collection.add(choice);
				}
			}

			// Order question choices.
			this.$el.find('.question-answers > table > tbody').sortable({
				axis: 'y',
				items: 'tr',
				handle: 'div.handle',
				placeholder: 'placeholder',
				helper: function(e, helper) {
					helper.children().each(function(i) {
						var td = $(this);
						td.width(td.innerWidth());
					});

					return helper;
				},
				start: function(e, ui) {
					ui.placeholder.height(ui.item.height() - 2);
				},
				update: function(e, ui) {
					$(this).trigger('updateChoicesOrder');
				},
				stop: function(e, ui) {
					ui.item.children().removeAttr('style');
				}
			});
		},

		/**
		 * Render a choice.
		 *
		 * @param {MultipleChoiceAnswerModel} choice
		 */
		renderChoice: function(choice) {
			var view = new EdrQuiz.MultipleChoiceAnswerView({
				model:         choice,
				questionModel: this.model
			});

			// Hide "no answers" message if needed.
			if (this.collection.length > 0) {
				this.$el.find('.no-answers').hide();
				this.$el.find('.question-answers > table > thead').show();
			}

			view.render();

			this.$el.find('.question-answers > table > tbody').append(view.el);
		},

		/**
		 * Hide the choices table if the choices collection is empty.
		 */
		onChoiceRemove: function() {
			if (this.collection.length < 1) {
				this.$el.find('.no-answers').show();
				this.$el.find('.question-answers > table > thead').hide();
			}
		},

		/**
		 * Register events.
		 */
		events: function() {
			return _.extend({
				'click button.add-answer': 'addChoice',
				'updateChoicesOrder': 'updateChoicesOrder'
			}, this.constructor.__super__.events);
		},

		/**
		 * Process the "add answer" event.
		 *
		 * @param {Object} e
		 */
		addChoice: function(e) {
			var choice = new EdrQuiz.MultipleChoiceAnswerModel();
			var maxMenuOrder = 0;

			_.each(this.collection.models, function(choice) {
				if (choice.get('menu_order') > maxMenuOrder) {
					maxMenuOrder = choice.get('menu_order');
				}
			});

			choice.set('menu_order', maxMenuOrder + 1);

			// Add choice to the choices collection.
			this.collection.add(choice);

			// Hide "no answers" message.
			this.$el.find('.no-answers').hide();

			e.preventDefault();
		},

		/**
		 * Update the menu order of each choice.
		 */
		updateChoicesOrder: function() {
			_.each(this.collection.models, function(choice) {
				choice.trigger('updateOrderFromView');
			});
		},

		/**
		 * Save question.
		 *
		 * @param {Object} e
		 */
		saveQuestion: function(e) {
			var that = this;
			var newData = {};

			EdrQuiz.QuestionView.prototype.saveQuestion.apply(this);

			// Setup question data.
			newData.question = this.$el.find('input.question-text').val();
			newData.question_content = this.$el.find('.question-content-input').val();
			newData.menu_order = this.$el.index();
			
			// Setup choices.
			newData.choices = [];

			_.each(this.collection.models, function(choice) {
				choice.trigger('updateAnswerValues');
				newData.choices.push({
					choice_id: choice.get('choice_id'),
					choice_text: choice.get('choice_text'),
					correct: choice.get('correct'),
					menu_order: choice.get('menu_order')
				});
			});

			// Send request to the server.
			this.model.save(newData, {
				wait: true,
				success: function(model, response, options) {
					if (response.status === 'success') {
						// If question was new, get id from the server.
						if (model.isNew()) {
							model.set('id', parseInt(response.id, 10));
						}

						// Remove previous models.
						that.collection.remove(that.collection.models);

						// Update choices.
						if (response.choices) {
							// Reset the collection such that the menu_order of the choices is preserved.
							that.collection.reset(response.choices);

							// Render new/updated choices.
							_.each(that.collection.models, function(choice) {
								that.renderChoice(choice);
							});
						}
					}
				},
				error: function(model, xhr, options) {
					that.showMessage('error', 800);
				}
			});

			e.preventDefault();
		}
	});

	exports.MultipleChoiceQuestionView = MultipleChoiceQuestionView;
}(EdrQuiz, jQuery));

/**
 * Written answer question view.
 */
(function(exports, $) {
	'use strict';

	var WrittenAnswerQuestionView = EdrQuiz.QuestionView.extend({
		/** @member {string} */
		className: 'question question-writtenanswer',

		/** @member {Function} */
		template: _.template($('#tpl-ib-edu-writtenanswerquestion').html()),

		/**
		 * Initialize view.
		 */
		initialize: function() {
			EdrQuiz.QuestionView.prototype.initialize.apply(this);
		},

		/**
		 * Render view.
		 */
		render: function() {
			EdrQuiz.QuestionView.prototype.render.apply(this);
		},

		/**
		 * Save the question.
		 *
		 * @param {Object} e
		 */
		saveQuestion: function(e) {
			var that = this;
			var newData = {};

			EdrQuiz.QuestionView.prototype.saveQuestion.apply(this);

			// Setup question data.
			newData.question = this.$el.find('input.question-text').val();
			newData.question_content = this.$el.find('.question-content-input').val();
			newData.menu_order = this.$el.index();

			// Send request to the server.
			this.model.save(newData, {
				wait: true,
				success: function(model, response, options) {
					if (response.status === 'success') {
						// If question was new, get id from the server
						if (model.isNew()) {
							model.set('id', parseInt(response.id, 10));
						}
					}
				},
				error: function(model, xhr, options) {
					that.showMessage('error', 800);
				}
			});

			e.preventDefault();
		}
	});

	exports.WrittenAnswerQuestionView = WrittenAnswerQuestionView;
}(EdrQuiz, jQuery));
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
