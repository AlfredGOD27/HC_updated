<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Listings {
	public function __construct() {

		$this->results_per_page = 24;
		$this->editor           = new HC_Post_Type_Editor('listing', array('add'), 'HC_Listing_Editor' );

		add_action( 'init', array($this, 'register') );
		add_action( 'wp', array($this, 'init') );

		add_action( 'wp_ajax_hc_get_listings', array($this, 'ajax_get_listings') );
		add_action( 'wp_ajax_nopriv_hc_get_listings', array($this, 'ajax_get_listings') );

		add_action( 'save_post', array($this, 'save_coords'), 10, 3 );

	}

	public function register() {

		register_post_type( 'listing',
			array(
				'labels' => array(
					'name'               => __('Listings', 'post type general name'),
					'singular_name'      => __('Listing', 'post type singular name'),
					'add_new'            => __('Add New', 'custom post type item'),
					'add_new_item'       => __('Add New Listing'),
					'edit'               => __( 'Edit' ),
					'edit_item'          => __('Edit Listing'),
					'new_item'           => __('New Listing'),
					'view_item'          => __('View Listing'),
					'search_items'       => __('Search Listings'),
					'not_found'          => __('Nothing found in the Database.'),
					'not_found_in_trash' => __('Nothing found in Trash'),
					'parent_item_colon'  => '',
				),
				'public'          => true,
				'has_archive'     => false,
				'capability_type' => 'post',
				'hierarchical'    => false,
				'menu_icon'       => 'dashicons-building',
				'supports'        => array('title', 'editor'),
			)
		);

		$labels = array(
			'name'              => _x( 'Listing Categories', 'taxonomy general name' ),
			'singular_name'     => _x( 'Listing Category', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Listing Categories' ),
			'all_items'         => __( 'All Listing Categories' ),
			'parent_item'       => __( 'Parent Listing Category' ),
			'parent_item_colon' => __( 'Parent Listing Category:' ),
			'edit_item'         => __( 'Edit Listing Category' ),
			'update_item'       => __( 'Update Listing Category' ),
			'add_new_item'      => __( 'Add New Listing Category' ),
			'new_item_name'     => __( 'New Listing Category Name' ),
			'menu_name'         => __( 'Listing Category' ),
		);

		$args = array(
			'hierarchical' => true,
			'labels'       => $labels,
			'public'       => false,
			'rewrite'      => false,
		);

		register_taxonomy( 'directories', array('listing'), $args );

		$labels = array(
			'name'              => _x( 'Listing Types', 'taxonomy general name' ),
			'singular_name'     => _x( 'Listing Type', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Listing Types' ),
			'all_items'         => __( 'All Listing Types' ),
			'parent_item'       => __( 'Parent Listing Type' ),
			'parent_item_colon' => __( 'Parent Listing Type:' ),
			'edit_item'         => __( 'Edit Listing Type' ),
			'update_item'       => __( 'Update Listing Type' ),
			'add_new_item'      => __( 'Add New Listing Type' ),
			'new_item_name'     => __( 'New Listing Type Name' ),
			'menu_name'         => __( 'Listing Type' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'public'            => false,
			'rewrite'           => false,
		);

		register_taxonomy( 'listing_type', array('listing'), $args );

		$labels = array(
			'name'              => _x( 'Listing Tags', 'taxonomy general name' ),
			'singular_name'     => _x( 'Listing Tag', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Listing Tags' ),
			'all_items'         => __( 'All Listing Tags' ),
			'parent_item'       => __( 'Parent Listing Tag' ),
			'parent_item_colon' => __( 'Parent Listing Tag:' ),
			'edit_item'         => __( 'Edit Listing Tag' ),
			'update_item'       => __( 'Update Listing Tag' ),
			'add_new_item'      => __( 'Add New Listing Tag' ),
			'new_item_name'     => __( 'New Listing Tag Name' ),
			'menu_name'         => __( 'Listing Tag' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'public'            => false,
			'rewrite'           => false,
		);

		register_taxonomy( 'listing_tag', array('listing'), $args );

		$labels = array(
			'name'              => _x( 'Listing Locations', 'taxonomy general name' ),
			'singular_name'     => _x( 'Listing Location', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Listing Locations' ),
			'all_items'         => __( 'All Listing Locations' ),
			'parent_item'       => __( 'Parent Listing Location' ),
			'parent_item_colon' => __( 'Parent Listing Location:' ),
			'edit_item'         => __( 'Edit Listing Location' ),
			'update_item'       => __( 'Update Listing Location' ),
			'add_new_item'      => __( 'Add New Listing Location' ),
			'new_item_name'     => __( 'New Listing Location Name' ),
			'menu_name'         => __( 'Listing Location' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => false,
			'public'            => false,
		);

		register_taxonomy( 'locations', array('listing'), $args );

	}

	public function init() {

		if( is_singular('listing') ) {
			add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
			remove_action( 'genesis_before_loop', 'hc_do_breadcrumbs' );
			remove_action( 'genesis_loop', 'genesis_do_loop' );
			add_action( 'genesis_loop', array($this, 'do_single_listing') );
		} else {
			if( 'page_templates/page_directory.php' === get_page_template_slug() ) {
				add_action( 'wp_enqueue_scripts', array($this, 'load_directory_assets') );
				add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
				remove_action( 'genesis_before_loop', 'hc_do_breadcrumbs' );
				remove_action( 'genesis_loop', 'genesis_do_loop' );
				add_action( 'genesis_loop', array($this, 'do_directory') );

				remove_action( 'genesis_before_footer', 'genesis_footer_widget_areas' );

				// Switch standard header to sticky one
				remove_action( 'genesis_before_header', 'hc_site_top' );

				remove_action( 'wp_footer', 'hc_sticky_header' );
				add_action( 'genesis_after_header', 'hc_sticky_header', 2 );
			}
		}

	}

	public function do_single_listing() {

		global $post;

		printf( '<article %s>', genesis_attr( 'entry' ) );
			?>
			<div class="two-fifths first">
				<?php
				genesis_entry_header_markup_open();
					genesis_do_post_title();

					$category_links = array();

					$types = wp_get_object_terms( $post->ID, 'listing_type' );
					foreach( $types as $type ) {
						if( $type->parent > 0 )
							$category_links[] = '<a href="' . get_term_link($type) . '">' . $type->name . '</a>';
					}

					$tags = wp_get_object_terms( $post->ID, 'listing_tag' );
					foreach( $tags as $tag )
						$category_links[] = '<a href="' . get_term_link($tag) . '">' . $tag->name . '</a>';

					if( !empty($category_links) )
						echo '<p class="entry-meta">' . implode( ', ', $category_links ) . '</p>';
				genesis_entry_header_markup_close();

				HC()->ratings->display( $post->ID );
				?>

				<?php
				$lines = array();

				// Address
				$map_address = get_field( '_hc_listing_address_map' );
				$address     = get_post_meta( $post->ID, '_hc_listing_address_text', true );
				if( !empty($address) ) {
					$lines['Address'] = sanitize_text_field($address);
				} else {
					if( !empty($map_address['address']) )
						$lines['Address'] = sanitize_text_field($map_address['address']);
				}

				// Hours
				$hours = get_post_meta( $post->ID, '_hc_event_hours', true );
				if( !empty($hours) )
					$lines['Hours'] = sanitize_text_field($hours);

				// Phone
				$phone = get_post_meta( $post->ID, '_hc_listing_phone', true );
				if( !empty($phone) ) {
					$lines['Contact'] = sanitize_text_field($phone);
				} else {
					// Email
					$email = get_post_meta( $post->ID, '_hc_listing_email', true );
					if( !empty($email) )
						$lines['Contact'] = '<a href="mailto:' . sanitize_email($email) . '">' . sanitize_text_field($email) . '</a>';
				}

				// Website
				$website = get_post_meta( $post->ID, '_hc_listing_website', true );
				if( !empty($website) )
					$lines['Website'] = HC()->formatting->get_linked_url( $website );

				// Good for
				$good_for = get_post_meta( $post->ID, '_hc_listing_good_for', true );
				if( !empty($good_for) )
					$lines['Good For'] = sanitize_text_field($good_for);

				if( count($lines) > 0 )
					HC()->formatting->display_data_list($lines);

				$submitter_id = get_post_meta( $post->ID, '_hc_listing_submitter_id', true );
				if( empty($submitter_id) ) {
					$claim_page_id = get_option( 'options__hc_claim_listing_page_id' );
					if( !empty($claim_page_id) ) {
						?>
						<a href="<?php echo get_permalink($claim_page_id); ?>" class="btn btn-claim">Own This Business? Claim Your Page.</a>
						<?php
					}
				}
				?>

				<div class="item-action-row">
					<?php HC()->folders->display_add_button( $post->ID ); ?>
					<?php HC()->share->display( $post->ID ); ?>
					<?php HC()->ratings->display_button( $post->ID ); ?>
				</div>

				<?php printf( '<div %s>', genesis_attr( 'entry-content' ) ); ?>
					<?php the_content(); ?>
				</div>
			</div>

			<div class="three-fifths">
				<?php
				HC()->utilities->display_basic_slider( $post->ID );

				if( !empty($map_address['address']) )
					HC()->formatting->display_map($map_address['address'], 790, 380);
				?>
			</div>

			<?php
			HC()->related->display_related_content( $post, 'aside' );
			?>
		</article>
		<?php

	}

	public function load_directory_assets() {

		$maps_url = add_query_arg(
			array(
				'callback' => 'hc_directory_maps',
				'v'        => '3.23',
				'key'      => get_field( '_hc_google_maps_api_key', 'option' ),
			),
			'//maps.googleapis.com/maps/api/js'
		);
		wp_enqueue_script( 'hc-google-maps', $maps_url, array(), null, true );

		$map = get_field( '_hc_directory_default_map_center' );
		wp_localize_script(
			'hc-google-maps',
			'hc_directory_coords',
			array(
				'lat' => round( $map['lat'], 3 ),
				'lng' => round( $map['lng'], 3 ),
			)
		);

	}

	public function ajax_get_listings() {

		$output = array();

		if( empty($_POST['type']) || !in_array( $_POST['type'], array('form', 'map'), true) ) {
			$output['status']  = 'error';
			$output['message'] = 'Search type not indicated.';
			echo json_encode($output);
			wp_die();
		}
		$type = $_POST['type'];

		$text = false;
		if( !empty($_POST['text']) )
			$text = sanitize_text_field($_POST['text']);

		$category_id = false;
		if( !empty($_POST['category_id']) )
			$category_id = absint($_POST['category_id']);

		$page = 1;
		if( !empty($_POST['page']) )
			$page = absint($_POST['page']);

		$args                           = array();
		$args['fields']                 = 'ids';
		$args['post_type']              = 'listing';
		$args['posts_per_page']         = -1;
		$args['update_post_meta_cache'] = false;
		$args['update_post_term_cache'] = false;
		$args['tax_query']              = array();
		$args['tax_query']['relation']  = 'AND';

		if( !empty($text) )
			$args['s'] = $text;

		if( !empty($category_id) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'listing_type',
				'field'    => 'term_id',
				'terms'    => $category_id,
			);
		}

		switch( $type ) {
			case 'form':
				$location_id = false;
				if( !empty($_POST['location_id']) )
					$location_id = absint($_POST['location_id']);

				if(
					empty($text) &&
					empty($location_id) &&
					empty($category_id)
				) {
					$output['status']  = 'error';
					$output['message'] = 'You must enter search text, a location, or a category';
					echo json_encode($output);
					wp_die();
				}

				if( !empty($location_id) ) {
					$args['tax_query'][] = array(
						'taxonomy' => 'locations',
						'field'    => 'term_id',
						'terms'    => $location_id,
					);
				}
				break;
			case 'map':
				$corners = array('ne', 'nw', 'se', 'sw');

				$coords = array();
				foreach( $corners as $key ) {
					if( empty($_POST[$key]) ) {
						$output['status']  = 'error';
						$output['message'] = 'Invalid coordinated';
						echo json_encode($output);
						wp_die();
					}

					$coords[$key] = array_map( 'floatval', (array) $_POST[$key] );
				}

				$args['meta_query']             = array();
				$args['meta_query']['relation'] = 'AND';

				$args['meta_query'][] = array(
					'key'     => '_hc_listing_lat',
					'value'   => array($coords['se'][0], $coords['ne'][0]),
					'compare' => 'BETWEEN',
				);

				$args['meta_query'][] = array(
					'key'     => '_hc_listing_lng',
					'value'   => array($coords['nw'][1], $coords['ne'][1]),
					'compare' => 'BETWEEN',
				);

				break;
		}

		$listings = get_posts( $args );
		if( empty($listings) ) {
			$output['status']  = 'info';
			$output['message'] = 'No listings found';
			echo json_encode($output);
			wp_die();
		}

		$listings = array_slice($listings, $this->results_per_page * ($page - 1) );

		$output['status'] = 'success';
		$output['items']  = array();
		$i                = 1;
		foreach( $listings as $listing_id ) {
			if( $i > $this->results_per_page )
				break 1;

			$map = get_field( '_hc_listing_address_map', $listing_id );
			if( empty($map['lat']) || empty($map['lng']) )
				continue;

			$categories = array();
			$terms      = wp_get_object_terms( $listing_id, 'listing_type' );
			foreach( $terms as $term )
				$categories[] = $term->name;

			$link  = get_permalink( $listing_id );
			$title = get_the_title( $listing_id );

			$info_window_html = '<a class="result-title" href="' . $link . '">' . $title . '</a>';
			$info_window_html .= '<span class="result-category">' . HC()->formatting->build_comma_separated_list( $categories ) . '</span>';

			$result_html = 1 === $i % 2 ? '<a href="' . get_permalink( $listing_id ) . '" class="listing-result first">' : '<a href="' . $link . '" class="listing-result">';
				if( has_post_thumbnail($listing_id) )
					$result_html .= '<div class="image-container">' . get_the_post_thumbnail($listing_id, 'archive-small' ) . '</div>';

				$result_html .= '<h3>' . $title . '</h3>';

				$result_html .= '<span class="more-link">Read more ></span>';
			$result_html .= '</div>';

			$output['items'][] = array(
				'id'               => $listing_id,
				'name'             => HC()->entry->get_headline_title($listing_id),
				'lat'              => round( $map['lat'], 3 ),
				'lng'              => round( $map['lng'], 3 ),
				'info_window_html' => $info_window_html,
				'result_html'      => $result_html,
			);
			++$i;
		}

		$output['total_results'] = count($listings);

		$output['load_more_settings'] = array();
		if( $output['total_results'] > $this->results_per_page ) {
			foreach( array('type', 'text', 'category_id', 'location_id') as $key ) {
				if( !empty(${$key}) )
					$output['load_more_settings'][$key] = ${$key};
			}

			if( isset($coords) )
				$output['load_more_settings'] = array_merge( $output['load_more_settings'], $coords );

			$output['load_more_settings']['page'] = $page + 1;
		}

		echo json_encode($output);
		wp_die();

	}

	public function do_directory() {

		?>
		<div class="directory-map-container">
			<div class="directory-map"></div>
		</div>

		<div class="directory-search">
			<form class="directory-search-form clearfix">
				<div class="row clearfix">
					<div class="two-fifths first">
						<h2>Search the Directory</h2>
					</div>

					<div class="three-fifths">
						<label for="directory-search-text">Search for...</label>
						<input id="directory-search-text" type="search" placeholder="Search for French restaurants...">
					</div>
				</div>

				<div class="row clearfix">
					<div class="three-fifths first">
						<div class="one-half first select-container">
							<label for="directory-location">Location</label>
							<select id="directory-location" name="location" class="styled">
								<option value="">Location</option>
								<?php
								$terms = get_terms( 'locations' );
								foreach( $terms as $term ) {
									?>
									<option value="<?php echo $term->term_id; ?>"><?php echo $term->name; ?></option>
									<?php
								}
								?>
							</select>
							<i class="ico-arrow-down"></i>
						</div>

						<div class="one-half select-container">
							<label for="directory-category">Category</label>
							<select id="directory-category" name="category" class="styled">
								<option value="">Category</option>
								<?php
								$args = array(
									'parent'   => 0,
									'taxonomy' => 'listing_type',
								);
								$terms = get_terms( $args );
								foreach( $terms as $term ) {
									?>
									<option value="<?php echo $term->term_id; ?>"><?php echo $term->name; ?></option>
									<?php
								}
								?>
							</select>
							<i class="ico-arrow-down"></i>
						</div>
					</div>

					<div class="two-fifths buttons-container">
						<button type="submit" class="submit-form">Search</button>

						<?php
						if( is_user_logged_in() ) {
							?>
							<a href="<?php echo $this->editor->get_add_url(); ?>" class="btn submit-listing">Submit A Listing</a>
							<?php
						} else {
							?>
							<button type="button" class="btn open-popup-link submit-listing" data-mfp-src="#login-popup" data-redirect="<?php echo $this->editor->get_add_url(); ?>">Submit A Listing</button>
							<?php
						}
						?>
					</div>
				</div>
			</form>

			<div class="directory-search-results clearfix"></div>
		</div>
		<?php

	}

	public function save_coords( $post_id, $post, $update ) {

		if( wp_is_post_revision( $post_id ) )
			return;

		if( 'listing' !== $post->post_type )
			return;

		$coords = get_post_meta( $post_id, '_hc_listing_address_map', true );

		if( !empty($coords['lat']) ) {
			$value = (float) $coords['lat'];
			update_post_meta( $post_id, '_hc_listing_lat', $value );
		} else {
			delete_post_meta( $post_id, '_hc_listing_lat' );
		}

		if( !empty($coords['lng']) ) {
			$value = (float) $coords['lng'];
			update_post_meta( $post_id, '_hc_listing_lng', $value );
		} else {
			delete_post_meta( $post_id, '_hc_listing_lng' );
		}

	}

}

return new HC_Listings();
