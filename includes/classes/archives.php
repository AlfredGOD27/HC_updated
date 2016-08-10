<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Archives {
	public function __construct() {

		$this->count      = 0;
		$this->post_types = array('page', 'post', 'event', 'listing');

		remove_action( 'genesis_before_loop', 'genesis_do_author_title_description', 15 );
		remove_action( 'genesis_before_loop', 'genesis_do_taxonomy_title_description', 15 );
		remove_action( 'genesis_entry_content', 'genesis_do_post_image', 8 );
		add_action( 'genesis_entry_header', 'genesis_do_post_image', 8 );

		add_action( 'wp', array($this, 'init') );
		add_action( 'wp_ajax_hc_get_next_page_html', array($this, 'get_next_page_html') );
		add_action( 'wp_ajax_nopriv_hc_get_next_page_html', array($this, 'get_next_page_html') );

	}

	public function init() {

		$this->mode       = false;
		$this->has_slider = false;
		$this->post_style = 'half';
		if( is_search() ) {
			// If search, set to infinite mode and fix title
			add_action( 'genesis_after_header', array($this, 'do_search_title'), 14 );
			$this->mode = 'infinite';
		} elseif( is_author() ) {
			// If search, set to infinite mode and fix title
			add_action( 'genesis_after_header', array($this, 'do_author_box'), 14 );
			$this->mode       = 'infinite';
			$this->post_style = 'full';
		} elseif( is_archive() ) {
			add_action( 'genesis_after_header', array($this, 'do_taxonomy_title_description'), 14 );

			$this->term = get_queried_object();

			// If archive, check for slider settings. If present, show slider. Otherwise, show archive title.
			$this->slider_mode = get_field( '_hc_category_slider_type', $this->term );
			$page              = get_query_var( 'paged', 0 );
			if(
				in_array( $this->slider_mode, array('manual', 'recent'), true ) &&
				0 === $page
			) {
				$this->has_slider = true;
				add_action( 'genesis_after_header', array($this, 'slider'), 16 );
			}

			// If is top level category with subcategories, show sections. Otherwise, show infinite.
			if( !empty($this->term->parent) ) {
				$this->mode = 'infinite';
				add_action( 'genesis_after_header', array($this, 'cat_leaderboard'), 13 );
			} else {
				$args = array(
					'parent' => $this->term->term_id,
				);
				$this->subcategories = get_terms( $this->term->taxonomy, $args );
				if( count($this->subcategories) <= 1 ) {
					$this->mode = 'infinite';
				} else {
					$this->mode = 'sub-sections';
				}
			}

			add_action( 'genesis_after_header', array($this, 'cat_leaderboard'), 17 );
		}

		if( false === $this->mode )
			return;

		// General hooks
		add_action( 'body_class', array($this, 'body_class') );
		remove_action( 'genesis_before_loop', 'hc_do_breadcrumbs' );
		remove_action( 'genesis_loop', 'genesis_do_loop' );

		switch( $this->mode ) {
			case 'infinite':
				add_action( 'genesis_loop', array($this, 'archive_loop') );
				break;
			case 'sub-sections':
				add_action( 'genesis_loop', array($this, 'subcategory_sections') );
				remove_action( 'genesis_after_endwhile', 'genesis_posts_nav' );
				break;
		}

	}

	public function get_next_page_html() {

		global $wp_query;

		if( empty($_POST['term_id']))
			wp_die();

		if( empty($_POST['taxonomy']))
			wp_die();

		$term_id  = absint($_POST['term_id']);
		$taxonomy = sanitize_title($_POST['taxonomy']);
		$term     = get_term_by( 'term_id', $term_id, $taxonomy );
		if( empty($term) || is_wp_error($term) )
			wp_die();

		if( !isset($_POST['offset']))
			wp_die();

		$offset = absint($_POST['offset']);

		$args = array(
			'posts_per_page' => 4,
			'post_type'      => $this->post_types,
			'offset'         => $offset,
			'tax_query'      => array(
				array(
					'taxonomy' => $term->taxonomy,
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			),
		);
		$wp_query = new WP_Query( $args );

		$this->mode       = 'sub-sections';
		$this->post_style = 'half';

		remove_action( 'genesis_after_endwhile', 'genesis_posts_nav' );

		$this->archive_loop();

		wp_reset_query();

		wp_die();

	}

	public function body_class( $classes ) {

		if( 'infinite' === $this->mode )
			$classes[] = 'infinite-scroll';

		if( 'sub-sections' === $this->mode )
			$classes[] = 'archive-sub-sections';

		if( $this->has_slider )
			$classes[] = 'archive-has-slider';

		return $classes;

	}

	public function do_search_title() {

		global $wp_query;

		echo sprintf(
			'<div class="archive-description"><div class="wrap"><h1 class="archive-title">Your search for "%s" found %d %s</h1></div></div>',
			get_search_query(),
			$wp_query->found_posts,
			_n( 'result', 'results', $wp_query->found_posts )
		);

	}

	public function do_author_box() {

		$page = get_query_var( 'paged', 0 );
		if( !empty($page) )
			return;

		HC()->authors->do_author_box( 'archive' );

	}

	public function do_taxonomy_title_description() {

		$headline = get_term_meta( $this->term->term_id, 'headline', true );
		if( !empty($headline) ) {
			$headline = sprintf( '<h1 %s>%s</h1>', genesis_attr( 'archive-title' ), strip_tags( $headline ) );
		} else {
			$headline = sprintf( '<h1 %s>%s</h1>', genesis_attr( 'archive-title' ), strip_tags( $this->term->name ) );
		}

		$intro_text = get_term_meta( $this->term->term_id, 'intro_text', true );
		if( !empty($intro_text) )
			$intro_text = apply_filters( 'genesis_term_intro_text_output', $intro_text );

		if( !empty($headline) || !empty($intro_text) )
			printf( '<div %s><div class="wrap">%s</div></div>', genesis_attr( 'taxonomy-archive-description' ), $headline . $intro_text );

	}

	public function slider() {

		$args = array(
			'post_type' => $this->post_types,
			'tax_query' => array(
				array(
					'taxonomy' => $this->term->taxonomy,
					'field'    => 'term_id',
					'terms'    => $this->term->term_id,
				),
			),
			'fields' => 'ids',
		);

		switch( $this->slider_mode ) {
			case 'manual':
				$args['post__in'] = get_field( '_hc_category_slider_post_ids', $this->term );
				$args['orderby']  = 'post__in';
				break;
			case 'recent':
				$post_count             = get_field( '_hc_category_post_count', $this->term );
				$args['posts_per_page'] = absint($post_count);
				break;
			default:
				return;
		}

		HC()->sliders->display( $args );

	}

	public function cat_leaderboard() {
		?>
        	<section id="leaderboard" class="clearfix cat_leaderboard">
            	<div class="content-sidebar-wrap">
					<?php
						if ($this->term->parent == 0) {
							$cat_term = $this->term->term_id;
						} elseif ($this->term->term_id == 6363) {
							$cat_term = $this->term->term_id;
						} else {
							$cat_term = $this->term->category_parent;
						}
                        // Category level 1
                        if( have_rows('_hc_leaderboard', 'category_' . $cat_term . '') ):
                            while ( have_rows('_hc_leaderboard', 'category_' . $cat_term . '') ) : the_row();
								$head = get_sub_field('head_code');
								$body = get_sub_field('body_code');
								
								if (!empty($head) && !empty($body)) {
									echo '<script>'.$head.'</script><br>'.$body.'';
								} else {	
									 echo '<div style="display:none" id="testing"><script>'.$head.'</script><br>'.$body.'</div>';
									 
								}
                            endwhile;
                        else :
                        endif;
                    ?>
            	</div>
            </section>
		<?php
	}

	public function subcategory_sections() {

		global $wp_query;

		foreach( $this->subcategories as $category ) {
			$args = array(
				'posts_per_page' => 4,
				'post_type'      => $this->post_types,
				'tax_query'      => array(
					array(
						'taxonomy' => $category->taxonomy,
						'field'    => 'term_id',
						'terms'    => $category->term_id,
					),
				),
			);
			$wp_query = new WP_Query( $args );

			?>
			<section class="subcategory clearfix" data-offset="8" data-total="<?php echo $wp_query->found_posts; ?>" data-term_id="<?php echo $category->term_id; ?>" data-taxonomy="<?php echo $category->taxonomy; ?>">
				<div class="subcategory-description">
					<h2 class="archive-title"><a href="<?php echo get_term_link($category); ?>"><?php echo $category->name; ?></a></h2>
				</div>

				<?php
				$this->archive_loop();
				?>
			</section>
			<?php

			wp_reset_query();
		}

	}

	public function archive_loop() {

		global $post;

		if( have_posts() ) {

			do_action( 'genesis_before_while' );

			$i = 1;
			while( have_posts() ) {
				the_post();

				if( 'full' === $this->post_style ) {
					$this->display_entry( $post, 'large' );
				} else {
					echo 1 === $i % 2 ? '<div class="one-half first">' : '<div class="one-half">';
						$this->display_entry( $post, 'medium' );
					echo '</div>';
				}

				++$i;
			}

			do_action( 'genesis_after_endwhile' );

		} else {
			do_action( 'genesis_loop_else' );
		}

	}

	private function display_entry_content( $post, $show_share ) {

		?>
		<h2 class="entry-title" itemprop="headline">
			<a href="<?php echo get_permalink( $post->ID ); ?>" rel="bookmark">
				<?php echo HC()->entry->get_headline_title( $post->ID ); ?>
			</a>
		</h2>

		<div class="entry-content entry-excerpt" itemprop="description">
			<?php
			echo '<p>' . HC()->formatting->get_excerpt( $post, 140 ) . '</p>';
			?>
		</div>

		<footer class="entry-footer">
			<div class="read-more-share-bar">
				<a href="<?php echo get_permalink(); ?>" class="more-link">Read more ></a>

				<?php
				if( $show_share ) {
					?>
					<div class="share">
						<?php HC()->folders->display_add_button( $post->ID ); ?>
						<?php HC()->share->display( $post->ID ); ?>
					</div>
					<?php
				}
				?>
			</div>
		</footer>
		<?php

	}

	public function display_entry( $post_or_post_id, $style, $show_byline = true ) {

		if( is_object($post_or_post_id) ) {
			$post    = $post_or_post_id;
			$post_id = $post->ID;
		} else {
			$post_id = $post_or_post_id;
			$post    = get_post( $post_id );
		}

		$has_image = has_post_thumbnail($post_id);
		if( $has_image ) {
			switch( $style ) {
				case 'tiny':
				case 'small':
					$image_size = 'archive-small';
					break;
				case 'medium':
					$image_size = 'archive';
					break;
				case 'large':
					$image_size = 'archive-large';
					break;
			}

			$image_html = get_the_post_thumbnail($post_id, $image_size );
			$has_image  = !empty($image_html);
		}

		if( $has_image ) {
			$image_html = '<a href="' . get_permalink($post_id) . '">' . $image_html . '</a>';
		}

		?>
		<article class="clearfix archive-entry archive-entry-<?php echo $style; ?> <?php echo $has_image ? 'has-image' : 'no-image'; ?>" itemscope itemtype="http://schema.org/CreativeWork">
			<?php
			switch( $style ) {
				case 'tiny':
					// Home 'other' posts
					HC()->folders->display_add_button( $post_id, true, true );

					if( $has_image ) {
						echo $image_html;
					} else {
						echo '<div class="placeholder small"></div>';
					}

					?>
					<h3 itemprop="headline">
						<a href="<?php echo get_permalink($post_id); ?>" rel="bookmark">
							<?php echo HC()->entry->get_headline_title($post_id); ?>
						</a>
					</h3>
					<?php
					break;
				case 'small':
					// Home infinite load list + related
					?>
					<div class="top">
						<?php
						$term = HC()->utilities->get_primary_term( $post->ID, 'category' );
						if( !empty($term) )
							echo HC()->utilities->get_category_icon_html( $term );

						HC()->folders->display_add_button( $post->ID, true, true );

						if( $has_image ) {
							echo $image_html;
						} else {
							echo '<div class="placeholder small"></div>';
						}
						?>
					</div>

					<div class="bottom <?php echo 'post' === $post->post_type && $show_byline ? 'roll-up' : ''; ?>">
						<h3 itemprop="headline">
							<a href="<?php echo get_permalink( $post->ID ); ?>" rel="bookmark">
								<?php echo HC()->entry->get_headline_title( $post->ID ); ?>
							</a>
						</h3>

						<?php
						if( 'post' === $post->post_type && $show_byline ) {
							?>
							<span class="author">
								By
								<?php
								$author = get_user_by( 'id', $post->post_author );
								echo $author->display_name;
								?>
							</span>
							<?php
						}
						?>
					</div>
					<?php
					break;
				case 'medium':
					// Archive two-column list
					?>
					<div class="top">
						<?php
						HC()->folders->display_add_button( $post->ID, true, true );

						if( $has_image ) {
							echo $image_html;
						} else {
							echo '<div class="placeholder medium"></div>';
						}
						?>
					</div>

					<div class="bottom">
						<?php $this->display_entry_content( $post, false ); ?>
					</div>
					<?php
					break;
				case 'large':
					// Archive one-column list
					?>
					<div class="one-half first">
						<?php
						if( $has_image ) {
							echo $image_html;
						} else {
							echo '<div class="placeholder large"></div>';
						}
						?>
					</div>

					<div class="one-half">
						<?php $this->display_entry_content( $post, true ); ?>
					</div>
					<?php
					break;
			}
			?>
		</article>
		<?php

	}
}

return new HC_Archives();
