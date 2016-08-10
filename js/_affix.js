(function($) {

	if( typeof document.addEventListener !== 'function' )
		return;

	// Setup global vars
	var window_height,

		sidebar,
		sidebar_width,
		sidebar_height,

		container,
		container_height,
		container_offset,

		affix_on = false,
		lastScrollY = 0,
		ticking = false,
		timer;

	function init() {

		// Update measurements
		container_height = container.height();
		container_offset = sidebar.offset().top;
		sidebar_width = sidebar.width();
		sidebar_height = sidebar.height();
		window_height = $(window).height();

		// Assume affix is on
		affix_on = true;

		if( container_offset + (container_height * 1.25 ) > sidebar_height ) {
			// Turn off if sidebar isn't tall enough
			affix_on = false;
		} else {
			// Turn off is window isn't fullwidth
			if( !im.greaterThan('portrait') )
				affix_on = false;
		}

		if( affix_on ) {
			// If so, lock container width
			container.css( 'width', sidebar_width );
		} else {
			// If off, undo any modifications
			container.css( 'width', 'auto' );
			container.css( 'bottom', 'auto' );
			container.removeClass('affix');
			container.removeClass('affix-bottom');
		}

	}

	function on_scroll() {

		lastScrollY = window.scrollY;

		if( affix_on )
			request_tick();

	}

	function request_tick() {

		if( ticking )
			return;

		ticking = true;

		clearTimeout(timer);
		timer = setTimeout(
			function() {
				requestAnimationFrame(update_affix);
			},
			20
		);

	}

	function update_affix() {

		var header_height = $('.site-top').outerHeight() + $('.header-navigation-container').outerHeight(),
			viewport_offset_height = $('.sticky-header').outerHeight() + $('#wpadminbar').outerHeight(),
			footer_offset = $('.site-footer').offset().top,
			container_distance = lastScrollY - container_offset + window_height - container_height - 16;

		container_offset = sidebar.offset().top;

		if( container_offset + container_height < lastScrollY + viewport_offset_height + window_height && container_distance > 0 ) {
			container.css( 'transform', 'translateY(' + container_distance + 'px)' );
			container.addClass('affix');

			if( lastScrollY + window_height > footer_offset - 16 ) {
				container.removeClass('affix');
				container.addClass('affix-bottom');
				container.css( 'transform', 'translateY(0)' );
			} else {
				container.removeClass('affix-bottom');
			}
		} else {
			container.removeClass('affix');
			container.css( 'transform', 'translateY(0)' );
		}

		ticking = false;

	}

	$(window).on( 'load', function() {

		container = $('.sidebar .affix-on-scroll');
		if( 0 === container.length )
			return;

		sidebar = container.closest('.sidebar');
		$('body').addClass('has-affixed-sidebar');

		init();
		on_scroll();

		window.addEventListener('scroll', on_scroll, false);
		$(window).on( 'resize', init );

	});

})( window.jQuery );