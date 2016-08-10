<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Migration {
	public function __construct() {

		if( isset($_GET['hc_do_migration']) )
			add_action( 'admin_init', array($this, 'do_migration'), 1 );

	}

	private function setup_default_listing_types() {

		if( !current_user_can('manage_options') )
			return;

		$types = array(
			'Eat' => array(
				'Restaurant',
				'Café',
				'Eatery',
				'Hawker Centre',
			),
			'Drink' => array(
				'Bar',
				'Club',
				'Pub',
				'Bottle Shop',
			),
			'Shopping' => array(),
			'Wellness' => array(
				'Beauty Salon',
				'Clinic',
				'Gym/Fitness Studio',
				'Hair Salon',
				'Hospitals',
				'Spa',
			),
			'Hotels & Accommodation' => array(
				'Boutique',
				'Budget',
				'Hostels',
				'Luxury',
				'Serviced Apartments',
			),
			'Art & Culture' => array(
				'Art Gallery',
				'Entertainment',
				'Museum',
				'Theatre',
				'Library',
			),
		);

		foreach( $types as $parent => $children ) {
			$parent_term = get_term_by( 'name', $parent, 'listing_type' );
			if( empty($parent_term) ) {
				$result = wp_insert_term( $parent, 'listing_type' );
				if( is_wp_error($result) || empty($result) )
					continue;

				$parent_term = get_term_by( 'id', $result['term_id'], 'listing_type' );
				echo 'Added listing type: ' . $parent_term->name . '<br>';
			}

			foreach( $children as $child ) {
				$child_term = get_term_by( 'name', $child, 'listing_type' );
				if( !empty($child_term) )
					continue;

				$args = array(
					'parent' => $parent_term->term_id,
				);

				$result = wp_insert_term( $child, 'listing_type', $args );
				if( is_wp_error($result) || empty($result) )
					continue;

				$child_term = get_term_by( 'id', $result['term_id'], 'listing_type' );
				echo 'Added listing type: ' . $child_term->name . '<br>';
			}
		}

		$tags_list = array(
			'Eat' => array(
				'American (Modern)',
				'American (Traditional)',
				'Australian (Modern)',
				'Australian (Traditional)',
				'Austrian',
				'Bakery',
				'BBQ',
				'Belgian',
				'Bistro',
				'Brazilian',
				'Caribbean',
				'Chinese',
				'Eastern European',
				'Eclectic/Global',
				'English',
				'French',
				'Gastropub',
				'German',
				'Greek',
				'Halal',
				'Hawaiian',
				'High Tea',
				'Indian',
				'International',
				'Irish',
				'Italian',
				'Japanese',
				'Juice',
				'Korean',
				'Latin American',
				'Mexican',
				'Middle Eastern',
				'Molecular Gastronomy',
				'Portuguese',
				'Russian',
				'Scandinavian',
				'Seafood',
				'Singaporean',
				'South American',
				'Southeast Asian',
				'Spanish',
				'Steakhouse',
				'Sweets',
				'Thai',
				'Turkish',
				'Vegetarian/Vegan',
				'Vietnamese',
			),
			'Drink' => array(
				'Beer',
				'Cocktail',
				'Gin',
				'Irish',
				'Live Music',
				'Rooftop',
				'Rum',
				'Sake/Soju',
				'Sports',
				'Waterfront',
				'Whisky',
				'Wine',
			),
			'Shopping' => array(
				'Accessories',
				'Beauty',
				'Bridal',
				'Books',
				'Department Store',
				'Electronics',
				'Entertainment',
				'Fair/Flea Market',
				'Fashion',
				'Food',
				'Gifts and Parties',
				'Home & Décor',
				'Kids',
				'Lingerie',
				'Mall',
				'Outlet',
				'Pets',
				'Sporting Goods',
				'Swimwear',
				'Tailoring & Alterations',
				'Thrift',
			),
		);

		foreach( $tags_list as $type => $tags ) {
			$parent_type = get_term_by( 'name', $type, 'listing_type' );
			if( empty($parent_type) )
				continue;

			foreach( $tags as $tag ) {
				$term = get_term_by( 'name', $tag, 'listing_tag' );
				if( !empty($term) )
					continue;

				$result = wp_insert_term( $tag, 'listing_tag' );
				if( is_wp_error($result) || empty($result) )
					continue;

				$term = get_term_by( 'id', $result['term_id'], 'listing_tag' );
				update_field('_hc_listing_type_id', $parent_type->term_id, $term);
				echo 'Added listing tag: ' . $term->name . '<br>';
			}
		}

	}

	private function migrate_listing_categories() {

		$mappings = array(
			'listing_type' => array(
				'art-2'              => 'art-gallery',
				'breakfast-brunch'   => 'eatery',
				'brunch-3'           => 'eatery',
				'cafes-3'            => 'cafe',
				'city-bars'          => 'bar',
				'date-night-eat'     => 'restaurant',
				'drinks-3'           => 'drink',
				'eat-3'              => 'eat',
				'fashion'            => 'shopping',
				'grocers'            => 'shopping',
				'hair-3'             => 'hair-salon',
				'health-fitness'     => 'gymfitness-studio',
				'hot-new-tables'     => 'restaurant',
				'inspire'            => 'art-culture',
				'lunch-venues'       => 'eatery',
				'makeup-2'           => 'beauty-salon',
				'medical'            => 'hospitals',
				'menswear-fashion'   => 'shopping',
				'online-store'       => 'shopping',
				'nails-3'            => 'beauty-salon',
				'pubs-2'             => 'pub',
				'retail-outlet'      => 'shopping',
				'shops'              => 'shopping',
				'spas'               => 'spa',
				'suburban-bars'      => 'bar',
				'treatments'         => 'clinic',
				'vintage-3'          => 'shopping',
				'wellness-directory' => 'wellness',
				'wine-bars'          => 'bar',
			),
			'listing_tag' => array(
				'accessories-3'   => 'accessories',
				'activities-3'    => 'kids',
				'bakeries'        => 'bakery',
				'birthdays'       => 'kids',
				'chinese'         => 'chinese',
				'education-3'     => 'kids',
				'entertainment-3' => 'entertainment',
				'fairs-fleas'     => 'fairflea-market',
				'french-2'        => 'french',
				'gifts-fashion'   => 'gifts-and-parties',
				'international'   => 'international',
				'italian-3'       => 'italian',
				'japanese-3'      => 'japanese',
				'kid-friendly'    => 'kids',
				'kids-directory'  => 'kids',
				'kids-fashion'    => 'kids',
				'korean'          => 'korean',
				'mexican-eat'     => 'mexican',
				'parenting-3'     => 'kids',
				'pet-friendly'    => 'pets',
				'spanish-2'       => 'spanish',
				'sports'          => 'sports',
				'sweets'          => 'sweets',
				'vegetarian'      => 'vegetarianvegan',
			),
		);

		foreach( $mappings as $taxonomy => $map ) {
			foreach( $map as $category => $type ) {
				$term = get_term_by( 'slug', $type, $taxonomy );
				if( empty($term) || is_wp_error($term) ) {
					echo "Listing type doesn't exist: " . $type . '<br>';
					continue;
				}

				$args = array(
					'posts_per_page' => -1,
					'post_type'      => 'listing',
					'tax_query'      => array(
						array(
							'taxonomy' => 'directories',
							'field'    => 'slug',
							'terms'    => $category,
						),
					),
					'fields' => 'ids',
				);

				$listing_ids = get_posts( $args );
				if( empty($listing_ids) )
					continue;

				echo 'Migrating listing category ' . $category . ' to ' . $taxonomy . ' ' . $type . '<br>';

				foreach( $listing_ids as $listing_id ) {
					if( has_term($term, $term->taxonomy, $listing_id) )
						continue;

					wp_set_object_terms( $listing_id, (int) $term->term_id, $term->taxonomy, true );
				}
			}
		}

	}

	public function do_migration() {

		global $wpdb;

		if( !current_user_can('manage_options') )
			return;

		// Delete old meta_keys
		$keys = array(
		);

		foreach( $keys as $key ) {
			$wpdb->query(
				$wpdb->prepare(
					"
					DELETE FROM $wpdb->postmeta
					WHERE meta_key LIKE %s
					",
					$key
				)
			);

			echo 'Deleted ' . $key . '<br>';
		}

		// Delete option keys
		$keys = array(
			'_site_transient_%',
			'_transient_%',
		);

		foreach( $keys as $key ) {
			$wpdb->query(
				$wpdb->prepare(
					"
					DELETE FROM $wpdb->options
					WHERE option_name LIKE %s
					",
					$key
				)
			);

			echo 'Deleted ' . $key . '<br>';
		}

		// Delete user keys
		$keys = array(
		);

		foreach( $keys as $key ) {
			$wpdb->query(
				$wpdb->prepare(
					"
					DELETE FROM $wpdb->usermeta
					WHERE meta_key LIKE %s
					",
					$key
				)
			);

			echo 'Deleted ' . $key . '<br>';
		}

		// Update user keys
		$keys = array(
			'wp_user_avatar' => HC()->users->get_ms_user_image_key(),
		);

		foreach( $keys as $from => $to ) {
			$wpdb->update(
				$wpdb->usermeta,
				array(
					'meta_key' => $to,
				),
				array('meta_key' => $from),
				array('%s'),
				array('%s')
			);

			echo 'Updated ' . $from . ' > ' . $to . '<br>';
		}

		// Update meta_keys
		$keys = array(
			'where_website'         => '_hc_listing_website',
			'where_phone'           => '_hc_listing_phone',
			'where_email'           => '_hc_listing_email',
			'where_address'         => '_hc_listing_address_text',
			'entry_location_map'    => '_hc_listing_address_map',
			'entry_headlinetitle_1' => '_hc_headline_title',
			'entry_subtitle_1'      => '_hc_subtitle',
			'entry_event_cost_2'    => '_hc_event_price',
			'entry_event_email'     => '_hc_event_contact',
			'entry_event_location'  => '_hc_event_venue',
			'entry_end_date'        => '_hc_event_end_date',
			'entry_start_date_2'    => '_hc_event_start_date',
			'entry_website'         => '_hc_event_website',
		);

		foreach( $keys as $from => $to ) {
			$wpdb->update(
				$wpdb->postmeta,
				array(
					'meta_key' => $to,
				),
				array('meta_key' => $from),
				array('%s'),
				array('%s')
			);

			echo 'Updated ' . $from . ' > ' . $to . '<br>';
		}

		// Migrate directory post type to listings
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_type' => 'listing',
			),
			array(
				'post_type' => 'directory',
			)
		);

		$event_category_ids   = array();
		$event_category       = get_term_by( 'slug', 'whatson-events', 'category' );
		$event_category_ids[] = $event_category->term_id;

		$args = array(
			'parent'   => $event_category->term_id,
			'taxonomy' => 'category',
		);
		$event_subcategory_ids = get_terms( $args );

		foreach( $event_subcategory_ids as $cat )
			$event_category_ids[] = $cat->term_id;

		$args = array(
			'posts_per_page' => -1,
			'post_type'      => 'post',
			'tax_query'      => array(
				array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => $event_category_ids,
				),
			),
			'fields' => 'ids',
		);
		$event_post_ids = get_posts( $args );

		foreach( $event_post_ids as $post_id ) {
			$categories = wp_get_object_terms( $post_id, 'category' );
			$term_ids   = array();
			foreach( $categories as $category ) {
				$event_category = get_term_by( 'name', $category->name, 'event-category' );
				if( empty($event_category) ) {
					$result = wp_insert_term( $category->name, 'event-category' );
					if( !empty($result) && !is_wp_error($result) )
						$term_ids[] = $result['term_id'];
				} else {
					$term_ids[] = $event_category->term_id;
				}
			}

			$wpdb->update(
				$wpdb->posts,
				array(
					'post_type' => 'event',
				),
				array(
					'ID' => $post_id,
				)
			);

			wp_set_object_terms( $post_id, $term_ids, 'event-category' );
			echo 'Migrating post #' . $post_id . ' to event<br>';
		}

		// Migrate listing taxonomies
		$this->setup_default_listing_types();
		$this->migrate_listing_categories();

		// Remove empty post tags
		$tags = get_terms(
			'post_tag',
			array(
				'orderby'    => 'count',
				'hide_empty' => 0,
				'order'      => 'ASC',
			)
		);

		foreach( $tags as $tag ) {
			if( $tag->count > 5 )
				continue;

			wp_delete_term( $tag->term_id, 'post_tag' );
			echo 'Deleting tag #' . $tag->term_id . ' <br>';
		}

		// Update coordinates
		$args = array(
			'posts_per_page' => -1,
			'post_type'      => 'listing',
			'fields'         => 'ids',
		);
		$listings = get_posts( $args );

		foreach( $listings as $listing_id ) {
			$lat = get_post_meta( $listing_id, '_hc_listing_lat', true );
			if( !empty($lat) )
				continue;

			$coords = get_post_meta( $listing_id, '_hc_listing_address_map', true );
			if( !empty($coords['lat']) ) {
				$value = (float) $coords['lat'];
				update_post_meta( $listing_id, '_hc_listing_lat', $value );
			}

			if( !empty($coords['lng']) ) {
				$value = (float) $coords['lng'];
				update_post_meta( $listing_id, '_hc_listing_lng', $value );
			}

			echo 'Set coordinates for ' . $listing_id . '<br>';
		}

		exit;

	}
}

return new HC_Migration();
