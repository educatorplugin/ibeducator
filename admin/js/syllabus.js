(function($) {
	'use strict';

	function addLessonCollection(lessons) {
		lessonCollections.push(lessons);
	}

	function removeLessonCollection(lessons) {
		for (var i = 0; i < lessonCollections.length; ++i) {
			if (lessonCollections[i] === lessons) {
				lessonCollections.splice(i, 1);
				break;
			}
		}
	}

	function lessonExists(lessonId) {
		var i, lessonExists;

		for (i = 0; i < lessonCollections.length; ++i) {
			lessonExists = false;

			_.each(lessonCollections[i].models, function(lesson) {
				if (lesson.get('post_id') === lessonId) {
					lessonExists = true;
					return false;
				}
			});

			if (lessonExists) {
				return true;
			}
		}

		return false;
	}

	var lessonCollections = [];
	var uniqueGroupId = 1;

	/**
	 * Lesson Model.
	 */
	var Lesson = Backbone.Model.extend({
		defaults: {
			post_id: 0,
			group_id: 0,
			title: ''
		}
	});

	/**
	 * Lessons Collection.
	 */
	var Lessons = Backbone.Collection.extend({
		model: Lesson
	});

	/**
	 * Lesson View.
	 */
	var LessonView = Backbone.View.extend({
		tagName: 'li',
		className: 'lesson',

		template: _.template($('#edr-syllabus-lesson-view').html()),

		events: {
			'click button.remove-lesson': 'removeLesson'
		},

		initialize: function() {
			this.listenTo(this.model, 'destroy', this.remove);
		},

		render: function() {
			this.$el.html(this.template(this.model.toJSON()));

			return this;
		},

		removeLesson: function() {
			this.model.destroy();
		}
	});

	/**
	 * Group Model.
	 */
	var Group = Backbone.Model.extend({
		defaults: {
			group_id: 0,
			title: ''
		}
	});

	/**
	 * Groups Collection.
	 */
	var Groups = Backbone.Collection.extend({
		model: Group
	});

	/**
	 * Group View.
	 */
	var GroupView = Backbone.View.extend({
		tagName: 'li',

		template: _.template($('#edr-syllabus-group-view').html()),

		events: {
			'click button.add-lesson': 'addLesson',
			'click button.remove-group': 'removeGroup'
		},

		lessons: null,

		initialize: function(options) {
			var i;

			this.lessons = new Lessons();

			if (options.lessons) {
				this.lessons.add(options.lessons);
			}

			this.listenTo(this.lessons, 'add', this.renderLesson);
			this.listenTo(this.model, 'destroy', this.remove);

			addLessonCollection(this.lessons);
		},

		render: function() {
			var that = this;

			this.$el.html(this.template(this.model.toJSON()));

			this.autocomplete = ibEducatorAutocomplete(this.$el.find('input.select-lessons:first').get(0), {
				key: 'id',
				value: 'title',
				searchBy: 'title',
				nonce: edrSyllabusText.autoCompleteNonce,
				url: ajaxurl,
				entity: 'admin_syllabus_lessons'
			});

			this.$el.find('ul.lessons').sortable({
				handle: '.handle',
				axis: 'y',
				placeholder: 'edr-placeholder',
				forcePlaceholderSize: true,
				start: function(e, ui) {
					ui.item.data('edr-index', ui.item.index());
				},
				update: function(e, ui) {
					var comparator = that.lessons.comparator;
					var model = that.lessons.at(ui.item.data('edr-index'));

					delete that.lessons.comparator;
					that.lessons.remove(model, {silent: true});
					that.lessons.add(model, {silent: true, at: ui.item.index()});
					that.lessons.comparator = comparator;
					that.lessons.trigger('reset', that.lessons);
				}
			});

			if (this.lessons.length) {
				_.each(this.lessons.models, function(lessonModel) {
					that.lessons.trigger('add', lessonModel);
				});
			}

			return this;
		},

		renderLesson: function(lessonModel) {
			var lessonView = new LessonView({
				model: lessonModel
			});

			this.$el.find('ul.lessons').append(lessonView.render().$el);
		},

		addLesson: function(e) {
			e.preventDefault();

			var data = {};

			data.post_id = parseInt(this.autocomplete.getValue(), 10);

			if (isNaN(data.post_id)) {
				data.post_id = 0;
			}

			if (data.post_id === 0 || lessonExists(data.post_id)) {
				return;
			}

			data.group_id = this.model.get('group_id');
			data.title = this.autocomplete.getLabel();

			this.lessons.add(data);
		},

		removeGroup: function(e) {
			e.preventDefault();

			this.model.destroy();
		},

		remove: function() {
			_.each(this.lessons.models, function(lessonModel) {
				lessonModel.trigger('destroy');
			});

			removeLessonCollection(this.lessons);

			this.autocomplete.destroy();
			this.$el.find('ul.lessons').sortable('destroy');
			
			Backbone.View.prototype.remove.apply(this, arguments);
		}
	});

	/**
	 * App View.
	 */
	var AppView = Backbone.View.extend({
		el: '#edr-syllabus',

		events: {
			'click button.add-group': 'addGroup'
		},

		loading: true,

		initialize: function() {
			var that = this;
			var groupModel = null;
			var groupView = null;
			var groupViewAttrs = null;
			var groupsHTML = null;

			this.groups = new Groups();

			this.listenTo(this.groups, 'add', this.renderGroup);

			// Populate with initial data.
			if (typeof edrSyllabus === 'object') {
				groupsHTML = document.createDocumentFragment();

				for (var i = 0; i < edrSyllabus.length; ++i) {
					groupModel = this.groups.add({
						group_id: uniqueGroupId++,
						title: edrSyllabus[i].title
					}, {
						silent: true
					});

					groupViewAttrs = {};
					groupViewAttrs.model = groupModel;

					if (edrSyllabus[i].lessons) {
						for (var j = 0; j < edrSyllabus[i].lessons.length; ++j) {
							edrSyllabus[i].lessons[j].group_id = groupModel.get('group_id');
						}

						groupViewAttrs.lessons = edrSyllabus[i].lessons;
					}

					groupView = new GroupView(groupViewAttrs);

					groupsHTML.appendChild(groupView.render().el);
				}

				this.$el.find('> .groups').append(groupsHTML);
			}

			this.$el.find('> .groups').sortable({
				axis: 'y',
				handle: '.handle',
				forcePlaceholderSize: true,
				placeholder: 'edr-placeholder',
				start: function(e, ui) {
					ui.item.data('edr-index', ui.item.index());
				},
				update: function(e, ui) {
					var comparator = that.groups.comparator;
					var model = that.groups.at(ui.item.data('edr-index'));

					delete that.groups.comparator;
					that.groups.remove(model, {silent: true});
					that.groups.add(model, {silent: true, at: ui.item.index()});
					that.groups.comparator = comparator;
					that.groups.trigger('reset', that.groups);
				}
			});

			this.loading = false;
			this.$el.find('input[name="edr_syllabus_status"]').val('ready');
			this.$el.find('div.edr-loading').hide();
		},

		renderGroup: function(groupModel) {
			var groupView = new GroupView({
				model: groupModel
			});

			this.$el.find('> .groups').append(groupView.render().$el);
		},

		addGroup: function(e) {
			e.preventDefault();

			if (this.loading) {
				return;
			}

			this.groups.add({
				group_id: uniqueGroupId++,
				title: ''
			});
		}
	});

	var av = new AppView();

})(jQuery);
