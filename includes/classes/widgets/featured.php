<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Featured_Widget extends WP_Widget {
	public function __construct() {

		parent::__construct(false, $name = 'HC: Featured');

	}

	public function widget( $args, $instance ) {

		global $post;

		extract($args);

		$post_type = get_field( '_hc_post_type', 'widget_' . $widget_id );

		$query_args                           = array();
		$query_args['post_type']              = $post_type;
		$query_args['fields']                 = 'ids';
		$query_args['update_post_meta_cache'] = false;
		$query_args['orderby']                = 'post__in';

		switch( $post_type ) {
			case 'event':
				$home_page_id = get_option( 'page_on_front' );
				if( empty($home_page_id) )
					return;

				$post_ids = get_post_meta( $home_page_id, '_hc_home_featured_event_ids', true );
				if( empty($post_ids) )
					return;

				$query_args['post__in'] = array_map( 'absint', $post_ids );
				break;
			case 'listing':
				if( is_archive() ) {
					$term = get_queried_object();
				} elseif( is_singular('post') ) {
					$term = HC()->utilities->get_primary_term( $post->ID, 'category' );
				}

				if( empty($term) )
					return;

				$post_id = get_field( '_hc_category_sidebar_featured_venue_id', $term );
				if( empty($post_id) )
					return;

				$post_ids               = (array) $post_id;
				$query_args['post__in'] = array_map( 'absint', $post_ids );
				break;
			case 'post':
				$home_page_id = get_option( 'page_on_front' );
				if( empty($home_page_id) )
					return;

				$main_post_id = get_post_meta( $home_page_id, '_hc_home_picks_main_post_id', true );
				$post_ids     = get_post_meta( $home_page_id, '_hc_home_picks_post_ids', true );

				$post_ids = array_merge( (array) $main_post_id, $post_ids );
				if( empty($post_ids) )
					return;

				$query_args['post__in'] = array_map( 'absint', $post_ids );
				break;
			default:
				return;
		}

		$posts = get_posts( $query_args );
		if( empty($posts) )
			return;

		echo $before_widget;
			$title = get_field( '_hc_title', 'widget_' . $widget_id );
			if( !empty($title) )
				echo $before_title . sanitize_text_field($title) . $after_title;

			?>
			<div class="featured-widget-slider hide-no-js">
				<?php
				foreach( $post_ids as $post_id ) {
					$date = HC()->events->get_event_date_info( $post_id );

					?>
					<div>
						<?php
						if( has_post_thumbnail($post_id) ) {
							echo '<a href="' . get_permalink($post_id) . '">';
								echo get_the_post_thumbnail( $post_id, 'archive-small' );
							echo '</a>';
						}
						?>

						<div class="bottom">
							<?php
							switch( $post_type ) {
								case 'event':
									?>
									<div class="left">
										<span class="m"><?php echo date('M', $date['start_date']); ?></span>
										<span class="d"><?php echo date('j', $date['start_date']); ?></span>
									</div>

									<div class="right clearfix">
										<?php
										$term = HC()->utilities->get_primary_term( $post_id, 'event-category' );
										if( !empty($term) ) {
											?>
											<p><?php echo $term->name; ?></p>
											<?php
										}
										?>

										<h5><a href="<?php echo get_permalink($post_id); ?>"><?php echo HC()->entry->get_headline_title($post_id); ?></a></h5>
									</div>
									<?php
									break;
								case 'listing':
								case 'post':
									$taxonomy = 'listing' === $post_type ? 'listing_type' : 'category';

									?>
									<div class="full">
										<?php
										$term = HC()->utilities->get_primary_term( $post_id, $taxonomy );
										if( !empty($term) ) {
											?>
											<p><?php echo $term->name; ?></p>
											<?php
										}
										?>

										<h5><a href="<?php echo get_permalink($post_id); ?>"><?php echo HC()->entry->get_headline_title($post_id); ?></a></h5>
									</div>
									<?php
									break;
							}
							?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		echo $after_widget;

	}

	public function form( $instance ) {

	}

}
