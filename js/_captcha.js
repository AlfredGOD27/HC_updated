var captcha_script_inserted = false,
	captchas = [];

window.hc_activate_captcha = function() {

	if( 'undefined' === typeof $.magnificPopup.instance.contentContainer )
		return;

	if( 'undefined' === typeof grecaptcha )
		return;

	$('.captcha:not(.loaded)').each( function() {
		var self = $(this),
			captcha;

		captcha = grecaptcha.render(
			self[0],
			{
				'sitekey': hc_settings.recaptcha_key,
				'callback': function(response) {
					self.data( 'captcha-response', response );
					self.closest('form').find('[type="submit"]').prop( 'disabled', false );
				},
				'expired-callback': function() {
					self.data( 'captcha-response', '' );
					self.closest('form').find('[type="submit"]').prop( 'disabled', true );
				}
			}
		);

		self.addClass('loaded');

		captchas.push(captcha);
	});

};

function hc_init_captcha() {

	if( captcha_script_inserted ) {
		hc_activate_captcha();
		return;
	}

	captcha_script_inserted = true;

	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = 'https://www.google.com/recaptcha/api.js?onload=hc_activate_captcha&render=explicit';

	document.getElementsByTagName('head')[0].appendChild(script);

}
