(function($) {

	$('.toggle-nav').on( 'click', function(e) {
		$('body').toggleClass('nav-open');
	});

	$('.main-menu .inactive-link').on( 'click', function() {
		$(this).closest('li').toggleClass('active');
	});

})( window.jQuery );
