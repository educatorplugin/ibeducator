(function($) {
	function Autocomplete( input, options ) {
		this.input = $(input);
		this.options = options;
		this.running = false;
		this.ajaxRequest = null;
		this.ajaxTimeout = null;
		this.visible = false;

		this.init();
	}

	/**
	 * Initialize.
	 */
	Autocomplete.prototype.init = function() {
		var that = this;

		// Hide the input field.
		this.input.attr('type', 'hidden');

		// Create select element.
		this.trigger = $('<a href="#"><span class="value"></span></a>');
		this.setLabel(this.input.attr('data-label'));

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

			that.setValue(this.getAttribute('data-value'));
			that.setLabel(this.innerHTML);
			that.clearChoices();
		});

		$('body').on('click', function() {
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
	 * Get value.
	 */
	Autocomplete.prototype.getFilterValue = function() {
		return this.choicesDiv.find('> .choices-filter > input').val();
	};

	/**
	 * Set input label.
	 */
	Autocomplete.prototype.setLabel = function(label) {
		if (label === '') {
			label = '&nbsp;';
		}

		this.trigger.find('.value').html(label);
	};

	/**
	 * Display choices.
	 *
	 * @param {Array} choices
	 */
	Autocomplete.prototype.display = function(choices) {
		var offset, choicesContainer, i, choiceClasses;

		this.clearOtherChoices();

		choicesContainer = this.choicesDiv.find('.choices');
		choicesContainer.html('');

		if (!this.choicesDiv.is(':visible')) {
			offset = this.trigger.offset();

			this.choicesDiv.css({
				left: offset.left + 'px',
				top: (offset.top + this.trigger.outerHeight()) + 'px',
				width: (this.trigger.outerWidth() - 2) + 'px',
				display: 'block'
			});

			this.trigger.parent().addClass('open');
		}

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

		// Send AJAX request.
		this.ajaxRequest = $.ajax({
			type: 'get',
			cache: false,
			dataType: 'json',
			url: this.options.url,
			data: {
				input: this.getFilterValue(),
				action: 'ib_educator_autocomplete',
				entity: this.options.entity,
				_wpnonce: this.options.nonce
			},
			success: function(response) {
				if (response) {
					that.display(response);
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
		ajaxTimeout = ajaxTimeout || 0;

		if (this.running || this.isDisabled()) {
			return;
		}

		this.running = true;

		var that = this;

		if (this.options.items) {
			var result = [];
			var inputValue = this.getFilterValue();

			for (var i = 0; i < this.options.items.length; ++i) {
				if (this.options.items[i][this.options.searchBy].indexOf(inputValue) !== -1) {
					result.push(this.options.items[i]);
				}
			}

			this.display(result);
			this.running = false;
		} else {
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
		var other = $('div.ib-edu-autocomplete-choices').not(this.choicesDiv);

		other.find('.choices').html('');
		other.css('display', 'none');
	};

	window.ibEducatorAutocomplete = function(input, options) {
		var autocomplete = new Autocomplete(input, options);
	};
})(jQuery);