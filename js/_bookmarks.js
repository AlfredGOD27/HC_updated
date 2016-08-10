(function($) {

	$('.view-all .btn').on( 'click', function() {
		$(this).closest('.button-nav').addClass('show-all');
	});

	$('body').on( 'click', '.add-to-folder', function(e) {
		e.preventDefault();

		var self = $(this);

		if( self.hasClass('added') )
			return;

		$.ajax({
			url: ajax_object.ajaxurl,
			type: 'POST',
			data: {
				action: 'hc_ajax_add_item_to_folder',
				folder_id: self.data('folder_id'),
				item_id: self.data('item_id')
			},
			success: function( json ) {

				var result = JSON.parse(json),
					count = self.closest('li').find('.count').text();

				if( 'success' === result.status ) {
					self.closest('li').addClass('added');

					count = parseInt(count);
					count += 1;

					self.closest('li').find('.count').text(count);

					self.closest('.button-nav').removeClass('open');
					$.magnificPopup.close();
				} else {
					alert(result.message);
				}
			}
		});
	});

})( window.jQuery );
