<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Folder_Editor extends HC_Form_Abstract {
	public function __construct( $editor, $action, $folder_id = false ) {

		$this->post_type    = 'folder';
		$this->allow_delete = true;

		$this->action = $action;
		switch( $this->action ) {
			case 'add':
				break;
			case 'edit':
				$this->post_id     = $folder_id;
				$this->post_object = get_post( $folder_id );
				break;
		}

		$this->default_post_status = 'publish';
		$this->editor              = $editor;

		parent::__construct();

	}

	public function setup_fields() {

		$this->fields = array();

		$this->fields[] = array(
			'slug'     => 'post_title',
			'label'    => 'Name',
			'type'     => 'text',
			'table'    => 'posts',
			'required' => true,
			'classes'  => array('first', 'one-half'),
		);
		$this->fields[] = array(
			'slug'     => '_hc_folder_is_public',
			'label'    => 'Public?',
			'type'     => 'radio',
			'table'    => 'postmeta',
			'required' => true,
			'options'  => array(
				'Yes',
				'No',
			),
			'classes' => array('one-half'),
			'default' => 'No',
		);
		$this->fields[] = array(
			'slug'     => 'post_content',
			'label'    => 'Description',
			'type'     => 'textarea',
			'table'    => 'posts',
			'required' => false,
			'classes'  => array('first', 'one-half'),
		);

		if( current_user_can('manage_options') ) {
			$this->fields[] = array(
				'slug'     => '_hc_folder_is_curated',
				'label'    => 'Curated?',
				'type'     => 'radio',
				'table'    => 'postmeta',
				'required' => true,
				'options'  => array(
					'Yes',
					'No',
				),
				'classes' => array('one-half'),
				'default' => 'No',
			);
		}

		$this->fields[] = array(
			'slug'     => '_hc_folder_item_ids',
			'label'    => 'Items',
			'type'     => 'posts_list',
			'table'    => 'postmeta',
			'required' => false,
			'classes'  => array('first'),
		);

	}

	protected function set_nonce_key() {

		switch( $this->action ) {
			case 'add':
				$this->nonce_key = 'add_folder_' . get_current_user_id();
				break;
			case 'edit':
				$this->nonce_key = 'edit_folder_' . $this->post_object->ID . '_' . get_current_user_id();
				break;
		}

	}

	protected function do_after_save() {

		if( isset($_GET['add_post_id']) ) {
			$item_id = absint($_GET['add_post_id']);
			if( HC()->folders->item_can_be_bookmarked( $item_id ) )
				HC()->folders->add_item_to_folder( $item_id, $this->post_id );
		}

		$url = get_permalink( $this->post_id );
		$url = add_query_arg(
			array(
				$this->action => true,
			),
			$url
		);

		wp_redirect( $url );
		exit;

	}

	protected function do_after_delete() {

		$url = add_query_arg(
			array(
				'deleted' => true,
			),
			HC()->profiles->get_url()
		);

		wp_redirect( $url );
		exit;

	}

}
