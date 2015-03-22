(function($) {

	'use strict';

	$(document).ready(function() {
		// Get current post(lesson) id.
		var lesson_id = parseInt(document.getElementById('ib-edu-quiz-lesson-id').value, 10);
		var questionType = $('#ib-edu-question-type');
		var nonce = $('#ibedu_quiz_nonce').val();

		$.ajaxPrefilter(function(options) {
			var data = null;

			if (options.url.indexOf('ibedu_quiz_question') !== -1) {
				if (options.data) {
					data = JSON.parse(options.data);
				} else {
					data = {};
				}

				if (typeof data === 'object') {
					data._wpnonce = nonce;
				}

				options.data = JSON.stringify(data);
			}
		});

		/**
		 * Multiple Choice Answer Model.
		 */
		var MultipleChoiceAnswerModel = Backbone.Model.extend({
			defaults: {
				choice_id: 0,
				question_id: 0,
				choice_text: '',
				correct: 0,
				menu_order: 0
			}
		});

		/**
		 * Question Model.
		 */
		var QuestionModel = Backbone.Model.extend({
			defaults: {
				lesson_id: 0,
				question: '',
				question_type: '',
				is_true: null,
				menu_order: 0
			},
			
			initialize: function() {},

			sync: function(method, model, options) {
				options || (options = {});
				options.url = ajaxurl;

				switch (method) {
					case 'read':
						break;

					case 'create':
						options.url += '?action=ibedu_quiz_question';
						break;

					case 'update':
						options.url += '?action=ibedu_quiz_question&id=' + this.id;
						break;

					case 'delete':
						options.url += '?action=ibedu_quiz_question&id=' + this.id;
						break;
				}

				return Backbone.sync.apply(this, arguments);
			}
		});

		/**
		 * Multiple Choice Answers Collection.
		 */
		var MultipleChoiceAnswersCollection = Backbone.Collection.extend({
			model: MultipleChoiceAnswerModel,
			comparator: function(model) {
				return model.get('menu_order');
			}
		});

		/**
		 * Questions Collection.
		 */
		var Questions = Backbone.Collection.extend({
			model: QuestionModel,
			comparator: function(model) {
				return model.get('menu_order');
			}
		});

		/**
		 * Multiple Choice Answer View.
		 */
		var MultipleChoiceAnswerView = Backbone.View.extend({
			className: 'answer',
			tagName: 'tr',
			template: _.template($('#tpl-ib-edu-multichoiceanswer').html()),
			questionModel: null,

			initialize: function(options) {
				this.listenTo(this.model, 'remove', this.remove);
				this.listenTo(this.model, 'updateAnswerValues', this.updateAnswerValues);
				this.listenTo(this.model, 'updateOrderFromView', this.updateOrderFromView);

				if (typeof options.questionModel !== 'undefined') {
					this.questionModel = options.questionModel;
				}
			},

			render: function() {
				this.$el.html(this.template(this.model.toJSON()));

				if (this.questionModel) {
					this.$el.find('input.answer-correct').attr('name', 'answer_' + this.questionModel.get('id'));
				}

				if (this.model.get('correct') === 1) {
					this.$el.find('input.answer-correct').attr('checked', 'checked');
				}
			},

			updateAnswerValues: function() {
				this.model.set('choice_text', this.$el.find('input.answer-text').val());
				this.model.set('correct', this.$el.find('input.answer-correct').is(':checked') ? 1 : 0);
			},

			events: {
				'click button.delete-answer': 'deleteAnswer'
			},

			deleteAnswer: function(e) {
				e.preventDefault();

				if ( confirm( educatorQuizText.confirm_delete ) ) {
					this.model.destroy();
				}
			},

			updateOrderFromView: function() {
				this.model.set('menu_order', this.$el.index());
			}
		});

		/**
		 * View: QuestionView.
		 */
		var QuestionView = Backbone.View.extend({
			tagName: 'div',

			events: {
				'click a.question-header': 'triggerQuestion',
				'click button.save-question': 'saveQuestion',
				'keypress': 'onKeyPress',
				'click a.delete-question': 'deleteQuestion'
			},

			initialize: function() {
				this.model.on('change:question', this.updateQuestionText, this);
				this.model.on('remove', this.remove, this);
				this.model.on('updateOrderFromView', this.updateOrderFromView, this);
				this.model.on('sync', this.afterSave, this);
			},

			render: function() {
				this.$el.html(this.template(this.model.toJSON()));
			},

			updateQuestionText: function() {
				this.$el.find('> .question-header > .text').text(this.model.get('question'));
			},

			updateOrderFromView: function() {
				this.model.set('menu_order', this.$el.index());
			},

			triggerQuestion: function(e) {
				e.preventDefault();

				if (this.isOpen()) {
					this.closeQuestion();
				} else {
					this.openQuestion();
				}
			},

			openQuestion: function() {
				this.$el.addClass('open');
			},

			closeQuestion: function() {
				this.$el.removeClass('open');
			},

			isOpen: function() {
				return this.$el.hasClass('open');
			},

			deleteQuestion: function(e) {
				e.preventDefault();

				if ( confirm( educatorQuizText.confirm_delete ) ) {
					this.model.destroy({wait: true});
				}
			},

			afterSave: function(model, response, options) {
				this.showMessage('saved', 800);
				this.$el.find('button.save-question').removeAttr('disabled');
			},

			saveQuestion: function() {
				this.$el.find('button.save-question').attr('disabled', 'disabled');
			},

			onKeyPress: function(e) {
				if (e.which === 13) {
					e.stopPropagation();

					if (!this.$el.find('button.save-question').attr('disabled')) {
						this.saveQuestion(e);
					}
				}
			},

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

			hideMessage: function(cb) {
				this.$el.find('.ib-edu-overlay').fadeOut(200, function() {
					$(this).remove();

					if (typeof cb === 'function') {
						cb.apply();
					}
				});
			}
		});

		/**
		 * Multiple Choice Question View.
		 */
		var MultipleChoiceQuestionView = QuestionView.extend({
			className: 'question question-multiple',
			template: _.template($('#tpl-ib-edu-multiplechoicequestion').html()),

			initialize: function() {
				QuestionView.prototype.initialize.apply(this);
				this.collection = new MultipleChoiceAnswersCollection();
				this.listenTo(this.collection, 'add', this.renderChoice);
				this.listenTo(this.collection, 'remove', this.onChoiceRemove);
			},

			render: function() {
				var choice;
				var i;
				var question_id = this.model.get('id');

				QuestionView.prototype.render.apply(this);

				// Populate question with existing choices.
				if ( educatorQuizChoices['question_' + question_id] ) {
					for ( i = 0; i < educatorQuizChoices['question_' + question_id].length; ++i ) {
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

			renderChoice: function(choice) {
				var view = new MultipleChoiceAnswerView({
					model: choice,
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

			onChoiceRemove: function() {
				if (this.collection.length < 1) {
					this.$el.find('.no-answers').show();
					this.$el.find('.question-answers > table > thead').hide();
				}
			},

			events: function() {
				return _.extend({
					'click button.add-answer': 'addChoice',
					'updateChoicesOrder': 'updateChoicesOrder'
				}, this.constructor.__super__.events);
			},

			addChoice: function(e) {
				e.preventDefault();
				var choice = new MultipleChoiceAnswerModel();
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
			},

			updateChoicesOrder: function() {
				_.each(this.collection.models, function(choice) {
					choice.trigger('updateOrderFromView');
				});
			},

			saveQuestion: function(e) {
				e.preventDefault();
				QuestionView.prototype.saveQuestion.apply(this);

				var that = this;

				// Setup question data.
				var newData = {};
				newData.question = this.$el.find('input.question-text').val();
				newData.menu_order = this.$el.index();
				
				// Save question choices to database.
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
			}
		});

		/**
		 * Written Answer Question View.
		 */
		var WrittenAnswerQuestionView = QuestionView.extend({
			className: 'question question-writtenanswer',
			template: _.template($('#tpl-ib-edu-writtenanswerquestion').html()),

			initialize: function() {
				QuestionView.prototype.initialize.apply(this);
			},

			render: function() {
				QuestionView.prototype.render.apply(this);
			},

			saveQuestion: function(e) {
				e.preventDefault();
				QuestionView.prototype.saveQuestion.apply(this);

				var that = this;

				// Setup question data.
				var newData = {};
				newData.question = this.$el.find('input.question-text').val();
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
			}
		});

		/**
		 * Quiz View.
		 */
		var QuizView = Backbone.View.extend({
			el: $('#ib-edu-quiz'),

			initialize: function() {
				this.collection = new Questions(educatorQuizQuestions);
				this.render();
			},

			render: function() {
				var that = this;

				_.each(this.collection.models, function(question) {
					that.renderQuestion(question);
				}, this);
			},

			renderQuestion: function(question) {
				var questionType = question.get('question_type');
				var view, meta, answer, i;

				question.set('lesson_id', lesson_id);

				switch (question.get('question_type')) {
					case 'multiplechoice':
						view = new MultipleChoiceQuestionView({model: question});
						break;

					case 'writtenanswer':
						view = new WrittenAnswerQuestionView({model: question});
						break;
				}

				view.render();
				$('#ib-edu-questions').append(view.el);
			},

			events: {
				'click button.add-question': 'addQuestion',
				'updateQuestionsOrder': 'updateQuestionsOrder'
			},

			addQuestion: function(e) {
				e.preventDefault();

				var view;
				var question = new QuestionModel();
				var question_type = questionType.val();
				question.set('lesson_id', lesson_id);
				question.set('question_type', question_type);

				switch (question_type) {
					case 'multiplechoice':
						view = new MultipleChoiceQuestionView({model: question});
						break;

					case 'writtenanswer':
						view = new WrittenAnswerQuestionView({model: question});
						break;
				}

				this.collection.add(question);
				view.render();
				view.openQuestion();
				$('#ib-edu-questions').append(view.el);
			},

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
					url: ajaxurl + '?action=ibedu_sort_questions',
					data: {
						lesson_id: lesson_id,
						question_id: question_id,
						order: order,
						_wpnonce: nonce
					},
					success: function(response) {},
					complete: function() {
						$('#ib-edu-questions').sortable('enable');
					}
				});
			}
		});

		var questions = new QuizView();

		// Sortable
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
	});

})(jQuery);