<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Formatting {
	public function build_comma_separated_list( $items ) {

		$count = count($items);

		if( $count === 0 )
			return;

		if( $count === 1 )
			return $items[0];

		return implode(', ', array_slice($items, 0, -1)) . ' and ' . end($items);

	}

	public function maybe_truncate( $content, $max_length = 250 ) {

		$content = strip_shortcodes( $content );
		$content = sanitize_text_field( $content );

		if( $max_length > 0 && strlen($content) > $max_length ) {
			$content = genesis_truncate_phrase( $content, $max_length - 3 );
			$content = trim($content);

			return $content . '...';
		}

		return $content;

	}

	public function get_excerpt( $post, $max_length = 250 ) {

		$subtitle = get_post_meta( $post->ID, '_hc_subtitle', true );
		if( !empty($subtitle) ) {
			$content = $subtitle;
		} elseif( !empty($post->post_excerpt) ) {
			$content = $post->post_excerpt;
		} else {
			$seo_description = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
			if( !empty($seo_description) ) {
				$content = $seo_description;
			} else {
				$content = $post->post_content;
			}
		}

		return $this->maybe_truncate( $content, $max_length );

	}

	public function get_linked_url( $website ) {

		$website_label = str_replace(
			array(
				'http://',
				'https://',
			),
			array(
				'',
				'',
			),
			$website
		);

		$website_label = trim($website_label, '/');

		return '<a href="' . esc_url($website) . '">' . sanitize_text_field($website_label) . '</a>';

	}

	public function display_data_list( $lines ) {

		echo '<dl class="item-data clearfix">';
			foreach( $lines as $label => $text ) {
				echo '<dt>' . $label . '</dt>';
				echo '<dd>' . $text . '</dd>';
			}
		echo '</dl>';

	}

	public function display_map( $map_address, $width, $height ) {

		$url = add_query_arg(
			array(
				'q' => urlencode($map_address),
			),
			'http://maps.google.com/'
		);

		$src = add_query_arg(
			array(
				'size'    => $width . 'x' . $height,
				'scale'   => 2,
				'zoom'    => 12,
				'maptype' => 'roadmap',
				'markers' => 'color:0xfe862c%7C' . urlencode($map_address),
			),
			'https://maps.googleapis.com/maps/api/staticmap'
		);

		?>
		<a href="<?php echo esc_url($url); ?>" target="_blank">
			<img src="<?php echo esc_url($src); ?>" alt="<?php echo esc_attr($map_address); ?>" class="aligncenter">
		</a>
		<?php

	}

}

return new HC_Formatting();
