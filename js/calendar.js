(function($) {

	var timer;

	if( !$('body').hasClass('page-template-page_calendar') )
		return;

	$('.datepicker').pikaday({
		firstDay: 1,
		minDate: new Date(),
		format: 'DD-MM-YYYY',
		formatStrict: true,
		i18n: {
			previousMonth: '',
			nextMonth: '',
			months: ['January','February','March','April','May','June','July','August','September','October','November','December'],
			weekdays: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
			weekdaysShort: ['S','M','T','W','T','F','S']
		}
	});

	$('.events-slider').slick({
		arrows: true,
		slidesToScroll: 3,
		speed: 150,
		slidesToShow: Math.floor($('.events-slider').width() / 225),
		centerPadding: '8px',
		prevArrow: hc_strings.prev_arrow,
		nextArrow: hc_strings.next_arrow,
		variableWidth: true,
		responsive: [
			{
				breakpoint: im.getValue('portrait', true),
				settings: {
					slidesToScroll: 1,
					slidesToShow: 2,
				}
			}
		]
	});

	function set_filter() {

		var current_text = $('.calendar-search-bar input[type="search"]' ).val(),
			current_category = $('.calendar-search-bar select option:selected').val(),
			current_date = $('.calendar-search-bar').find( '.datepicker' ).val();

		if( current_text && current_text.length > 0 )
			current_text = current_text.toLowerCase();

		if( current_category )
			current_category = parseInt(current_category);

		if( current_date )
			current_date = moment( current_date, 'DD-MM-YYYY' ).unix();

		$('.events-slider').slick( 'slickUnfilter' );

		$('.events-slider').slick( 'slickFilter', function() {

			var self = $(this),
				item_categories;

			if( current_text && current_text.length > 0 ) {
				if( -1 === self.data('text').indexOf(current_text) )
					return false;
			}

			if( current_category ) {
				item_categories = self.data('category_ids');
				if( 'undefined' === typeof item_categories )
					return false;

				item_categories = item_categories.toString().split(',');
				item_categories = $.map( item_categories, parseInt );

				if( -1 === $.inArray(current_category, item_categories) )
					return false;
			}

			if( current_date ) {
				if( current_date < self.data('start_date') || current_date > self.data('end_date') )
					return false;
			}

			return true;
		});

		$('.events-slider').each( function() {
			var args = $(this).slick( 'getSlick' );

			if( 0 === args.$slides.length ) {
				$(this).closest('.subcategory').addClass('hide');
			} else {
				$(this).closest('.subcategory').removeClass('hide');
			}
		});

	}

	set_filter();
	$('.calendar-search-bar').find( 'select, .datepicker' ).on( 'change', set_filter );
	$('.calendar-search-bar input[type="search"]' ).on( 'keyup keydown', function() {
		clearTimeout(timer);
		timer = setTimeout(set_filter, 200);
	});

})( window.jQuery );
