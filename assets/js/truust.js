(function($) {
	var Truust = {
		init: function() {
			$('#truust-validator-btn').on('click', function() {
				$('#truust-validator-btn').prop('disabled', true).addClass('working');
				$('#mainform .submit button').trigger('click');
			});
		}
	}

	$(document).ready(function() {
		Truust.init();
	});
})(jQuery);
