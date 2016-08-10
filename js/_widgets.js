(function($) {

	$('.featured-widget-slider').slick({
		arrows: true,
		slidesToScroll: 1,
		slidesToShow: 1,
		adaptiveHeight: true,
		speed: 0,
		fade: true,
		prevArrow: '<button type="button" class="slick-prev" title="Previous"><i class="ico-arrow-up"></i></button>',
		nextArrow: '<button type="button" class="slick-next" title="Next"><i class="ico-arrow-down"></i></button>'
	});

})( window.jQuery );
