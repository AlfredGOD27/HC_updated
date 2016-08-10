(function($) {

	var container = $( '#rating' ),
		current_value;

	if( 0 === container.length )
		return;

	container.hide().before( '<div class="stars"><span><a class="star-1" href="#">1</a><a class="star-2" href="#">2</a><a class="star-3" href="#">3</a><a class="star-4" href="#">4</a><a class="star-5" href="#">5</a></span></div>' );

	current_value = container.val();
	if( current_value ) {
		$('.stars').addClass('selected');
		$('.stars .star-' + current_value ).addClass('active');
	}

	$( '.stars a' ).on( 'click', function(e) {
		e.preventDefault();

		var self = $( this ),
			rating = self.closest( '.sub' ).find( '#rating' ),
			container = self.closest( '.stars' );

		rating.val( self.text() );
		self.siblings( 'a' ).removeClass( 'active' );
		self.addClass( 'active' );
		container.addClass( 'selected' );

		$.ajax({
			url: ajax_object.ajaxurl,
			type: 'POST',
			data: {
				action: 'hc_ajax_set_rating',
				item_id: rating.data('item_id'),
				rating: rating.val()
			},
			success: function( json ) {
				var result = JSON.parse(json);

				if( 'success' === result.status ) {
					self.closest('.button-nav').removeClass('open');
				} else {
					alert(result.message);
				}
			}
		});
	});

})( window.jQuery );
