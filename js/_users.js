(function($) {

	function add_message( container, status, message ) {

		if( 'error' === status ) {
			container.html( '<div class="alert alert-' + status + ' animated shake">' + message + '</div>' );
		} else {
			container.html( '<div class="alert alert-' + status + '">' + message + '</div>' );
		}

	}

	function reset_captcha() {

		$.each( captchas, function( _, captcha ) {
			grecaptcha.reset(
				captcha
			);
		});

		$('.captcha.loaded').each( function() {
			var self = $(this);
			self.data( 'captcha-response', '' );
			self.closest('form').find('[type="submit"]').prop( 'disabled', true );
		});

	}

	$('#register-popup form').on( 'submit', function(e) {
		e.preventDefault();

		var self = $(this);

		$.ajax({
			url: ajax_object.ajaxurl,
			type: 'POST',
			data: {
				action: 'hc_ajax_register',
				first_name: self.find('[name="first_name"]').val(),
				last_name: self.find('[name="last_name"]').val(),
				user_email: self.find('[name="user_email"]').val(),
				_hc_user_city: self.find('[name="_hc_user_city"] option:selected').val(),
				user_pass: self.find('[name="user_pass"]').val(),
				user_pass_2: self.find('[name="user_pass_2"]').val(),
				captcha: self.find('.captcha').data('captcha-response')
			},
			success: function( json ) {
				var data = JSON.parse( json );

				add_message( self.closest('.white-popup').find('.messages'), data.status, data.message );

				if( 'success' === data.status ) {
					self.find('input, button').prop('disabled', true);

					setTimeout(
						function() {
							window.location.href = data.redirect_to;
						},
						1500
					);
				} else {
					reset_captcha();
				}
			}
		});

		return false;
	});


	$('#login-popup form').on( 'submit', function(e) {
		e.preventDefault();

		var self = $(this);

		$.ajax({
			url: ajax_object.ajaxurl,
			type: 'POST',
			data: {
				action: 'hc_ajax_login',
				log: self.find('[name="log"]').val(),
				pwd: self.find('[name="pwd"]').val(),
				rememberme: self.find('[name="rememberme"]').prop('checked'),
				captcha: self.find('.captcha').data('captcha-response')
			},
			success: function( json ) {
				var data = JSON.parse( json ),
					redirect = self.data('redirect');

				add_message( self.closest('.white-popup').find('.messages'), data.status, data.message );

				if( 'success' === data.status ) {
					self.find('input, button').prop('disabled', true);

					setTimeout(
						function() {
							window.location.href = 'undefined' !== typeof redirect ? redirect : data.redirect_to;
						},
						1500
					);
				} else {
					reset_captcha();
				}
			}
		});

		return false;
	});

	$('#password-popup form').on( 'submit', function(e) {
		e.preventDefault();

		var self = $(this);

		$.ajax({
			url: ajax_object.ajaxurl,
			type: 'POST',
			data: {
				action: 'hc_ajax_reset_password',
				email: self.find('[name="user_login"]').val(),
				captcha: self.find('.captcha').data('captcha-response')
			},
			success: function( json ) {
				var data = JSON.parse( json );

				add_message( self.closest('.white-popup').find('.messages'), data.status, data.message );

				if( 'success' === data.status ) {
					self.find('input, button').prop('disabled', true);

					setTimeout(
						function() {
							$.magnificPopup.close();
						},
						1500
					);
				} else {
					reset_captcha();
				}
			}
		});

		return false;
	});

	$('.btn-facebook').on( 'click', function(e) {
		e.preventDefault();

		var self = $(this);

		FB.login(
			function(response) {

				if( response.status === 'connected' ) {
					$.ajax({
						url: ajax_object.ajaxurl,
						type: 'POST',
						data: {
							action: 'hc_ajax_facebook_register_or_login',
							token: response.authResponse.accessToken
						},
						success: function( json ) {
							var data = JSON.parse( json );

							add_message( self.closest('.white-popup').find('.messages'), data.status, data.message );

							if( 'success' === data.status ) {
								self.find('input, button').prop('disabled', true);

								setTimeout(
									function() {
										window.location.href = data.redirect_to;
									},
									1500
								);
							}
						}
					});
				} else if( response.status === 'not_authorized' ) {
					add_message( self.closest('.white-popup').find('.messages'), 'error', 'You must authorize the app to login via Facebook.' );
				} else {
					add_message( self.closest('.white-popup').find('.messages'), 'error', 'You must login to Facebook.' );
				}
			},
			{
				scope: 'public_profile,email'
			}
		);
	});

})( window.jQuery );
