<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Utilities {
	public function get_page_link( $key ) {

		$page_id = get_option( 'options_', $key );
		if( empty($page_id) )
			return;

		return get_permalink($page_id);

	}

	public function get_category_icon_html( $category, $size = 'small', $color = 'orange' ) {

		$icon = get_field( '_hc_category_icon', $category );
		if( empty($icon) && !empty($category->parent) ) {
			$parent = get_term_by( 'id', $category->term_id, $category->taxonomy );
			$icon   = get_field( '_hc_category_icon', $parent );
		}

		if( !empty($icon) )
			return '<i class="animation animation-' . $size . ' category-icon animation-' . $icon . '-' . $color . '"></i>';

	}

	public function atts_to_html( $atts ) {

		$html = array();
		foreach( $atts as $att => $value )
			$html[] = $att . '="' . esc_attr($value) . '"';

		return implode( ' ', $html );

	}

	public function get_async_image_placeholder( $atts, $placeholder_class = '' ) {

		$data_atts = array();
		foreach( $atts as $att => $value )
			$data_atts[ 'data-' . $att ] = $value;

		$data_atts['class'] = 'async-load-image ' . $placeholder_class;

		$atts_html = $this->atts_to_html( $data_atts );

		return '<span ' . $atts_html . '></span>';

	}

	public function get_primary_term( $post_id, $taxonomy ) {

		// If a primary term if set, try getting it directly...
		$primary_term_id = get_post_meta( $post_id, '_yoast_wpseo_primary_category', true );
		if( !empty($primary_term_id) ) {
			$term = get_term_by( 'id', absint($primary_term_id), $taxonomy );
			if( !empty($term) && !is_wp_error($term) )
				return $term;
		}

		// ...otherwise, take the first term in the taxonomy
		$terms = wp_get_object_terms( $post_id, $taxonomy );
		if( empty($terms) )
			return;

		return $terms[0];

	}

	public function display_basic_slider( $post_id ) {

		$images = array();

		if( has_post_thumbnail( $post_id ) )
			$images[] = get_post_thumbnail_id( $post_id );

		$additional_images = get_post_meta( $post_id, '_hc_gallery_image_ids', true );
		if( !empty($additional_images) )
			$images = array_merge($images, $additional_images);

		if( 1 === count($images) ) {
			echo wp_get_attachment_image( $images[0], 'featured', '', array('class' => 'aligncenter') );
		} elseif( count($images) > 1 ) {
			?>
			<div class="basic-slider">
				<?php
				foreach( $images as $image_id ) {
					?>
					<div>
						<?php
						echo wp_get_attachment_image( $image_id, 'featured' );
						?>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}

	}

}

return new HC_Utilities();
