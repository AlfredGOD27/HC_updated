var fb_init = false;

window.fbAsyncInit = function() {
	jQuery('html').removeClass('no-fb');

	FB.init({
		appId : hc_settings.facebook_app_id,
		xfbml : true,
		version : 'v2.6'
	});
};

function hc_maybe_load_facebook() {

	if( fb_init )
		return;

	fb_init = true;

	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = 'https://connect.facebook.net/en_US/sdk.js';

	document.getElementsByTagName('head')[0].appendChild(script);

}
