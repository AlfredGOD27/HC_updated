<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Listing_Editor extends HC_Form_Abstract {
	public function __construct( $editor, $action, $item_id = false ) {

		$this->post_type           = 'listing';
		$this->level               = $this->get_user_level();
		$this->action              = 'add';
		$this->default_post_status = 'pending';
		$this->editor              = $editor;

		parent::__construct();

	}

	public function get_user_level() {

		return 'free';

	}

	public function setup_fields() {

		$this->fields = array();

		$this->fields[] = array(
			'slug'     => 'post_title',
			'label'    => 'Venue Name',
			'type'     => 'text',
			'table'    => 'posts',
			'required' => true,
			'classes'  => array('first', 'one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_listing_location',
			'label'    => 'Locations',
			'type'     => 'term_list',
			'table'    => 'postmeta',
			'taxonomy' => 'locations',
			'multiple' => true,
			'required' => false,
			'classes'  => array('one-half'),
		);

		$this->fields[] = array(
			'slug'        => '_hc_listing_type',
			'label'       => 'Venue Type',
			'type'        => 'term_list',
			'table'       => 'postmeta',
			'taxonomy'    => 'listing_type',
			'multiple'    => true,
			'required'    => true,
			'description' => 'Can\'t find a suitable category or venue type? Keen to take your listing to the next level? Get in touch at <a href="mailto:hello@thehoneycombers.com" target="_blank">hello@thehoneycombers.com</a>.',
			'classes'     => array('first', 'one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_listing_tag',
			'label'    => 'Specialties and Features',
			'type'     => 'term_list',
			'table'    => 'postmeta',
			'taxonomy' => 'listing_tag',
			'multiple' => true,
			'required' => false,
			'classes'  => array('one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_listing_address_text',
			'label'    => 'Address',
			'type'     => 'text',
			'table'    => 'postmeta',
			'required' => true,
			'classes'  => array('first'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_listing_phone',
			'label'    => 'Phone',
			'type'     => 'text',
			'table'    => 'postmeta',
			'required' => false,
			'classes'  => array('first', 'one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_listing_email',
			'label'    => 'Email',
			'type'     => 'email',
			'table'    => 'postmeta',
			'required' => false,
			'classes'  => array('one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_listing_website',
			'label'    => 'Website',
			'type'     => 'url',
			'table'    => 'postmeta',
			'required' => false,
			'classes'  => array('first', 'one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_listing_good_for',
			'label'    => 'Good For',
			'type'     => 'text',
			'table'    => 'postmeta',
			'required' => false,
			'classes'  => array('one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_listing_hours',
			'label'    => 'Hours',
			'type'     => 'text',
			'table'    => 'postmeta',
			'required' => false,
			'classes'  => array('one-half', 'first'),
		);

		$this->fields[] = array(
			'slug'        => 'post_content',
			'label'       => 'Venue Description',
			'type'        => 'textarea',
			'table'       => 'posts',
			'word_limit'  => 100,
			'required'    => true,
			'description' => 'Word limit: 100',
			'classes'     => array('first', 'one-half'),
		);

		$this->fields[] = array(
			'slug'               => '_thumbnail_id',
			'label'              => 'Venue Photo',
			'type'               => 'file',
			'table'              => 'postmeta',
			'required'           => true,
			'description'        => 'Optimal image size: 850 (w) x 400 (h); max 1MB',
			'allowed_mime_types' => array('image/jpg', 'image/jpeg'),
			'max_size'           => 1,
			'preview_type'       => 'image',
			'preview_image_size' => 'archive-small',
			'classes'            => array('one-half', 'block-image', 'first'),
		);

	}

	protected function set_nonce_key() {

		switch( $this->action ) {
			case 'add':
				$this->nonce_key = 'add_listing_' . get_current_user_id();
				break;
		}

	}

	public function pre_add() {

	}

	protected function subtract_point() {

	}

	protected function do_after_save() {

		// Save level
		update_post_meta( $this->post_id, '_hc_' . $this->post_type . '_level', $this->level );

		update_post_meta( $this->post_id, '_hc_event_submitter_id', get_current_user_id() );

		// Remove point
		// $this->subtract_point();

		// Redirect
		$url = add_query_arg(
			array(
				'listing_added' => true,
			),
			HC()->profiles->get_url()
		);

		wp_redirect( $url );
		exit;

	}

}
