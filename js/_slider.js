(function($) {

	$('.slider-for').on( 'init', function() {
		$(this).fitVids();
	});

	if( im.greaterThan('portrait') ) {
		$('.slider-for').slick({
			adaptiveHeight: true,
			arrows: true,
			asNavFor: '.slider-nav',
			fade: true,
			autoplay: true,
			autoplaySpeed: 4000,
			slidesToScroll: 1,
			slidesToShow: 1,
			prevArrow: hc_strings.prev_arrow,
			nextArrow: hc_strings.next_arrow,
		});

		$('.slider-nav').slick({
			arrows: false,
			asNavFor: '.slider-for',
			focusOnSelect: true,
			slidesToScroll: 1,
			slidesToShow: 4,
			speed: 0,
			useCSS: false,
			vertical: true,
		});
	} else {
		$('.slider-for').slick({
			arrows: true,
			slidesToScroll: 1,
			slidesToShow: 1,
			prevArrow: hc_strings.prev_arrow,
			nextArrow: hc_strings.next_arrow,
		});
	}

	$('.slider-for').on( 'afterChange', function() {
		$(this).find('.youtube-tracked-embed').each( function() {
			if( 'object' !== typeof YT )
				return;

			var player = YT.get( $(this).attr('id') );

			if( 'undefined' !== typeof player && 'function' === typeof player.stopVideo )
				player.stopVideo();
		});
	});

})( window.jQuery );