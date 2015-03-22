(function($) {
	'use strict';

	/**
	 * Tax Class Model.
	 */
	var TaxClass = Backbone.Model.extend({
		idAttribute: 'name',
		defaults: {
			name: null,
			description: ''
		},

		sync: function(method, model, options) {
			options || (options = {});
			options.url = ajaxurl;
			
			switch (method) {
				case 'read':
					break;

				case 'create':
					options.url += '?action=ib_edu_taxes&method=add-tax-class';
					break;

				case 'update':
					options.url += '?action=ib_edu_taxes&method=edit-tax-class&name=' + this.id;
					break;

				case 'delete':
					options.url += '?action=ib_edu_taxes&method=delete-tax-class&name=' + this.id + '&_wpnonce=' + eduTaxAppNonce;
					break;
			}

			return Backbone.sync.apply(this, arguments);
		},

		validate: function(attrs, options) {
			var regex = /^[a-zA-Z0-9-_]+$/;
			var errors = [];

			if (!regex.test(attrs.name)) {
				errors.push(eduTaxAppErrors.name);
			}

			if (this.isNew() && theAppData.taxClasses.get(attrs.name) !== undefined) {
				errors.push(eduTaxAppErrors.nameNotUnique);
			}

			if (attrs.description.length === 0) {
				errors.push(eduTaxAppErrors.description);
			}

			if ( errors.length ) {
				return errors;
			}
		}
	});
	
	/**
	 * Tax Rate Model.
	 */
	var TaxRate = Backbone.Model.extend({
		defaults: {
			ID: null,
			tax_class: '',
			country: '',
			state: '',
			name: '',
			rate: 0,
			priority: 0,
			rate_order: 0,
			country_name: '',
			state_name: ''
		},
		idAttribute: 'ID',

		validate: function(attrs, options) {
			var errors = [];

			if (attrs.state !== attrs.state.replace(/(<([^>]+)>)/ig, '')) {
				errors.push('state');
			}

			if (isNaN(attrs.rate)) {
				errors.push('rate');
			}

			if (isNaN(attrs.priority) || attrs.priority < 0) {
				errors.push('priority');
			}

			if (errors.length) {
				return errors;
			}
		},

		sync: function(method, model, options) {
			options || (options = {});
			options.url = ajaxurl;
			
			switch (method) {
				case 'read':
					break;

				case 'create':
				case 'update':
					options.url += '?action=ib_edu_taxes&method=rates';
					break;

				case 'delete':
					options.url += '?action=ib_edu_taxes&method=rates&ID=' + this.id + '&_wpnonce=' + eduTaxAppNonce;
					break;
			}

			return Backbone.sync.apply(this, arguments);
		}
	});

	/**
	 * Tax Classes Collection.
	 */
	var TaxClasses = Backbone.Collection.extend({
		model: TaxClass
	});

	/**
	 * Tax Rates Collection.
	 */
	var TaxRates = Backbone.Collection.extend({
		model: TaxRate,
		url: function() {
			return ajaxurl + '?action=ib_edu_taxes&method=rates&class_name=' + this.taxClassName;
		}
	});

	/**
	 * Taxes Controller.
	 */
	var TaxesController = {
		index: function() {
			var view;

			if (!theAppData.taxClasses) {
				theAppData.taxClasses = new TaxClasses(eduTaxClasses);
			}
			
			view = new TaxClassesView({
				collection: theAppData.taxClasses
			});
			view.render();
			theAppView.render(view);
		},

		edit: function(name) {
			var taxClass,
				view;

			if (name) {
				taxClass = theAppData.taxClasses.get(name);
			} else {
				taxClass = new TaxClass();
			}

			view = new EditTaxClassView({
				model: taxClass
			});

			view.render();

			theAppView.render(view);
		},

		editRates: function(name) {
			var view = new TaxRatesView({
				taxClassName: name
			});

			theAppView.render(view);
		}
	};

	/**
	 * Tax Class View.
	 */
	var TaxClassView = Backbone.View.extend({
		tagName: 'tr',
		template: _.template($('#edu-tax-class').html()),
		events: {
			'click .edit-tax-class': 'edit',
			'click .edit-rates': 'editRates',
			'click .delete-tax-class': 'delete'
		},

		initialize: function() {
			this.listenTo(this.model, 'change', this.render);
			this.listenTo(this.model, 'destroy', this.remove);
		},

		render: function() {
			this.$el.html(this.template(this.model.toJSON()));

			if (this.model.get('name') === 'default') {
				this.$el.find('.delete-tax-class').attr('disabled', 'disabled');
			}
		},

		edit: function(e) {
			e.preventDefault();
			TaxesController.edit(this.model.id);
		},

		editRates: function(e) {
			e.preventDefault();
			TaxesController.editRates(this.model.id);
		},

		delete: function(e) {
			e.preventDefault();
			this.model.destroy({wait: true});
		}
	});

	/**
	 * Tax Classes View.
	 */
	var TaxClassesView = Backbone.View.extend({
		tagName: 'div',
		template: _.template($('#edu-tax-classes').html()),
		events: {
			'click .add-new-class': 'addNew'
		},

		initialize: function(options) {
			this.collection = options.collection;
		},

		render: function() {
			var that = this;

			this.$el.html(this.template());

			_.each(this.collection.models, function(taxClass) {
				that.renderTaxClass(taxClass);
			});
		},

		renderTaxClass: function(taxClass) {
			var view = new TaxClassView({model: taxClass});
			view.render();
			this.$el.find('> table').append(view.el);
		},

		addNew: function(e) {
			e.preventDefault();
			TaxesController.edit();
		},

		remove: function() {
			// Remove child views, because we keep the collection in memory.
			_.each(this.collection.models, function(model) {
				model.trigger('destroy');
			});

			Backbone.View.prototype.remove.apply(this, arguments);
		}
	});

	/**
	 * Edit Tax Class View.
	 */
	var EditTaxClassView = Backbone.View.extend({
		tagName: 'div',
		template: _.template($('#edu-edit-tax-class').html()),
		events: {
			'click .save-tax-class': 'save',
			'click .cancel': 'cancel'
		},

		initialize: function() {
			this.listenTo(this.model, 'invalid', this.onInvalid);
		},

		render: function() {
			this.$el.html(this.template(this.model.toJSON()));

			if (!this.model.isNew()) {
				this.$el.find('input.short-name').attr('disabled', 'disabled');
				this.$el.find('> .title-add-new').remove();
			} else {
				this.$el.find('> .title-edit').remove();
			}
		},

		onInvalid: function(model, errors) {
			var err = this.$el.find('> .errors');

			if (!err.length) {
				err = $('<ul class="errors"></ul>');
				this.$el.prepend(err);
			}

			err.html('');

			for (var i = 0; i < errors.length; ++i) {
				err.append('<li>' + errors[i] + '</li>');
			}

			this.$el.find('button').removeAttr('disabled');
		},

		save: function(e) {
			var that = this;

			e.preventDefault();

			this.$el.find('button').attr('disabled', 'disabled');

			this.model.save({
				name: this.$el.find('input.short-name').val().trim(),
				description: this.$el.find('input.description').val().trim(),
				_wpnonce: eduTaxAppNonce
			}, {
				wait: true,
				success: function(model) {
					theAppData.taxClasses.add(model);
					TaxesController.index();
				},
				error: function() {
					that.$el.find('button').removeAttr('disabled');
				}
			});
		},

		cancel: function(e) {
			e.preventDefault();
			TaxesController.index();
		}
	});

	/**
	 * Tax Rate View.
	 */
	var TaxRateView = Backbone.View.extend({
		tagName: 'tr',
		templateView: _.template($('#edu-tax-rate').html()),
		templateEdit: _.template($('#edu-tax-rate-edit').html()),
		viewMode: '',
		waiting: false,
		events: {
			'click .edit-rate': 'editRate',
			'click .save-rate': 'saveRate',
			'click .delete-rate': 'deleteRate',
			'change select.country': 'updateStates'
		},

		initialize: function() {
			this.listenTo(this.model, 'destroy', this.remove);
			this.listenTo(this.model, 'invalid', this.onInvalid);
			this.listenTo(this.model, 'updateRateOrder', this.updateRateOrder);
		},

		render: function() {
			if (this.model.isNew()) {
				this.setMode('edit');
			} else {
				this.setMode('view');
			}
		},

		deleteRate: function(e) {
			e.preventDefault();
			this.model.destroy();
		},

		onInvalid: function(model, errors) {
			for (var i = 0; i < errors.length; ++i) {
				switch (errors[i]) {
					case 'state':
						this.$el.find('> td.state > .state').addClass('error');
						break;

					case 'rate':
						this.$el.find('input.rate').addClass('error');
						break;
				}
			}
		},

		addStateInput: function() {
			this.$el.find('td.state').append('<input type="text" class="state" value="' + this.model.get('state') + '">');
		},

		updateStates: function() {
			var that = this;

			if (this.waiting) {
				return;
			}

			this.waiting = true;

			this.$el.find('td.state').html('');

			var statesForCountry = this.$el.find('select.country').val();

			if (statesForCountry == '') {
				statesForCountry = this.model.get('country');
			}

			$.ajax({
				type: 'GET',
				cache: false,
				url: ajaxurl,
				dataType: 'json',
				data: {
					action: 'ib_edu_get_states',
					country: statesForCountry,
					_wpnonce: eduGetStatesNonce
				},
				complete: function() {
					that.waiting = false;
				},
				success: function(response) {
					var state, select, curState, i;

					if (response && response.length) {
						select = $('<select class="state">');
						select.append('<option value=""></option>');

						curState = (that.model.get('country') === statesForCountry) ? that.model.get('state') : '';

						for (i = 0; i < response.length; ++i) {
							select.append('<option value="' + response[i].code + '"' + (curState === response[i].code ? ' selected="selected"' : '') + '>' + response[i].name + '</option>');
						}

						that.$el.find('td.state').append(select);
					} else {
						that.addStateInput();
					}
				},
				error: function() {
					that.addStateInput();
				}
			});
		},

		getInput: function() {
			return {
				country: this.$el.find('> td.country > select').val(),
				state: this.$el.find('> td.state > .state').val(),
				name: this.$el.find('> td.name > input').val(),
				rate: this.$el.find('> td.rate > input').val(),
				priority: this.$el.find('> td.priority > input').val(),
				tax_class: this.model.collection.taxClassName
			};
		},

		setMode: function(mode) {
			var stateSelect;

			if (mode === 'view') {
				if (this.viewMode === 'edit') {
					// Get country name.
					this.model.set('country_name', this.$el.find('> td.country > select > option[value="' + this.model.get('country') + '"]').text());

					// Get state name.
					stateSelect = this.$el.find('> td.state > .state');

					if ( stateSelect.length && stateSelect[0].nodeName === 'SELECT' ) {
						this.model.set('state_name', stateSelect.find('option[value="' + this.model.get('state') + '"]').text());
					} else {
						this.model.set('state_name', this.model.get('state'));
					}
				}

				this.$el.html(this.templateView(this.model.toJSON()));
				this.viewMode = 'view';
			} else {
				this.$el.html(this.templateEdit(this.model.toJSON()));
				this.$el.find('> td.country > select').val(this.model.get('country'));
				this.updateStates();
				this.viewMode = 'edit';
			}
		},

		editRate: function(e) {
			e.preventDefault();

			this.setMode('edit');
		},

		saveRate: function(e) {
			e.preventDefault();
			var that, input;

			if (this.waiting) {
				return;
			}

			this.waiting = true;
			that = this;
			input = this.getInput();
			input._wpnonce = eduTaxAppNonce;

			this.model.save(input, {
				wait: true,
				success: function() {
					that.waiting = false;
					that.setMode('view');
				},
				error: function() {
					that.waiting = false;
				}
			});
		},

		updateRateOrder: function() {
			this.model.set('rate_order', this.$el.index());
		}
	});

	/**
	 * Tax Rates View.
	 */
	var TaxRatesView = Backbone.View.extend({
		tagName: 'div',
		template: _.template($('#edu-tax-rates').html()),
		saveOrderAJAX: null,
		events: {
			'click .add-new-rate': 'addNewRate',
			'click .cancel': 'cancel',
			'click .save-order': 'saveOrder',
		},

		initialize: function(options) {
			var that = this;

			this.collection = new TaxRates();
			this.collection.taxClassName = options.taxClassName;

			this.listenTo(this.collection, 'add', this.renderTaxRate);
			this.render();
			this.collection.fetch({
				success: function(collection) {
					that.$el.find('> table > tbody > .loading').remove();
				}
			});
		},

		render: function() {
			var that = this;
			this.$el.html(this.template());

			// Sortable.
			this.$el.find('> table > tbody').sortable({
				axis: 'y',
				items: 'tr',
				handle: 'div.ib-edu-sort-y',
				placeholder: 'placeholder',
				helper: function(e, helper) {
					helper.children().each(function(i) {
						var td = $(this);
						td.width(td.width());
					});

					return helper;
				},
				start: function(e, ui) {
					ui.placeholder.height(ui.item.height() - 2);
				},
				update: function(e, ui) {
					//that.saveOrder();
					that.$el.find('.save-order').removeAttr('disabled');
				},
				stop: function(e, ui) {
					ui.item.children().removeAttr('style');
				}
			});
		},

		saveOrder: function(e) {
			var data = {
				order: {},
				_wpnonce: eduTaxAppNonce
			};
			var that = this;

			e.preventDefault();

			this.$el.find('.save-order').attr('disabled', 'disabled');

			_.each(this.collection.models, function(model) {
				model.trigger('updateRateOrder');

				if (!model.isNew()) {
					data.order[model.id] = model.get('rate_order');
				}
			});

			if (this.saveOrderAJAX) {
				this.saveOrderAJAX.abort();
			}

			this.saveOrderAJAX = $.ajax({
				type: 'POST',
				data: data,
				url: ajaxurl + '?action=ib_edu_taxes&method=save-rates-order',
				error: function() {
					that.$el.find('.save-order').removeAttr('disabled');
				}
			});
		},

		renderTaxRate: function(taxRate) {
			var view = new TaxRateView({
				model: taxRate
			});

			view.render();

			this.$el.find('> table > tbody').append(view.el);
		},

		addNewRate: function(e) {
			var maxOrder = -1;
			var model;
			e.preventDefault();

			_.each(this.collection.models, function(model) {
				var order = model.get('rate_order');

				if (maxOrder < order) {
					maxOrder = order;
				}
			});

			model = new TaxRate();
			model.set('rate_order', maxOrder + 1);
			this.collection.add(model);
		},

		cancel: function(e) {
			e.preventDefault();
			TaxesController.index();
		}
	});

	/**
	 * Main View.
	 */
	var MainView = Backbone.View.extend({
		el: $('#edu-tax-classes-container'),
		currentView: null,

		render: function(view) {
			if (this.currentView) {
				this.currentView.remove();
			}

			this.currentView = view;
			this.$el.html(this.currentView.el);
		}
	});

	var theAppData = {};
	var theAppView = new MainView();
	TaxesController.index();

})(jQuery);