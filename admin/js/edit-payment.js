(function($) {
	'use strict';

	var statesRequest = null;

	var getStates = function() {
		var data = {
			action: 'ib_edu_get_states',
			country: $('#ib-edu-country').val(),
			_wpnonce: $('#ib-edu-get-states-nonce').val()
		};

		if (statesRequest) {
			statesRequest.abort();
		}

		statesRequest = $.ajax({
			type: 'GET',
			url: ajaxurl,
			dataType: 'json',
			data: data,
			success: function(response) {
				var field, i;

				if (response && response.length) {
					field = $('<select id="ib-edu-state" name="state"></select>');
					field.append('<option value=""></option>');

					for (i = 0; i < response.length; ++i) {
						field.append('<option value="' + response[i].code + '">' + response[i].name + '</option>');
					}

					$('#ib-edu-state').replaceWith(field);
				} else {
					$('#ib-edu-state').replaceWith('<input type="text" id="ib-edu-state" name="state" class="regular-text">');
				}
			}
		});
	};

	$('#ib-edu-country').on('change', function() {
		getStates();
	});
})(jQuery);