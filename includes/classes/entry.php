<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Entry {
	public function __construct() {

		remove_action( 'genesis_entry_header', 'genesis_post_info', 12 );
		add_action( 'wp', array($this, 'init') );

	}

	public function init() {

		global $post;

		if( !is_singular('post') )
			return;

		add_filter( 'body_class', array($this, 'body_class') );

		remove_action( 'genesis_entry_header', 'genesis_do_post_title' );

		add_action( 'genesis_entry_header', array($this, 'display_header'), 12 );

		$this->header_type = get_post_meta( $post->ID, '_hc_post_header_type', true );
		$this->header_type = !empty($this->header_type) ? $this->header_type : 'default';
		switch( $this->header_type ) {
			case 'default':
				add_action( 'genesis_entry_content', array($this, 'display_excerpt'), 8 );
				break;
			case 'slideshow':
				add_action( 'genesis_after_header', array($this, 'display_slideshow'), 14 );
				break;
			case 'video':
				add_action( 'genesis_entry_content', array($this, 'display_video'), 8 );
				break;
		}

	}

	public function body_class( $classes ) {

		switch( $this->header_type ) {
			case 'default':
			case 'video':
				$classes[] = 'header-default';
				break;
			case 'slideshow':
				$classes[] = 'header-' . $this->header_type;
				break;
		}

		return $classes;

	}

	public function display_header() {

		global $post;

		switch( $this->header_type ) {
			case 'slideshow':
				?>
				<div class="clearfix inner">
					<div class="three-fifths first header-left">
						<?php genesis_do_post_title(); ?>

						<?php $this->display_excerpt(); ?>
					</div>

					<div class="two-fifths header-right clearfix">
						<div class="author header-top clearfix">
							<?php
							$user_id = get_the_author_meta( 'ID' );
							?>

							<div class="author-left">
								<?php echo get_avatar( $user_id, 85 ); ?>
							</div>

							<div class="author-right">
								<?php echo do_shortcode( '[post_author_posts_link]' ); ?>

								<?php

								$job_title = HC()->authors->get_title( $user_id );
								if( !empty($job_title) )
									echo '<p>' . $job_title . '</p>';
								?>
							</div>
						</div>

						<div class="share header-bottom">
							<?php HC()->folders->display_add_button( $post->ID ); ?>
							<?php HC()->share->display( $post->ID ); ?>
						</div>
					</div>
				</div>
				<?php
				break;
			default:
				genesis_do_post_title();

				?>
				<div class="entry-meta">
					<div class="author-share-row clearfix">
						<div class="left">
							<?php
							$lines   = array();
							$lines[] = do_shortcode( __( 'By', CHILD_THEME_TEXT_DOMAIN ) . ' [post_author_posts_link]' );

							$title = HC()->authors->get_title( $post->post_author );
							if( !empty($title) )
								$lines[] = $title;

							echo '<p>' . implode( ', ', $lines ) . '</p>';
							?>
						</div>

						<div class="right">
							<?php HC()->folders->display_add_button( $post->ID ); ?>
							<?php HC()->share->display( $post->ID ); ?>
						</div>
					</div>
				</div>
				<?php

				if( 'default' === $this->header_type )
					$this->display_image();

				break;
		}

	}

	public function display_slideshow() {

		$slides = get_field( '_hc_post_slides' );
		if( empty($slides) )
			return;

		?>
		<div class="entry-slideshow hide-no-js">
			<?php
			foreach( $slides as $slide ) {
				echo '<div>';
					$src = wp_get_attachment_image_src( $slide['ID'], 'full' );
					echo '<a href="' . $src[0] . '" class="entry-slideshow-item">';
						echo wp_get_attachment_image( $slide['ID'], 'featured' );
					echo '</a>';

					$image = get_post( $slide['ID'] );
					if( !empty($image->post_excerpt) ) {
						?>
						<div class="slide-content">
							<?php echo apply_filters( 'the_content', $image->post_excerpt ); ?>
						</div>
						<?php
					}
				echo '</div>';
			}
			?>
		</div>
		<?php

	}
	
	public function display_image() {

		global $post;

		?>
		<div class="featured-image-container">
			<?php
			$is_animated = get_post_meta( $post->ID, '_hc_image_is_animated', true );
			$image_size  = !empty($is_animated) ? 'full' : 'entry-image';

			$atts          = genesis_parse_attr( 'entry-image', array('alt' => get_the_title()) );
			$atts['class'] = 'alignnone';

			echo genesis_get_image(
				array(
					'format' => 'html',
					'size'   => $image_size,
					'attr'   => $atts,
				)
			);

			$sponsored = get_post_meta( $post->ID, '_hc_post_is_sponsored', true );
			/* Hidden at the moment
			if( !empty($sponsored) ) {
				?>
				<span class="spon-tag">Sponsored</span>
				<?php
			}
			*/
			?>
		</div>
		<?php

	}

	public function display_excerpt() {

		global $post;

		?>
		<div class="entry-excerpt" itemprop="description">
			<?php echo '<p>' . HC()->formatting->get_excerpt($post, 0) . '</p>'; ?>
		</div>
		<?php

	}

	public function display_video() {

		global $post;

		$video_url = get_post_meta( $post->ID, '_hc_post_video_url', true );
		if( empty($video_url) )
			return;

		?>
		<div class="entry-video">
			<?php echo wp_oembed_get( $video_url ); ?>
		</div>
		<?php

	}

	public function get_headline_title( $post_id ) {

		$title = get_post_meta( $post_id, '_hc_headline_title', true );
		if( !empty($title) ) {
			return sanitize_text_field($title);
		} else {
			return get_the_title($post_id);
		}

	}

}

return new HC_Entry();
