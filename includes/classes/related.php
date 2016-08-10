<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Related {
	public function display_related_content( $post, $context, $count = 4 ) {

		switch( $post->post_type ) {
			case 'post':
				$style = 'small';

				$args = array();

				$terms = wp_get_post_terms( $post->ID, 'post_tag' );
				if( !empty($terms) ) {
					$taxonomy = 'post_tag';
				} else {
					$taxonomy = 'category';
					$terms    = wp_get_post_terms( $post->ID, 'category' );
				}
				break;
			case 'event':
				$style = 'tiny';

				$args = HC()->events->get_date_query_args();

				$taxonomy = 'event-category';
				$terms    = wp_get_post_terms( $post->ID, $taxonomy );
				break;
			case 'listing':
				$style = 'tiny';

				$args            = array();
				$args['orderby'] = 'rand';

				$terms = wp_get_post_terms( $post->ID, 'listing_type' );
				if( !empty($terms) ) {
					$taxonomy = 'listing_type';
				} else {
					$taxonomy = 'locations';
					$terms    = wp_get_post_terms( $post->ID, 'locations' );
				}
				break;
		}

		if( empty($terms) )
			return;

		$term_ids = array();

		// Try to only get child terms
		foreach( $terms as $term ) {
			if( 0 !== $term->parent )
				$term_ids[] = $term->term_id;
		}

		// If not possible, use all terms
		if( empty($term_ids) ) {
			foreach( $terms as $term )
				$term_ids[] = $term->term_id;
		}

		$args['tax_query'] = array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_ids,
				'operator' => 'IN',
			),
		);

		$args['posts_per_page']         = $count;
		$args['post_type']              = $post->post_type;
		$args['post__not_in']           = array($post->ID);
		$args['update_post_term_cache'] = false;

		$posts = get_posts( $args );
		if( empty($posts) )
			return;

		switch( $context ) {
			case 'section':
				?>
				<section class="related">
					<div class="wrap">
				<?php
				break;
			case 'aside':
				?>
				<aside class="related">
				<?php
				break;
		}

		?>
		<div class="block clearfix">
			<h2>You May Also Like</h2>
			<?php

			$i = 1;
			foreach( $posts as $post ) {
				echo 1 === $i % 4 ? '<div class="one-fourth first">' : '<div class="one-fourth">';
					HC()->archives->display_entry( $post, $style, false );
				echo '</div>';
				++$i;
			}
			?>
		</div>
		<?php

		switch( $context ) {
			case 'section':
				?>
					</div>
				</section>
				<?php
				break;
			case 'aside':
				?>
				</aside>
				<?php
				break;
		}

	}
}

return new HC_Related();
