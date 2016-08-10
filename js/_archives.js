(function($) {

	if( !$('body').hasClass('archive-sub-sections') )
		return;

	function load_next_page() {

		var self = $(this),
			container = self.closest('.subcategory'),
			offset = container.data('offset');

		$('body').addClass('il-loading-content');

		container.find('.il-load-more').remove();
		container.append( hc_strings.loading );

		$.ajax({
			url: ajax_object.ajaxurl,
			type: 'POST',
			data: {
				action: 'hc_get_next_page_html',
				term_id: container.data('term_id'),
				taxonomy: container.data('taxonomy'),
				offset: offset
			},
			dataType: 'html',
			success: function( html ) {
				container.find('.il-loading').remove();
				container.append( html );

				container.data( 'offset', offset + 4 );
				maybe_add_load_more_button( container );

				$('body').removeClass('il-loading-content');
			}
		});

	}

	function maybe_add_load_more_button( container ) {

		if( container.data('offset') < container.data('total') )
			container.append( hc_strings.more_button );

	}

	$('.subcategory').each( function() {
		var self = $(this);
		maybe_add_load_more_button( self );
	});

	$('.subcategory').on( 'click', '.il-load-more', load_next_page );

})( window.jQuery );
