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
