<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Videos {
	public function __construct() {

		add_filter( 'oembed_dataparse', array($this, 'embed_oembed_html'), 10, 3 );

	}

	public function get_youtube_iframe_html( $video_id, $atts = array() ) {

		$attr_str = array();
		foreach( $atts as $name => $value )
			$attr_str[] = $name . '="' . esc_attr($value) . '"';

		$attr_str = implode( ' ', $attr_str );

		$url = add_query_arg(
			array(
				'enablejsapi' => 1,
			),
			'https://www.youtube.com/embed/' . $video_id
		);

		return '<iframe src="' . $url . '" id="youtube-player-' . $video_id . '" class="youtube-tracked-embed" ' . $attr_str . ' allowfullscreen></iframe>';

	}

	public function embed_oembed_html( $html, $data, $url ) {

		// Extract YouTube ID
		preg_match( '/src="https:\/\/www.youtube.com\/embed\/([-\w]+)/', $html, $matches );
		if( isset($matches[1]) ) {
			$video_id = $matches[1];

			$atts = array();

			foreach( array('width', 'height') as $d ) {
				preg_match( '/' . $d . '="(\d+)/', $html, $matches );
				if( isset($matches[1]) )
					$atts[$d] = $matches[1];
			}

			return $this->get_youtube_iframe_html( $video_id, $atts );
		}

		return $html;

	}

}

return new HC_Videos();
