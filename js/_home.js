(function($) {

	if( !$('body').hasClass('page-template-page_home') )
		return;

	$('.home-section-featured-video-listings iframe').addClass('mute').addClass('autoplay');

	if( im.greaterThan('portrait') ) {
		$('.listing-slider-for').slick({
			adaptiveHeight: true,
			arrows: false,
			asNavFor: '.listing-slider-nav',
			fade: true,
			slidesToScroll: 1,
			slidesToShow: 1
		});

		$('.listing-slider-nav').slick({
			arrows: false,
			asNavFor: '.listing-slider-for',
			focusOnSelect: true,
			slidesToScroll: 1,
			slidesToShow: 4,
			speed: 0,
			vertical: true,
		});

		$('.listing-slider-nav .slick-slide').on(
			'mouseenter',
			function(e) {
				var idx = $(e.currentTarget).data('slick-index'),
					slick_obj = $('.listing-slider-for').slick('getSlick');

				slick_obj.slickGoTo(idx);
			}
		);
	} else {
		$('.listing-slider-for').slick({
			arrows: true,
			slidesToScroll: 1,
			slidesToShow: 1,
			prevArrow: hc_strings.prev_arrow,
			nextArrow: hc_strings.next_arrow,
		});
	}

	$('.trending-slider').slick({
		arrows: false,
		slidesToScroll: 3,
		slidesToShow: 5,
		speed: 150,
		prevArrow: hc_strings.prev_arrow,
		nextArrow: hc_strings.next_arrow,
		responsive: [
			{
				breakpoint: im.getValue('portrait', true),
				settings: {
					arrows: true,
					slidesToScroll: 1,
					slidesToShow: 2,
					variableWidth: true
				}
			}
		]
	});

	function load_next_page() {

		var self = $(this),
			container = self.closest('.block'),
			offset = container.data('offset');

		$('body').addClass('il-loading-content');

		container.find('.il-load-more').remove();
		container.append( hc_strings.loading );

		$.ajax({
			url: ajax_object.ajaxurl,
			type: 'POST',
			data: {
				action: 'hc_get_home_next_page_html',
				offset: offset
			},
			dataType: 'html',
			success: function( html ) {
				container.find('.il-loading').remove();
				container.append( html );

				container.data( 'offset', offset + 8 );
				maybe_add_load_more_button( container );

				$('body').removeClass('il-loading-content');
			}
		});

	}

	function maybe_add_load_more_button( container ) {

		if( container.data('offset') < container.data('total') )
			container.append( hc_strings.more_button );

	}

	maybe_add_load_more_button( $('.home-section-latest-posts .block') );

	$('.home-section-latest-posts').on( 'click', '.il-load-more', load_next_page );

})( window.jQuery );