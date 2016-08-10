<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Home {
	public function __construct() {

		add_action( 'wp', array($this, 'init') );

		add_action( 'wp_ajax_hc_get_home_next_page_html', array($this, 'get_next_page_html') );
		add_action( 'wp_ajax_nopriv_hc_get_home_next_page_html', array($this, 'get_next_page_html') );

	}

	public function init() {

		if( 'page_templates/page_home.php' !== get_page_template_slug() )
			return;

		add_action( 'genesis_before', array($this, 'takeover') );
		add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
		remove_action( 'genesis_before_loop', 'hc_do_breadcrumbs' );

		remove_action( 'genesis_loop', 'genesis_do_loop' );
		add_action( 'genesis_loop', array($this, 'do_slider') );
		add_action( 'genesis_loop', array($this, 'do_mobile_buttons') );
		add_action( 'genesis_loop', array($this, 'do_featured_posts') );
		add_action( 'genesis_loop', array($this, 'do_featured_video_and_listings') );
		add_action( 'genesis_loop', array($this, 'takeover') );
		add_action( 'genesis_loop', array($this, 'do_featured_events_and_join') );
		add_action( 'genesis_loop', array($this, 'do_trending') );
		add_action( 'genesis_loop', array($this, 'do_latest_posts') );

	}

	public function do_slider() {

		global $post;

		$enable = get_post_meta( $post->ID, '_hc_home_enable_slider', true );
		if( empty($enable) )
			return;

		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => 8,
			'meta_query'     => array(
				array(
					'key'   => '_hc_sticky_on_home',
					'value' => 1,
				),
			),
			'fields' => 'ids',
		);
		$stickied_post_ids = get_posts( $args );

		$count = get_post_meta( $post->ID, '_hc_home_slider_count', true );
		$count = !empty($count) ? absint($count) : 8;

		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => $count,
			'post__not_in'   => $stickied_post_ids,
			'fields'         => 'ids',
		);
		$post_ids = get_posts( $args );

		$all_post_ids = array_merge($stickied_post_ids, $post_ids);

		$total = count( $all_post_ids );

		$args = array(
			'post_type'      => 'post',
			'post__in'       => $all_post_ids,
			'orderby'        => 'post__in',
			'fields'         => 'ids',
			'posts_per_page' => -1,
		);

		HC()->sliders->display( $args );

	}

	public function do_mobile_buttons() {

		$buttons = get_field( '_hc_home_mobile_links' );
		if( empty($buttons) )
			return;

		?>
		<section class="home-section home-section-mobile-buttons show-phone">
			<div class="wrap">
				<?php
				foreach( $buttons as $button ) {
					echo '<a href="' . esc_url($button['link']) . '" class="btn">' . sanitize_text_field($button['label']) . '</a>';
				}
				?>
			</div>
		</section>
		<?php

	}

	private function display_editors_pick_other_posts( $post_ids ) {

		global $post;

		$mpu_1 = HC()->ads->get_ad_container( 'mpu-1' );
		$mpu_2 = HC()->ads->get_ad_container( 'mpu-2-desktop' );

		$i = 1;
		foreach( $post_ids as $post_id ) {
			echo 1 === $i % 2 ? '<div class="pull-left item item-' . $i . '">' : '<div class="pull-right item item-' . $i . '">';
				if( 4 === $i && !empty($mpu_2) ) {
					echo $mpu_2;
				} else {
					if( 2 === $i && !empty($mpu_1) ) {
							echo $mpu_1;
						echo '</div>';
						++$i;
						echo 1 === $i % 2 ? '<div class="pull-left item item-' . $i . '">' : '<div class="pull-right item item-' . $i . '">';
					}

					HC()->archives->display_entry( $post_id, 'tiny' );
				}
			echo '</div>';
			++$i;
		}

	}

	public function do_featured_posts() {

		global $post;

		$enable = get_post_meta( $post->ID, '_hc_home_enable_featured_posts', true );
		if( empty($enable) )
			return;

		$main_post_ids = get_post_meta( $post->ID, '_hc_home_picks_main_post_id', true );
		$main_post_id  = $main_post_ids[0];
		$post_ids      = get_post_meta( $post->ID, '_hc_home_picks_post_ids', true );

		?>
		<section class="home-section home-section-featured-posts">
			<div class="wrap">
				<?php
				$heading = get_post_meta( $post->ID, '_hc_home_picks_heading', true );
				echo '<h2>' . sanitize_text_field($heading) . '</h2>';

				$main_post = get_post( $main_post_id );
				?>
				<div class="pull-left first">
					<div class="main-pick">
						<?php HC()->folders->display_add_button( $main_post_id, true, true ); ?>

						<?php
						if( has_post_thumbnail($main_post_id) ) {
							?>
							<div class="top">
								<a href="<?php echo get_permalink($main_post_id); ?>">
									<?php echo get_the_post_thumbnail($main_post_id, 'archive-large' ); ?>
								</a>
							</div>
							<?php
						}
						?>

						<a href="<?php echo get_permalink($main_post_id); ?>" class="bottom clearfix">
							<div class="left">
								<h3><?php echo HC()->entry->get_headline_title( $main_post_id ); ?></h3>

								<?php
								echo '<p>' . HC()->formatting->get_excerpt($main_post) . '</p>';
								?>
							</div>

							<div class="right">
								<?php echo get_avatar( $main_post->post_author, 90 ); ?>

								<?php
								$user = get_user_by( 'id', $main_post->post_author );
								echo '<p>' . $user->display_name . '</p>';
								?>
							</div>
						</a>
					</div>
				</div>
				<div class="pull-right other-picks">
					<?php
					$this->display_editors_pick_other_posts( $post_ids );
					?>
				</div>
			</div>
		</section>
		<?php

	}

	public function do_featured_video_and_listings() {

		global $post;

		$enable_video    = get_post_meta( $post->ID, '_hc_home_enable_featured_video', true );
		$enable_listings = get_post_meta( $post->ID, '_hc_home_enable_featured_listings', true );
		if( empty($enable_video) && empty($enable_listings) )
			return;

		?>
		<section class="home-section home-section-featured-video-listings">
			<div class="wrap">
				<?php
				if( !empty($enable_video) ) {
					$video_page_id   = get_field( '_hc_video_category_id', 'option' );
					$video_page_link = get_term_link($video_page_id, 'category');
					?>
					<div class="left">
						<?php
						$heading = get_post_meta( $post->ID, '_hc_home_watch_this_heading', true );
						echo '<h2><a href="' . $video_page_link . '">' . sanitize_text_field($heading) . '</a></h2>';

						$src = get_post_meta( $post->ID, '_hc_home_watch_this_video_url', true );
						$src = esc_url($src);
						echo wp_oembed_get($src);
						?>

						<div class="mobile-bar show-phone">
							<a href="<?php echo $video_page_link; ?>" class="btn btn-icon"><span>More Videos</span> <i class="ico-arrow-right"></i></a>
						</div>
					</div>
					<?php
				}
				?>

				<?php
				if( !empty($enable_listings) ) {
					$directory_page_id   = get_field( '_hc_directory_page_id', 'option' );
					$directory_page_link = get_permalink($directory_page_id);

					?>
					<div class="right hide-no-js">
						<?php
						$heading = get_post_meta( $post->ID, '_hc_home_tables_heading', true );
						echo '<h2><a href="' . $directory_page_link . '">' . sanitize_text_field($heading) . '</a></h2>';
						?>

						<?php
						$listing_ids = get_post_meta( $post->ID, '_hc_home_tables_listing_ids', true );
						$args        = array(
							'post_type'      => 'listing',
							'post__in'       => $listing_ids,
							'orderby'        => 'post__in',
							'posts_per_page' => -1,
							'fields'         => 'ids',
						);

						$listings = get_posts( $args );
						?>
						<div class="listings-slider">
							<div class="listing-slider-for">
								<?php
								foreach( $listings as $post_id ) {
									?>
									<div>
										<?php
										echo '<a href="' . get_permalink($post_id) . '">';
											echo get_the_post_thumbnail( $post_id, 'archive' );
										echo '</a>';
										?>

										<div class="slide-content show-phone">
											<h3><?php echo HC()->entry->get_headline_title( $post_id ); ?></h3>
										</div>
									</div>
									<?php
								}
								?>
							</div>

							<div class="listing-slider-nav">
								<?php
								foreach( $listings as $post_id ) {
									?>
									<div>
										<a href="<?php echo get_permalink($post_id); ?>">
											<div class="listing-slide-left">
												<div class="inner">
													<h3><?php echo HC()->entry->get_headline_title( $post_id ); ?></h3>
												</div>
											</div>

											<div class="listing-slide-right">
												<div class="inner">
													<div class="left">
														<i class="ico-pin-filled"></i>
													</div>

													<div class="right">
														<div class="address">
															<?php
															$address = get_post_meta( $post_id, '_hc_listing_address_text', true );
															if( !empty($address) )
																echo sanitize_text_field($address);
															?>
														</div>

														<div class="contact">
															<?php
															$contact = get_post_meta( $post_id, '_hc_listing_phone', true );
															if( !empty($contact) )
																echo sanitize_text_field($contact);
															?>
														</div>
													</div>
												</div>
											</div>
										</a>
									</div>
									<?php
								}
								?>
							</div>

							<div class="listings-more">
								<?php
								$page_id = get_field( '_hc_directory_page_id', 'option' );
								?>
								<a href="<?php echo get_permalink($page_id); ?>" class="inner">
									<div class="left">
										<?php
										$heading = get_post_meta( $post->ID, '_hc_home_more_listings_label', true );
										?>
										<h3><?php echo $heading; ?></h3>
									</div>

									<div class="right">
										<i class="ico-arrow-right"></i>
									</div>
								</a>
							</div>
						</div>

						<div class="mobile-bar show-phone">
							<a href="<?php echo $directory_page_link; ?>" class="btn btn-icon"><span>More Restaurants</span> <i class="ico-arrow-right"></i></a>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</section>
		<?php

		$mpu = HC()->ads->get_ad_container( 'mpu-2-mobile' );
		if( !empty($mpu) ) {
			?>
			<div class="home-mobile-promo show-phone">
				<?php
				echo $mpu;
				?>
			</div>
			<?php
		}

	}
	function takeover() {
					// Takeover Ad
					if( have_rows('_hc_takeover') ):
						while ( have_rows('_hc_takeover') ) : the_row();
						$bg_color = get_sub_field('background_color');
						$image = get_sub_field('image');
						$url = get_sub_field('url');
					if ($image) {
				?>
					<section class="home-section home-section-takeover">
						<div class="takeover" style="background: <?php echo $bg_color; ?> ;"><a href="<?php echo $url ?>"><img src="<?php echo $image['url'] ?>"></a></div>
					</section>
				<?php
					}
						endwhile;
					else :
					endif;
				?>

			<?php
	}
	public function do_featured_events_and_join() {

		global $post;

		$enable = get_post_meta( $post->ID, '_hc_home_enable_featured_events', true );
		if( empty($enable) )
			return;

		$events = get_post_meta( $post->ID, '_hc_home_featured_event_ids', true );

		$events_page_id   = get_field( '_hc_calendar_page_id', 'option' );
		$events_page_link = get_permalink($events_page_id);

		?>
		<section class="home-section home-section-events-join">
			<div class="wrap">
				<?php
				$heading = get_post_meta( $post->ID, '_hc_home_featured_events_heading', true );
				echo '<h2><a href="' . $events_page_link . '">' . sanitize_text_field($heading) . '</a></h2>';
				?>

				<div class="three-fourths first left hide-no-js">
					<?php
					HC()->events->display_slider($events);
					?>

					<div class="mobile-bar show-phone">
						<?php  ?>
						<a href="<?php echo $events_page_link; ?>" class="btn btn-icon"><span>More Events</span> <i class="ico-arrow-right"></i></a>
					</div>
				</div>

				<div class="one-fourth right join">
					<div class="join-inner">
						<i class="ico-heart"></i>

						<?php
						if( is_user_logged_in() ) {
							$title = get_option( 'options__hc_join_user_title' );
							$text  = get_option( 'options__hc_join_user_text' );

							echo !empty($title) ? '<h3>' . $title . '</h3>' : '';
							echo !empty($text) ? wpautop($text) : '';
						} else {
							$title = get_option( 'options__hc_join_visitor_title' );
							$text  = get_option( 'options__hc_join_visitor_text' );

							echo !empty($title) ? '<h3>' . $title . '</h3>' : '';
							?>
							<button class="btn open-popup-link" data-mfp-src="#login-popup">Sign Up <i class="ico-exit"></i></button>
							<?php
							echo !empty($text) ? wpautop($text) : '';
						}
						?>
					</div>
				</div>
			</div>
		</section>
		<?php

	}

	public function do_trending() {
		global $post;
		$enable = get_post_meta( $post->ID, '_hc_home_enable_trending', true );
		if( empty($enable) )
			return;
		$transient_name = 'hc_home_trending_ids';
		$post_ids       = get_transient($transient_name);
		if( false === $post_ids ) {
			$post_ids = HC()->trending->get_trending();
			set_transient( $transient_name, $post_ids, HOUR_IN_SECONDS * 4 );
		}
		if( empty($post_ids) )
			return;
		?>
		<section class="home-section home-section-trending">
			<div class="wrap">
				<?php
				$heading = get_post_meta( $post->ID, '_hc_home_trending_heading', true );
				echo '<h2>' . sanitize_text_field($heading) . '</h2>';
				?>

				<div class="clearfix trending-slider hide-no-js">
					<?php
					$i = 1;
					foreach( $post_ids as $post_id ) {
						if( !has_post_thumbnail($post_id) )
							continue;

						$term = HC()->utilities->get_primary_term( $post_id, 'category' );
						?>
						<div>
							<a href="<?php echo get_permalink($post_id); ?>">
								<?php
								echo get_the_post_thumbnail( $post_id, 'archive-small' );
								?>

								<i class="ico-hexagon"></i>
								<span><?php echo $i; ?></span>

								<div class="overlay">
									<div>
										<?php
										if( !empty($term) ) {
											?>
											<div class="hide-phone">
												<?php
												echo HC()->utilities->get_category_icon_html( $term, 'large', 'white' );
												?>
											</div>

											<div class="show-phone">
												<?php
												echo HC()->utilities->get_category_icon_html( $term, 'small', 'white' );
												?>
											</div>
											<?php
										}

										?>

										<h3><?php echo HC()->entry->get_headline_title($post_id); ?></h3>
									</div>
								</div>
							</a>
						</div>
						<?php
						++$i;
					}
					?>
				</div>
			</div>
		</section>
		<?php

	}

	private function display_posts( $offset = 0 ) {

		$args = array(
			'posts_per_page'         => 8,
			'offset'                 => $offset,
			'post_type'              => 'post',
			'update_post_term_cache' => false,
		);
		$posts = get_posts( $args );

		$i = 1;
		foreach( $posts as $post ) {
			echo 1 === $i % 4 ? '<div class="one-fourth first">' : '<div class="one-fourth">';
				HC()->archives->display_entry( $post, 'small' );
			echo '</div>';
			++$i;
		}

	}

	public function get_next_page_html() {

		global $wp_query;

		if( !isset($_POST['offset']))
			wp_die();

		$offset = absint($_POST['offset']);
		$this->display_posts( $offset );

		wp_die();

	}

	public function do_latest_posts() {

		global $post;

		$enable = get_post_meta( $post->ID, '_hc_home_enable_slider_latest_posts', true );
		if( empty($enable) )
			return;

		?>
		<section class="home-section home-section-latest-posts">
			<div class="wrap">
				<?php
				$ad = HC()->ads->get_ad_container( 'leaderboard-1' );
				if( !empty($ad) )
					echo '<div class="banner">' . $ad . '</div>';
				?>

				<h2>
					<?php
					$heading = get_post_meta( $post->ID, '_hc_home_latest_posts_heading', true );
					echo trim($heading);
					?>
				</h2>

				<div class="block clearfix" data-offset="8" data-total="100">
					<?php
					$this->display_posts();
					?>
				</div>
			</div>
		</section>
		<?php

	}

}

return new HC_Home();
