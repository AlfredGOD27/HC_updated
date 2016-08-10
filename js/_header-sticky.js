(function($) {

	if( typeof document.addEventListener !== 'function' )
		return;

	var status;

	// Setup global vars
	var affix_on = false,
		sticky_el,
		lastScrollY = 0,
		ticking = false;

	function init() {
		// Assume affix is on
		affix_on = true;

		// Turn off is window isn't fullwidth
		if( !im.greaterThan('portrait') )
			affix_on = false;
	}

	function on_scroll() {
		lastScrollY = window.scrollY;

		if( affix_on )
			request_tick();
	}

	function request_tick() {
		if( ticking )
			return;

		requestAnimationFrame(update_affix);
		ticking = true;
	}

	function update_affix() {
		var header_height = $('#wpadminbar').height() + $('.site-top').height() + $('.site-header').height() + $('.nav-primary-wrapper').height();

		if( lastScrollY > header_height ) {
			sticky_el.addClass('show');
		} else {
			sticky_el.removeClass('show');
		}

		ticking = false;
	}

	$(window).on( 'load', function() {
		sticky_el = $('.sticky-header.use-sticky');

		if( 1 !== sticky_el.length )
			return;

		init();
		on_scroll();

		window.addEventListener('scroll', on_scroll, false);
		$(window).on( 'resize', init );
	});

	// Scroll
	$('.scroll-to-top .btn').on( 'click', function() {
		$('html, body').animate(
			{
				scrollTop: 0
			},
			250
		);
	});

})( window.jQuery );
