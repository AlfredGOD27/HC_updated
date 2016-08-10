<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Events {
	public function __construct() {

		$this->slug   = 'event';
		$this->editor = new HC_Post_Type_Editor('event', array('add'), 'HC_Event_Editor' );

		add_action( 'init', array($this, 'register') );
		add_action( 'admin_notices', array($this, 'maybe_display_invalid_dates_warning') );
		add_action( 'wp', array($this, 'init') );
		add_action( 'pre_get_posts', array($this, 'filter_events_query') );
		add_action( 'genesis_before', array($this, 'takeover') );
		add_action( 'genesis_after', array($this, 'takeover') );

	}
	
	function takeover() { 
					// Takeover Ad
					if( have_rows('_hc_events_takeover') ):
						while ( have_rows('_hc_events_takeover') ) : the_row();
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

	public function register() {

		register_post_type( 'event',
			array(
				'labels' => array(
					'name'               => __('Events', 'post type general name'),
					'singular_name'      => __('Event', 'post type singular name'),
					'add_new'            => __('Add New', 'custom post type item'),
					'add_new_item'       => __('Add New Event'),
					'edit'               => __( 'Edit' ),
					'edit_item'          => __('Edit Event'),
					'new_item'           => __('New Event'),
					'view_item'          => __('View Event'),
					'search_items'       => __('Search Events'),
					'not_found'          => __('Nothing found in the Database.'),
					'not_found_in_trash' => __('Nothing found in Trash'),
					'parent_item_colon'  => '',
				),
				'public'          => true,
				'has_archive'     => false,
				'capability_type' => 'post',
				'hierarchical'    => false,
				'menu_icon'       => 'dashicons-calendar-alt',
				'rewrite'         => array('slug' => $this->slug),
				'supports'        => array('title', 'editor'),
			)
		);

		$labels = array(
			'name'              => _x( 'Event Categories', 'taxonomy general name' ),
			'singular_name'     => _x( 'Event Category', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Event Categories' ),
			'all_items'         => __( 'All Event Categories' ),
			'parent_item'       => __( 'Parent Event Category' ),
			'parent_item_colon' => __( 'Parent Event Category:' ),
			'edit_item'         => __( 'Edit Event Category' ),
			'update_item'       => __( 'Update Event Category' ),
			'add_new_item'      => __( 'Add New Event Category' ),
			'new_item_name'     => __( 'New Event Category Name' ),
			'menu_name'         => __( 'Event Category' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array('slug' => 'event-category'),
		);

		register_taxonomy( 'event-category', array('event'), $args );

	}

	public function init() {

		if( is_singular('event') ) {
			add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
			remove_action( 'genesis_before_loop', 'hc_do_breadcrumbs' );
			remove_action( 'genesis_loop', 'genesis_do_loop' );
			add_action( 'genesis_loop', array($this, 'do_single_event') );
		} else {
			if( 'page_templates/page_calendar.php' === get_page_template_slug() ) {
				add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
				remove_action( 'genesis_before_loop', 'hc_do_breadcrumbs' );
				remove_action( 'genesis_loop', 'genesis_do_loop' );
				add_action( 'genesis_loop', array($this, 'do_calendar') );
				// add_action( 'wp_footer', array($this, 'display_post_event_modal') );
			}
		}

	}

	public function maybe_display_invalid_dates_warning() {

		if( !isset($_GET['action']) )
			return;

		if( 'edit' !== $_GET['action'] )
			return;

		if( !isset($_GET['post']) )
			return;

		$post_id = absint($_GET['post']);
		if( 'event' !== get_post_type($post_id) )
			return;

		$dates = $this->get_event_date_info( $post_id );
		if( $dates['start_datetime'] > $dates['end_datetime'] ) {
			?>
			<div class="error">
				<p><strong>Warning:</strong> This event's end date is before its start date.</p>
			</div>
			<?php
		}

	}

	public function get_date_query_args( $direction = 'future' ) {

		$args = array(
			'post_type'      => 'event',
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_hc_event_start_date',
			'posts_per_page' => -1,
		);

		if( 'past' === $direction ) {
			$args['order']      = 'DESC';
			$args['meta_query'] = array(
				array(
					'key'     => '_hc_event_end_date',
					'value'   => date('Ymd'),
					'compare' => '<',
				),
			);
		} else {
			$args['order']      = 'ASC';
			$args['meta_query'] = array(
				array(
					'key'     => '_hc_event_end_date',
					'value'   => (date('Ymd') - 1),
					'compare' => '>=',
				),
			);
		}

		return $args;

	}

	public function filter_events_query( $query ) {

		// Stop if not event category
		if( !$query->is_tax('event-category') )
			return;

		// Stop if not main query
		if( !$query->is_main_query() )
			return;

		$args = $this->get_date_query_args();
		foreach( $args as $key => $value )
			$query->set( $key, $value );

	}

	public function get_event_date_info( $post_id ) {

		$info = array();

		$all_day         = get_post_meta( $post_id, '_hc_event_all_day', true );
		$info['all_day'] = !empty($all_day);

		$start_date         = get_post_meta( $post_id, '_hc_event_start_date', true );
		$info['start_date'] = !empty($start_date) ? strtotime($start_date) : false;

		if( !$info['all_day'] ) {
			$start_time         = get_post_meta( $post_id, '_hc_event_start_time', true );
			$info['start_time'] = !empty($start_time) ? $start_time : false;
		}

		if( !$info['all_day'] && false !== $info['start_time'] ) {
			$info['start_datetime'] = strtotime( $start_date . ' ' . $start_time );
		} else {
			$info['start_datetime'] = $info['start_date'];
		}

		$end_date         = get_post_meta( $post_id, '_hc_event_end_date', true );
		$info['end_date'] = !empty($end_date) ? strtotime($end_date) : false;

		if( !$info['all_day'] ) {
			$end_time         = get_post_meta( $post_id, '_hc_event_end_time', true );
			$info['end_time'] = !empty($end_time) ? $end_time : false;
		}

		if( !$info['all_day'] && false !== $info['end_time'] ) {
			$info['end_datetime'] = strtotime( $end_date . ' ' . $end_time );
		} else {
			$info['end_datetime'] = $info['end_date'];
		}

		return $info;

	}

	public function get_date_string( $date, $format = 'l, F j' ) {

		$date_string = '';
		if( false !== $date['start_date'] && false !== $date['end_date'] ) {
			$start_date = date( $format, $date['start_date'] );
			$end_date   = date( $format, $date['end_date'] );

			if( $start_date !== $end_date ) {
				$date_string = $start_date . ' - ' . $end_date;
			} else {
				$date_string = $start_date;
			}
		} elseif( false !== $date['start_date'] ) {
			$start_date  = date( $format, $date['start_date'] );
			$date_string = $start_date;
		} elseif( false !== $date['end_date'] ) {
			$end_date    = date( $format, $date['end_date'] );
			$date_string = $end_date;
		}

		return $date_string;

	}

	public function get_event_image( $post_id, $size ) {

		if( has_post_thumbnail($post_id) ) {
			$image_id = get_post_thumbnail_id($post_id);
		} else {
			$term = HC()->utilities->get_primary_term( $post_id, 'event-category' );
			if( !empty($term) )
				$image_id = get_field( '_hc_fallback_image_id', $term );
		}

		if( !empty($image_id) )
			echo wp_get_attachment_image( $image_id, $size );

	}

	public function do_single_event() {

		global $post;

		printf( '<article %s>', genesis_attr( 'entry' ) );
			?>
			<div class="one-half first">
				<?php
				genesis_entry_header_markup_open();
					genesis_do_post_title();

					$categories = wp_get_object_terms( $post->ID, 'event-category' );
					if( !empty($categories) ) {
						$category_links = array();

						foreach( $categories as $category )
							$category_links[] = '<a href="' . get_term_link($category) . '">' . $category->name . '</a>';

						echo '<p class="entry-meta">' . HC()->formatting->build_comma_separated_list($category_links) . '</p>';
					}
				genesis_entry_header_markup_close();
				?>

				<div class="show-phone">
					<?php
					HC()->utilities->display_basic_slider( $post->ID );
					?>
				</div>

				<?php
				$lines = array();

				// Date
				$date        = $this->get_event_date_info( $post->ID );
				$date_string = $this->get_date_string( $date );
				if( !empty($date_string) )
					$lines['Date'] = $date_string;

				// Time
				if( !$date['all_day'] ) {
					if( false !== $date['start_time'] && false !== $date['end_time'] ) {
						$start_time = $date['start_time'];
						$end_time   = $date['end_time'];

						if( $start_time !== $end_time ) {
							$lines['Time'] = $start_time . ' - ' . $end_time;
						} else {
							$lines['Time'] = $start_time;
						}
					} elseif( false !== $date['start_time'] ) {
						$lines['Time'] = $date['start_time'];
					} elseif( false !== $date['end_time'] ) {
						$lines['Time'] = $date['end_time'];
					}
				}
				?>
				<meta itemprop="startDate" content="<?php echo date('c', $date['start_datetime']); ?>">
				<meta itemprop="endDate" content="<?php echo date('c', $date['end_datetime']); ?>">
				<?php

				// Venue
				$venue = get_post_meta( $post->ID, '_hc_event_venue', true );
				if( !empty($venue) ) {
					$venue = sanitize_text_field($venue);
					?>
					<meta itemprop="location" content="<?php echo esc_attr($venue); ?>">
					<?php
					$lines['Venue'] = $venue;
				}

				// Price
				$price = get_post_meta( $post->ID, '_hc_event_price', true );
				if( !empty($price) )
					$lines['Price'] = sanitize_text_field($price);

				// Contact
				$contact = get_post_meta( $post->ID, '_hc_event_contact', true );
				if( !empty($contact) )
					$lines['Contact'] = '<a href="mailto:' . sanitize_email($contact) . '">' . sanitize_text_field($contact) . '</a>';

				// Website
				$website = get_post_meta( $post->ID, '_hc_event_website', true );
				if( !empty($website) )
					$lines['Website'] = HC()->formatting->get_linked_url( $website );

				if( count($lines) > 0 )
					HC()->formatting->display_data_list($lines);
				?>

				<div class="item-action-row">
					<?php HC()->folders->display_add_button( $post->ID ); ?>
					<?php HC()->share->display( $post->ID ); ?>

					<?php
					$start = date( 'Ymd', $date['start_datetime'] );
					$start .= 'T';
					$start .= date( 'His', $date['start_datetime'] );

					if( !$date['all_day'] || $date['start_datetime'] !== $date['end_datetime'] ) {
						$end = date( 'Ymd', $date['end_datetime'] );
						$end .= 'T';
						$end .= date( 'His', $date['end_datetime'] );
					} else {
						$end = date( 'Ymd', $date['end_datetime'] + DAY_IN_SECONDS );
						$end .= 'T';
						$end .= date( 'His', $date['end_datetime'] );
					}

					$url = add_query_arg(
						array(
							'action'   => 'TEMPLATE',
							'text'     => urlencode( $post->post_title ),
							'details'  => urlencode( HC()->formatting->get_excerpt($post) ),
							'location' => urlencode( $venue ),
							'dates'    => urlencode( $start . '/' . $end ),
						),
						'http://www.google.com/calendar/event'
					);
					?>
					<a href="<?php echo $url; ?>" class="calendar-button btn btn-icon" target="_blank"><i class="ico-calendar"></i> <span>Add to Google Calendar</span></a>
				</div>

				<?php printf( '<div %s>', genesis_attr( 'entry-content' ) ); ?>
					<?php the_content(); ?>
				</div>
			</div>

			<div class="one-half">
				<div class="hide-phone">
					<?php
					HC()->utilities->display_basic_slider( $post->ID );
					?>
				</div>
				<?php

				$map_address = get_post_meta( $post->ID, '_hc_event_map_address', true );
				if( !empty($map_address) )
					HC()->formatting->display_map($map_address, 630, 300);
				?>
			</div>

			<?php
			HC()->related->display_related_content( $post, 'aside' );
			?>
		</article>
		<?php

	}

	public function display_slider( $events ) {

		?>
		<div class="event-slider-for">
			<?php
			foreach( $events as $post_id ) {
				$date = HC()->events->get_event_date_info( $post_id );
				?>
				<div>
					<?php
					echo '<a href="' . get_permalink($post_id) . '">';
						echo $this->get_event_image( $post_id, 'slide' );
					echo '</a>';
					?>

					<div class="slide-content show-phone">
						<div class="info">
							<span class="m"><?php echo date('M', $date['start_date']); ?></span>
							<span class="d"><?php echo date('j', $date['start_date']); ?></span>
						</div>

						<div class="name">
							<?php
							$term = HC()->utilities->get_primary_term( $post_id, 'event-category' );
							if( !empty($term) )
								echo '<span class="cat">' . $term->name . '</span>';

							echo '<span class="title">' . HC()->formatting->maybe_truncate( HC()->entry->get_headline_title( $post_id ), 45 ) . '</span>';
							?>
						</div>

						<?php
						if( !empty($term) )
							echo HC()->utilities->get_category_icon_html( $term );
						?>
					</div>
				</div>
				<?php
			}
			?>
		</div>

		<div class="event-slider-nav">
			<?php
			foreach( $events as $post_id ) {
				$date = HC()->events->get_event_date_info( $post_id );
				?>
				<div>
					<div class="outer">
						<?php
						echo $this->get_event_image( $post_id, 'slide-thumbnail' );
						?>

						<a class="inner" href="<?php echo get_permalink($post_id); ?>">
							<div class="info">
								<span class="m"><?php echo date('M', $date['start_date']); ?></span>
								<span class="d"><?php echo date('j', $date['start_date']); ?></span>
							</div>

							<div class="name">
								<?php
								$term = HC()->utilities->get_primary_term( $post_id, 'event-category' );
								if( !empty($term) )
									echo '<span class="cat">' . $term->name . '</span>';

								echo '<span class="title">' . HC()->formatting->maybe_truncate( HC()->entry->get_headline_title( $post_id ), 25 ) . '</span>';
								?>
							</div>

							<?php
							if( !empty($term) )
								echo HC()->utilities->get_category_icon_html( $term );
							?>
						</a>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php

	}

	private function do_calendar_subcategory( $name, $events, $term = false ) {

		?>
        <section class="events_leaderboard">
        	<div>
            <?php
			$ad = HC()->ads->get_ad_container( 'leaderboard-1' );
			if( !empty($ad) )
				echo '<div class="banner">' . $ad . '</div>';
			?>
            </div>
        </section>
        
        
		<section class="subcategory">
			<div class="wrap">
            
				<div class="subcategory-description">
					<?php
					if( false === $term ) {
						?>
						<h2 class="archive-title"><?php echo $name; ?></h2>
						<?php
					} else {
						?>
						<h2 class="archive-title"><a href="<?php echo get_term_link($term); ?>"><?php echo $name; ?></a></h2>
						<?php
					}
					?>
				</div>

				<div class="events-slider hide-no-js">
					<?php
					$dates    = array();
					$one_time = array();
					$ongoing  = array();
					foreach( $events as $event ) {
						$dates[$event->ID] = $this->get_event_date_info( $event->ID );

						if( $dates[$event->ID]['start_date'] === $dates[$event->ID]['end_date'] ) {
							$one_time[] = $event;
						} else {
							$ongoing[] = $event;
						}
					}

					$events = array_merge($one_time, $ongoing);

					foreach( $events as $event ) {
						$text = HC()->entry->get_headline_title($event->ID) . ' ' . $event->post_content;
						$text = sanitize_text_field($text);
						$text = strtolower($text);

						$category_ids = array();
						$categories   = wp_get_object_terms( $event->ID, 'event-category' );
						foreach( $categories as $category )
							$category_ids[] = $category->term_id;

						$date = $dates[$event->ID];

						?>
						<div class="event-slide" data-text="<?php echo esc_attr($text); ?>" data-category_ids="<?php echo implode( ',', $category_ids ); ?>" data-start_date="<?php echo $date['start_datetime']; ?>" data-end_date="<?php echo $date['end_datetime']; ?>">
							<a href="<?php echo get_permalink($event->ID); ?>">
								<?php
								echo $this->get_event_image($event->ID, 'archive-small' );
								?>

								<div class="inner">
									<span class="title"><?php echo HC()->entry->get_headline_title($event->ID); ?></span>
									<span class="date">
										<?php
										if( date( 'Ymd', $date['start_date'] ) !== date( 'Ymd', $date['end_date'] ) ) {
											echo date( 'M j', $date['start_date'] ) . ' - ' . date( 'M j', $date['end_date'] );
										} else {
											echo date( 'M j', $date['start_date'] );
										}
										?>
									</span>
								</div>
							</a>
						</div>
						<?php
					}
					?>
				</div>
			</div>
		</section>
		<?php

	}

	public function do_calendar() {

		global $post;

		$event_ids = get_post_meta( $post->ID, '_hc_calendar_slider_events', true );
		if( !empty($event_ids) ) {
			$args = array(
				'post_type'      => 'event',
				'post__in'       => $event_ids,
				'orderby'        => 'post__in',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			$events = get_posts( $args );
			if( !empty($events) ) {
				?>
				<section class="archive-slider-container hide-no-js">
					<div class="wrap">
						<?php
						HC()->events->display_slider($events);
						?>
					</div>
				</section>
				<?php
			}
		}

		$picks_term = get_term_by( 'slug', 'editors-picks', 'event-category' );

		$args = array(
			'exclude'  => array($picks_term->term_id),
			'taxonomy' => 'event-category',
		);
		$terms = get_terms( $args );
		if( empty($terms) )
			return;

		$terms = array_merge(  array($picks_term), $terms );

		?>
		<section class="calendar-search-bar">
			<div class="wrap">


				<form class="clearfix">
					<div class="head">
						<h2>Search Events</h2>
					</div>

					<div class="search">
						<label for="calendar-search">Search</label>
						<input id="calendar-search" type="search" name="search" placeholder="What's happening this Friday night...">
					</div>

					<div class="one">
						<label for="calendar-category">Date</label>
						<select id="calendar-category" name="category" class="styled">
							<option value="">Select a Category</option>
							<?php
							foreach( $terms as $term ) {
								?>
								<option value="<?php echo $term->term_id; ?>"><?php echo $term->name; ?></option>
								<?php
							}
							?>
						</select>
						<i class="ico-arrow-down"></i>
					</div>

					<div class="one">
						<label for="calendar-date">Date</label>
						<input id="calendar-date" type="text" name="date" class="datepicker" placeholder="Date">
						<i class="ico-arrow-down"></i>
					</div>

					<div class="post">
						<?php
						if( is_user_logged_in() ) {
							?>
							<a href="<?php echo $this->editor->get_add_url(); ?>" class="btn">Post an Event</a>
							<?php
						} else {
							?>
							<button type="button" class="btn open-popup-link" data-mfp-src="#login-popup" data-redirect="<?php echo $this->editor->get_add_url(); ?>">Post an Event</button>
							<?php
						}
						?>
					</div>
				</form>

                
			</div>
		</section>
		<?php

		$args = array(
			'post_type'      => 'event',
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_hc_event_start_date',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_hc_event_start_date',
					'value'   => (int) date('N') === 1 ? date('Ymd') : date('Ymd', strtotime('last monday') ),
					'compare' => '>=',
				),
				array(
					'key'     => '_hc_event_end_date',
					'value'   => (int) date('N') === 7 ? date('Ymd') : date('Ymd', strtotime('next sunday') ),
					'compare' => '<=',
				),
			),
		);
		$events = get_posts( $args );
		if( !empty($events) )
			$this->do_calendar_subcategory( 'This Week', $events );

		foreach( $terms as $term ) {
			$args                   = $this->get_date_query_args();
			$args['posts_per_page'] = -1;
			$args['tax_query']      = array(
				array(
					'taxonomy' => $term->taxonomy,
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			);
			$events = get_posts( $args );
			if( empty($events) )
				continue;

			$this->do_calendar_subcategory( $term->name, $events, $term );
		}

	}

	public function display_post_event_modal() {

		$logged_in = is_user_logged_in();

		?>
		<aside id="post-event-popup" class="post-event-popup white-popup mfp-hide clearfix">
			<i class="ico-favicon"></i>

			<h2>Post to the Honeycombers Calendar</h2>

			<p class="lead">Please select from the options below</p>

			<div class="inner clearfix">
				<div class="one-fourth first">
					<div class="top">
						<h3>Free</h3>

						<p>$0</p>

						<ul>
							<li>1 standard listing</li>
							<li>50 word description</li>
							<li>1 image</li>
						</ul>
					</div>

					<div class="bottom">
						<?php
						$url = add_query_arg(
							array(
								'level' => 'free',
							),
							$this->editor->get_add_url()
						);

						if( $logged_in ) {
							?>
							<a href="<?php echo $url; ?>" class="btn">Select</a>
							<?php
						} else {
							?>
							<button type="button" class="btn open-popup-link" data-mfp-src="#login-popup" data-redirect="<?php echo $url; ?>">Select</button>
							<?php
						}
						?>
					</div>
				</div>

				<div class="one-fourth">
					<div class="top">
						<h3>Upgrade</h3>

						<p>$200</p>

						<ul>
							<li>3 listings</li>
							<li>300 word description</li>
							<li>5 images per listing</li>
							<li>1 editor’s pick on calendar page for 1 month</li>
						</ul>
					</div>

					<div class="bottom">
						<?php
						$url = add_query_arg(
							array(
								'level' => 'upgrade',
							),
							$this->editor->get_add_url()
						);

						if( $logged_in ) {
							?>
							<a href="<?php echo $url; ?>" class="btn">Select</a>
							<?php
						} else {
							?>
							<button type="button" class="btn open-popup-link" data-mfp-src="#login-popup" data-redirect="<?php echo $url; ?>">Select</button>
							<?php
						}
						?>
					</div>
				</div>

				<div class="one-fourth">
					<div class="top">
						<h3>Premium</h3>

						<p>$1,500</p>

						<ul>
							<li>5 listings</li>
							<li>Unlimited description</li>
							<li>10 images per listing</li>
							<li>1 hero listing in calendar page, homepage, and all sidebars for 1 month</li>
							<li>2 editor's picks on calendar page for 1 month each</li>
							<li>1 EDM inclusion in our weekly newsletter</li>
						</ul>
					</div>

					<div class="bottom">
						<?php
						$url = add_query_arg(
							array(
								'level' => 'premium',
							),
							$this->editor->get_add_url()
						);

						if( $logged_in ) {
							?>
							<a href="<?php echo $url; ?>" class="btn">Enquire</a>
							<?php
						} else {
							?>
							<button type="button" class="btn open-popup-link" data-mfp-src="#login-popup" data-redirect="<?php echo $url; ?>">Enquire</button>
							<?php
						}
						?>
					</div>
				</div>

				<div class="one-fourth">
					<div class="top">
						<h3>Premium</h3>

						<p>POA</p>

						<ul>
							<li>Tailor-made articles and listings</li>
							<li>EDM Inclusions</li>
							<li>Social amplification</li>
							<li>Prime positioning on Honeycombers</li>
						</ul>
					</div>

					<div class="bottom">
						<a href="/contact-us/" class="btn">Enquire</a>
					</div>
				</div>
			</div>
		</aside>
		<?php

	}

}

return new HC_Events();
