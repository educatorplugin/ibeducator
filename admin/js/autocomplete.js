(function($) {
	function Autocomplete(input, options) {
		this.input = $(input);
		this.options = options;
		this.running = false;
		this.ajaxRequest = null;
		this.ajaxTimeout = null;
		this.visible = false;
		this.multiple = false;

		this.search = null;
		this.items = null;

		this.init();
	}

	/**
	 * Initialize.
	 */
	Autocomplete.prototype.init = function() {
		var that = this;

		// Hide the input field.
		this.input.attr('type', 'hidden');
		this.input.addClass('edr-autocomplete');

		// Create select element.
		this.trigger = $('<div class="chosen-values"></div>');

		var label = this.input.attr('data-label');
		var value = this.input.val();

		if (label && value) {
			this.setLabel(label, value);
		}

		if (this.isDisabled()) {
			this.input.parent().addClass('disabled');
		}

		this.trigger.insertBefore(this.input);

		// Show choices box.
		this.trigger.on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			that.autocomplete(0);
		});

		// Remove a choice.
		this.trigger.on('click', '.remove-value', function(e) {
			e.stopPropagation();
			e.preventDefault();

			if (!that.isDisabled()) {
				that.remove($(this).parent().data('value'));
			}
		});

		// Add choices div.
		this.choicesDiv = $('<div class="ib-edu-autocomplete-choices"><div class="choices-filter"><input type="text"></div><div class="choices"></div></div>');
		this.choicesDiv.appendTo('body');

		// Get choices filter input.
		var choicesFilter = this.choicesDiv.find('> .choices-filter > input');

		choicesFilter.on('keyup', function() {
			that.autocomplete(1);
		});

		choicesFilter.on('click', function(e) {
			e.stopPropagation();
		});

		// Process choice selection.
		this.choicesDiv.on('click', 'a', function(e) {
			e.preventDefault();
			e.stopPropagation();

			that.add(this.innerHTML, this.getAttribute('data-value'));
			that.clearChoices();
		});

		$('body').on('click.edr-autocomplete', function() {
			that.clearChoices();
		});
	};

	Autocomplete.prototype.isDisabled = function() {
		return (this.input.attr('disabled') === 'disabled');
	};

	/**
	 * Set input value.
	 */
	Autocomplete.prototype.setValue = function(value) {
		this.input.val(value);
	};

	/**
	 * Get input value.
	 *
	 * @return {String}
	 */
	Autocomplete.prototype.getValue = function() {
		return this.input.val();
	};

	/**
	 * Add item.
	 *
	 * @param {string} label
	 * @param {string} value
	 */
	Autocomplete.prototype.add = function(label, value) {
		this.setValue(value);
		this.setLabel(label, value);
	};

	/**
	 * Remove item.
	 *
	 * @param {string} value
	 */
	Autocomplete.prototype.remove = function(value) {
		this.trigger.find('> div[data-value="' + value + '"]').remove();
		this.input.val('');
	};

	/**
	 * Get value.
	 */
	Autocomplete.prototype.getFilterValue = function() {
		return this.choicesDiv.find('> .choices-filter > input').val();
	};

	/**
	 * Set input label.
	 *
	 * @param {string} label
	 * @param {string} value
	 */
	Autocomplete.prototype.setLabel = function(label, value) {
		var firstLabel, labelHTML;

		value = value || '';

		if (label === '') {
			label = '&nbsp;';
		}

		labelHTML = '<div class="chosen-value" data-value="' + value + '"><span>' + label + '</span>';

		if (value !== '') {
			labelHTML += '<button class="remove-value">&times;</button>';
		}

		labelHTML += '</div>';

		if (this.multiple) {
			this.trigger.append(labelHTML);
		} else {
			this.trigger.html(labelHTML);
		}
	};

	/**
	 * Get label.
	 *
	 * @return {String}
	 */
	Autocomplete.prototype.getLabel = function(value) {
		return this.trigger.find('> div[data-value="' + value + '"] > span').html();
	};

	/**
	 * Display choices.
	 *
	 * @param {Array} choices
	 */
	Autocomplete.prototype.display = function(choices) {
		var choicesContainer, i, choiceClasses;

		choicesContainer = this.choicesDiv.find('.choices');
		choicesContainer.html('');

		for (i = 0; i < choices.length; ++i) {
			choiceClasses = ['choice'];

			if (choices[i]._lvl) {
				choiceClasses.push('level-' + choices[i]._lvl);
			}

			choicesContainer.append('<a data-value="' + choices[i][this.options.key] + '" class="' + choiceClasses.join(' ') + '">' + choices[i][this.options.value] + '</a>');
		}
	};

	/**
	 * Send AJAX request.
	 */
	Autocomplete.prototype.sendRequest = function() {
		var that = this;

		if (this.ajaxRequest) {
			// Stop current request.
			this.ajaxRequest.abort();
		}

		this.search = this.getFilterValue();

		// Send AJAX request.
		this.ajaxRequest = $.ajax({
			type: 'get',
			cache: false,
			dataType: 'json',
			url: this.options.url,
			data: {
				input: this.search,
				action: 'ib_educator_autocomplete',
				entity: this.options.entity,
				_wpnonce: this.options.nonce
			},
			success: function(response) {
				if (response) {
					that.items = response;

					that.display(that.items);
				}

				that.running = false;
			},
			error: function() {
				that.running = false;
			}
		});
	},

	/**
	 * Fetch and display choices based on the choices filter.
	 */
	Autocomplete.prototype.autocomplete = function(ajaxTimeout) {
		var result,
			inputValue,
			offset,
			that = this;

		ajaxTimeout = ajaxTimeout || 0;

		if (this.running || this.isDisabled()) {
			return;
		}

		this.running = true;

		// Hide choices of the other autocomplete elements on the page.
		this.clearOtherChoices();

		// Show choices container.
		if (!this.choicesDiv.is(':visible')) {
			offset = this.trigger.offset();

			this.choicesDiv.css({
				left: offset.left + 'px',
				top: (offset.top + this.trigger.outerHeight()) + 'px',
				width: (this.trigger.outerWidth() - 2) + 'px',
				display: 'block'
			});

			this.trigger.parent().addClass('open');

			this.choicesDiv.find('> .choices-filter > input').focus();
		}

		// Display items.
		if (this.items && this.search === this.getFilterValue()) {
			// If the search input didn't change, display cached items.
			this.display(this.items);

			this.running = false;
		} else if (this.options.items) {
			// if the items are set in options, don't do a request,
			// just display this items.
			result = [];
			inputValue = this.getFilterValue();

			for (var i = 0; i < this.options.items.length; ++i) {
				if (this.options.items[i][this.options.searchBy].indexOf(inputValue) !== -1) {
					result.push(this.options.items[i]);
				}
			}

			this.display(result);

			this.running = false;
		} else {
			// Use AJAX to fetch items.
			if (this.ajaxTimeout) {
				clearTimeout(this.ajaxTimeout);
			}

			if (ajaxTimeout) {
				this.ajaxTimeout = setTimeout(function() {
					that.sendRequest();
				}, 800);
			} else {
				this.sendRequest();
			}
		}
	};

	/**
	 * Clear choices div.
	 */
	Autocomplete.prototype.clearChoices = function() {
		this.trigger.parent().removeClass('open');
		this.choicesDiv.find('.choices').html('');
		this.choicesDiv.css('display', 'none');
	};

	/**
	 * Clear divs of other choices.
	 */
	Autocomplete.prototype.clearOtherChoices = function() {
		var other = $('input.edr-autocomplete').not(this.input);

		other.each(function() {
			$(this).data('edrAutocomplete').clearChoices();
		});
	};

	Autocomplete.prototype.destroy = function() {
		if (this.ajaxRequest) {
			this.ajaxRequest.abort();
		}

		if (this.ajaxTimeout) {
			clearTimeout(this.ajaxTimeout);
		}

		this.trigger.off();
		this.choicesDiv.find('> .choices-filter > input').off();
		this.choicesDiv.off();
		$('body').off('.edr-autocomplete');

		this.choicesDiv.remove();

		this.input.data('edrAutocomplete', null);

		this.choicesDiv = null;
		this.input = null;
	};

	window.ibEducatorAutocomplete = function(input, options) {
		var autocomplete = new Autocomplete(input, options);

		$.data(input, 'edrAutocomplete', autocomplete);

		return autocomplete;
	};
})(jQuery);
