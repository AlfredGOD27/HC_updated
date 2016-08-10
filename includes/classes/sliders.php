<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Sliders {
	private function display_post_content( $post_id, $max_chars = false ) {

		$term = false;
		switch( get_post_type($post_id) ) {
			case 'post':
				$term = HC()->utilities->get_primary_term( $post_id, 'category' );
				break;
			case 'event':
				$term = HC()->utilities->get_primary_term( $post_id, 'event-category' );
				break;
			case 'listing':
				$term = HC()->utilities->get_primary_term( $post_id, 'listing_type' );
				break;
		}

		if( !empty($term) ) {
			echo '<div class="white">';
				echo HC()->utilities->get_category_icon_html( $term, 'small', 'white' );
			echo '</div>';

			echo '<div class="orange">';
				echo HC()->utilities->get_category_icon_html( $term, 'small', 'orange' );
			echo '</div>';
		}

		if( false === $max_chars ) {
			echo '<span>' . HC()->entry->get_headline_title( $post_id ) . '</span>';
		} else {
			echo '<span>' . HC()->formatting->maybe_truncate( HC()->entry->get_headline_title( $post_id ), $max_chars ) . '</span>';
		}

	}

	public function display( $args ) {

		$posts = get_posts( $args );
		if( empty($posts) )
			return;

		?>
		<section class="archive-slider-container hide-no-js">
			<div class="wrap">
				<div class="slider-for">
					<?php
					foreach( $posts as $post_id ) {
						?>
						<div>
							<?php
							$header_type = get_post_meta( $post_id, '_hc_post_header_type', true );
							switch( $header_type ) {
								case 'video':
									$video_url = get_post_meta( $post_id, '_hc_post_video_url', true );
									if( !empty($video_url) )
										echo wp_oembed_get($video_url);
									break;
								default:
									echo '<a href="' . get_permalink($post_id) . '">';
										echo get_the_post_thumbnail( $post_id, 'slide' );
									echo '</a>';
									break;
							}

							?>
							<div class="slide-content">
								<?php
								$this->display_post_content( $post_id );
								?>
							</div>
						</div>
						<?php
					}
					?>
				</div>

				<div class="slider-nav">
					<?php
					foreach( $posts as $post_id ) {
						?>
						<div>
							<div class="outer">
								<?php
								echo get_the_post_thumbnail( $post_id, 'slide-thumbnail' );
								?>

								<a class="inner" href="<?php echo get_permalink($post_id); ?>">
									<?php
									$this->display_post_content( $post_id, 50 );
									?>
								</a>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
		</section>
		<?php

	}
}

return new HC_Sliders();
