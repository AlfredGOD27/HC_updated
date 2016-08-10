(function($) {

	$('.subscribe-form').on( 'submit', function(e) {
		e.preventDefault();

		var self = $(this),
			email_field = self.find('input[type="email"]'),
			email = email_field.val();

		if( self.hasClass('processing') )
			return;

		self.find('.result').remove();
		self.addClass('processing');
		$.ajax({
			url: ajax_object.ajaxurl,
			type: 'POST',
			data: {
				action: 'hc_ajax_subscribe',
				email: email
			},
			success: function( json ) {
				var data = JSON.parse(json);

				self.removeClass('processing');
				self.addClass(data.status);

				self.find('.email-container').append('<i title="' + data.message + '" class="result ' + data.status + '"></i>');

				if( 'success' === data.status ) {
					email_field.prop( 'readonly', true );
					self.find('button[type="submit"]').prop( 'disabled', true );
				}
			}
		});
	});

})( window.jQuery );
