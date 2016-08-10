<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Event_Editor extends HC_Form_Abstract {
	public function __construct( $editor, $action, $item_id = false ) {

		$this->post_type           = 'event';
		$this->level               = $this->get_user_level();
		$this->action              = 'add';
		$this->default_post_status = 'pending';
		$this->editor              = $editor;

		parent::__construct();

	}

	public function get_user_level() {

		$user_id = get_current_user_id();

		$premium_credits = get_user_meta( $user_id, '_hc_' . $this->post_type . '_credits_premium', true );
		if( !empty($premium_credits) )
			return 'premium';

		$upgrade_credits = get_user_meta( $user_id, '_hc_' . $this->post_type . '_credits_upgrade', true );
		if( !empty($upgrade_credits) )
			return 'upgrade';

		return 'free';

	}

	public function setup_fields() {

		$this->fields = array();

		$this->fields[] = array(
			'slug'     => 'post_title',
			'label'    => 'Full Event Name',
			'type'     => 'text',
			'table'    => 'posts',
			'required' => true,
			'classes'  => array('first', 'one-half'),
		);

		$this->fields[] = array(
			'slug'        => '_hc_headline_title',
			'label'       => 'Alternative Event Name',
			'type'        => 'text',
			'table'       => 'postmeta',
			'maxlength'   => 45,
			'required'    => true,
			'description' => "This title will appear on the Home Page and the Calendar's main page. Character limit: 45",
			'classes'     => array('one-half'),
		);

		$description_word_limit = 50;
		$gallery_images         = 1;
		switch( $this->level ) {
			case 'upgrade':
				$description_word_limit = 300;
				$gallery_images         = 5;
				break;
			case 'premium':
				$description_word_limit = 0;
				$gallery_images         = 10;
				break;
		}

		$this->fields[] = array(
			'slug'        => 'post_content',
			'label'       => 'Event Description',
			'type'        => 'textarea',
			'table'       => 'posts',
			'word_limit'  => $description_word_limit,
			'required'    => true,
			'description' => 'Word limit: ' . $description_word_limit,
			'classes'     => array('first', 'one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_event_start_date',
			'label'    => 'Start Date',
			'type'     => 'date',
			'table'    => 'postmeta',
			'required' => true,
			'classes'  => array('first', 'one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_event_start_time',
			'label'    => 'Start Time',
			'type'     => 'text',
			'table'    => 'postmeta',
			'required' => false,
			'classes'  => array('one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_event_end_date',
			'label'    => 'End Date',
			'type'     => 'date',
			'table'    => 'postmeta',
			'required' => true,
			'classes'  => array('first', 'one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_event_end_time',
			'label'    => 'End Time',
			'type'     => 'text',
			'table'    => 'postmeta',
			'required' => false,
			'classes'  => array('one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_event_all_day',
			'label'    => 'Ongoing?',
			'type'     => 'boolean',
			'table'    => 'postmeta',
			'required' => false,
			'classes'  => array('one-half', 'first'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_event_venue',
			'label'    => 'Location',
			'type'     => 'text',
			'table'    => 'postmeta',
			'required' => true,
			'classes'  => array('one-half', 'first'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_event_price',
			'label'    => 'Price',
			'type'     => 'number',
			'table'    => 'postmeta',
			'required' => false,
			'min'      => 0,
			'step'     => .01,
			'classes'  => array('one-half'),
		);

		$this->fields[] = array(
			'slug'        => '_hc_event_contact',
			'label'       => 'Contact Email',
			'type'        => 'email',
			'table'       => 'postmeta',
			'required'    => false,
			'description' => "Contact information will only be available to the site's admin.",
			'classes'     => array('one-half', 'first'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_event_website',
			'label'    => 'Website',
			'type'     => 'url',
			'table'    => 'postmeta',
			'required' => false,
			'classes'  => array('one-half'),
		);

		$this->fields[] = array(
			'slug'     => '_hc_event_category',
			'label'    => 'Event Category',
			'type'     => 'term_list',
			'table'    => 'postmeta',
			'required' => true,
			'taxonomy' => 'event-category',
			'classes'  => array('one-half', 'first'),
		);

		$this->fields[] = array(
			'slug'               => '_thumbnail_id',
			'label'              => 'Event Photo',
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

		if( $gallery_images > 1 ) {
			$this->fields[] = array(
				'slug'               => '_hc_gallery_image_ids',
				'label'              => 'Additional Images',
				'type'               => 'file',
				'table'              => 'postmeta',
				'required'           => false,
				'multiple'           => true,
				'max_files'          => $gallery_images - 1,
				'allowed_mime_types' => array('image/jpg', 'image/jpeg'),
				'max_size'           => 1,
				'preview_type'       => 'image',
				'preview_image_size' => 'archive-small',
				'classes'            => array('one-half', 'block-image', 'first'),
				'description'        => 'You may upload up to ' . ($gallery_images - 1) . ' additional images.',
			);
		}

	}

	protected function set_nonce_key() {

		switch( $this->action ) {
			case 'add':
				$this->nonce_key = 'add_event_' . get_current_user_id();
				break;
		}

	}

	public function pre_add() {

		if( !isset($_GET['level']) )
			return;

		if( !in_array( $_GET['level'], array('free', 'upgrade', 'premium'), true ) )
			return;

		$form_level = $_GET['level'];
		if( $form_level === $this->level )
			return;

		switch( $this->level ) {
			case 'free':
				$purchase_page_id = get_option( 'options__hc_purchase_credits_page_id' );
				$url              = add_query_arg(
					array(
						'purchase_type' => 'event-' . $form_level,
					),
					get_permalink($purchase_page_id)
				);

				wp_redirect($url);
				exit;

				break;
			case 'upgrade':
			case 'premium':
				switch( $form_level ) {
					case 'free':
						HC()->messages->add( 'info', 'You indicated that you want to post a free event, but you still have ' . $this->level . '-level credits to use first.' );
						break;
					case 'upgrade':
						HC()->messages->add( 'info', 'You indicated that you want to post an upgraded event, but you still have ' . $this->level . '-level credits to use first.' );
						break;
					case 'premium':
						HC()->messages->add( 'info', 'You indicated that you want to post a premium event, but you still have ' . $this->level . '-level credits to use first.' );
						break;
				}
				break;
		}

	}

	protected function subtract_point() {

		if( 'free' === $this->level )
			return;

		$user_id = get_current_user_id();
		$key     = '_hc_' . $this->post_type . '_credits_' . $this->level;
		$points  = get_user_meta( $user_id, $key, true );
		--$points;
		update_user_meta( $user_id, $key, $points );

		$data = array(
			'post_type'          => $this->post_type,
			'level'              => $this->level,
			'target_user_id'     => $user_id,
			'initiating_user_id' => $user_id,
			'ref_id'             => $this->post_id,
			'amount'             => -1,
		);

		HC()->logs->add( $data );

	}

	protected function do_after_save() {

		// Save level
		update_post_meta( $this->post_id, '_hc_' . $this->post_type . '_level', $this->level );

		update_post_meta( $this->post_id, '_hc_event_submitter_id', get_current_user_id() );

		// Remove point
		$this->subtract_point();

		// Redirect
		$url = add_query_arg(
			array(
				'event_added' => true,
			),
			HC()->profiles->get_url()
		);

		wp_redirect( $url );
		exit;

	}

}
