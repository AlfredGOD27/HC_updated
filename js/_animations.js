(function($) {

	$('body').on(
		'webkitAnimationEnd mozAnimationEnd animationend',
		'.animation',
		function() {
			$(this).removeClass('run');
		}
	);

	function reset_animation( el ) {

		el.removeClass('run');
		el.addClass('run');

	}

	$(window).on(
		'load',
		function() {
			$('body').on(
				'mouseenter',
				'.event-slider-nav .slick-slide, .event-slider-for .slide-content, .slider-nav .slick-slide, .subcategory-description, .archive-entry-small, .home-section-trending a, .main-menu .menu-item.has-children',
				function() {
					reset_animation( $(this).find('.category-icon') );
				}
			);
		}
	);

})( window.jQuery );
