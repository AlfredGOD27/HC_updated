<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Post_Type_Editor {
	public function __construct( $post_type, $supported_actions, $editor_class_name ) {

		$this->post_type         = $post_type;
		$this->editor_class_name = $editor_class_name;
		$this->supported_actions = $supported_actions;

		add_action( 'init', array($this, 'rewrites'), 12 );
		add_action( 'wp', array($this, 'init') );

	}

	// http://wordpress.stackexchange.com/questions/26388/how-to-create-custom-url-routes
	public function rewrites() {

		$this->post_type_object = get_post_type_object( $this->post_type );

		$page_id = get_option( 'page_on_front' );

		add_rewrite_tag( '%hc_' . $this->post_type . '_slug%', '([^&]+)' );
		add_rewrite_tag( '%hc_' . $this->post_type . '_action%', '([^&]+)' );

		add_rewrite_rule(
			'^' . $this->post_type_object->rewrite['slug'] . '/new/?$',
			'index.php?p=' . $page_id . '&&hc_' . $this->post_type . '_action=add',
			'top'
		);

		add_rewrite_rule(
			'^' . $this->post_type_object->rewrite['slug'] . '/([^/]+)/edit/?$',
			'index.php?p=' . $page_id . '&hc_' . $this->post_type . '_slug=$matches[1]&hc_' . $this->post_type . '_action=edit',
			'top'
		);

	}

	public function init() {

		global $wp_query;

		$this->action = get_query_var( 'hc_' . $this->post_type . '_action' );
		if( empty($this->action) || !in_array($this->action, $this->supported_actions, true ) )
			return;

		if( !is_user_logged_in() )
			return;

		$this->user_id          = get_current_user_id();
		$this->user             = get_user_by( 'id', $this->user_id );
		HC()->profiles->user_id = $this->user_id;
		HC()->profiles->user    = $this->user;

		$valid = false;

		switch( $this->action ) {
			case 'add':
				$this->form = new $this->editor_class_name( $this, 'add' );
				$valid      = true;

				if( method_exists($this->form, 'pre_add') )
					$this->form->pre_add();
				break;
			case 'edit':
				$this->slug = get_query_var( 'hc_' . $this->post_type . '_slug' );
				if( empty($this->slug) )
					return;

				$args = array(
					'posts_per_page' => 1,
					'post_type'      => $this->post_type,
					'name'           => sanitize_title($this->slug),
					'fields'         => 'ids',
				);

				if( !current_user_can('manage_options') )
					$args['author'] = get_current_user_id();

				$items = get_posts( $args );
				if( 0 === count($items) )
					return;

				$this->form = new $this->editor_class_name( $this, 'edit', $items[0] );
				$valid      = true;
				$this->item = $items[0];
				break;
		}

		if( $valid ) {
			$wp_query->is_404 = false;
			status_header(200);

			add_action( 'wp_enqueue_scripts', array(HC()->profiles, 'load_assets') );
			add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
			add_filter( 'body_class', array(HC()->profiles, 'body_classes') );
			add_action( 'genesis_loop', array(HC()->profiles, 'display_heading') );
			add_action( 'genesis_loop', array(HC()->messages, 'display') );
			add_action( 'genesis_loop', array($this->form, 'display_form') );
			add_action( 'template_include', array($this, 'do_seo') );
			remove_action( 'genesis_loop', 'genesis_do_loop' );
		}

	}

	public function get_add_url() {

		if( !in_array('add', $this->supported_actions, true) )
			return;

		$url = get_bloginfo('url');
		$url = trailingslashit($url);
		$url .= $this->post_type_object->rewrite['slug'] . '/';
		$url .= 'new/';

		return $url;

	}

	public function get_edit_url( $item_id ) {

		if( !in_array('edit', $this->supported_actions, true) )
			return;

		$url = get_permalink( $item_id );
		$url = trailingslashit($url) . 'edit/';

		return $url;

	}

	public function do_seo() {

		// If WordPress SEO is installed, overwrite everything. Otherwise, just replace the <title>
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if( is_plugin_active('wordpress-seo/wp-seo.php') || is_plugin_active('wordpress-seo-premium/wp-seo-premium.php') ) {
			add_action( 'wpseo_robots', array($this, 'noindex') );
			add_filter( 'wpseo_canonical', array($this, 'seo_canonical') );
			add_filter( 'wpseo_title', array($this, 'seo_title') );
		} else {
			add_filter( 'wp_title', array($this, 'seo_title') );
		}

		return get_query_template( 'index' );

	}

	public function noindex() {

		return 'noindex,nofollow';

	}

	public function seo_canonical( $canonical ) {

		switch( $this->action ) {
			case 'add':
				$canonical = $this->get_add_url();
				break;
			case 'edit':
				$canonical = $this->get_edit_url( $this->item->ID );
				break;
		}

		return $canonical;

	}

	public function seo_title( $title ) {

		$titles = get_option( 'wpseo_titles' );

		switch( $this->action ) {
			case 'add':
				$title = str_replace( '%%title%%', 'Add New ' . $this->post_type_object->labels->singular_name, $titles['title-folder'] );
				break;
			case 'edit':
				$title = str_replace( '%%title%%', 'Edit ' . $this->post_type_object->labels->singular_name, $titles['title-folder'] );
				break;
		}

		return wpseo_replace_vars( $title, array() );

	}

}
