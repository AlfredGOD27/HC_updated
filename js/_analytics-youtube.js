(function($) {

	var loaded = false,
		ready = [],
		played = [],
		finished = [];

	function load_script() {

		var firstScriptTag = document.getElementsByTagName('script')[0],
			tag = document.createElement('script');

		if( loaded )
			return false;

		tag.src = 'https://www.youtube.com/iframe_api';
		firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

		loaded = true;

	}

	function post_ga_event( player_id, event_label ) {

		if( 'undefined' === typeof ga )
			return;

		ga(
			'send',
			'event',
			'YouTube',
			event_label,
			'https://youtu.be/' + player_id,
			undefined,
			{
				'nonInteraction': 1
			}
		);

	}

	function on_ready( e ) {

		var player_id = $( e.target.getIframe() ).attr('id').toString().replace( 'youtube-player-', '' ),
			iframe,
			src;

		if( $.inArray( player_id, ready ) > -1 )
			return;

		ready.push( player_id );
		post_ga_event( player_id, 'Loaded video' );

		on_change( e );

		iframe = $('#youtube-player-' + player_id);

		// Maybe mute
		if( iframe.hasClass('mute') )
			e.target.mute();

		// Maybe autoplay
		src = iframe.attr('src');
		if( 'undefined' !== typeof src && (src.indexOf('autoplay') > -1 || iframe.hasClass('autoplay')) )
			e.target.playVideo();

	}

	function on_change( e ) {

		var player_id = $( e.target.getIframe() ).attr('id').toString().replace( 'youtube-player-', '' );

		if(
			e.data === YT.PlayerState.PLAYING ||
			e.data === YT.PlayerState.BUFFERING
		) {
			if( $.inArray( player_id, played ) > -1 )
				return;

			played.push( player_id );
			post_ga_event( player_id, 'Started video' );
		}

		if( e.data === YT.PlayerState.ENDED ) {
			if( $.inArray( player_id, finished ) > -1 )
				return;

			finished.push( player_id );
			post_ga_event( player_id, 'Completed video' );
		}

	}

	function attach_players() {

		if( 'object' !== typeof YT )
			return;

		$('.youtube-tracked-embed').each( function() {
			var self = $(this),
				player_id = self.attr('id').toString().replace( 'youtube-player-', '' );

			new YT.Player(
				self.attr('id'),
				{
					videoId: player_id,
					events: {
						'onReady': on_ready,
						'onStateChange': on_change
					}
				}
			);
		});

	}

	$('body').on( 'hc_yt_api_loaded', attach_players );

	if( $('.youtube-tracked-embed').length > 0 )
		load_script();

	$('body').on( 'hc_load_youtube_modal_video', function() {
		if( loaded ) {
			attach_players();
		} else {
			load_script();
		}
	});

})( window.jQuery );

function onYouTubeIframeAPIReady() {

	jQuery('body').trigger('hc_yt_api_loaded');

}
