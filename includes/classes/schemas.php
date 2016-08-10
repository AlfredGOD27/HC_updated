<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Schemas {
	public function __construct() {

		add_filter( 'genesis_attr_search-form', array($this, 'unset_role_attribute') );
		add_filter( 'genesis_attr_sidebar-primary', array($this, 'unset_role_attribute') );

		add_filter( 'genesis_attr_entry', array($this, 'entry_atts') );
		add_filter( 'genesis_attr_entry-title', array($this, 'entry_title_atts') );
		add_filter( 'genesis_attr_entry-content', array($this, 'entry_content_atts') );

	}

	public function unset_role_attribute( $attributes ) {

		if( isset($attributes['role']) )
			unset($attributes['role']);

		return $attributes;

	}

	public function empty_genesis_schema_atts( $attr ) {

		$attr['itemtype']  = '';
		$attr['itemprop']  = '';
		$attr['itemscope'] = '';

		return $attr;

	}

	public function entry_atts( $attr ) {

		global $post;

		if( 'event' === $post->post_type ) {
			$attr['itemtype']  = 'http://schema.org/Event';
			$attr['itemprop']  = '';
			$attr['itemscope'] = 'itemscope';
		}

		return $attr;

	}

	public function entry_title_atts( $attr ) {

		global $post;

		if( 'event' === $post->post_type ) {
			$attr['itemtype']  = '';
			$attr['itemprop']  = 'name';
			$attr['itemscope'] = '';
		}

		return $attr;

	}

	public function entry_content_atts( $attr ) {

		global $post;

		if( 'event' === $post->post_type ) {
			$attr['itemtype']  = '';
			$attr['itemprop']  = 'description';
			$attr['itemscope'] = '';
		}

		return $attr;

	}

}

return new HC_Schemas();
