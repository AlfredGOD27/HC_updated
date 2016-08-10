<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

remove_action( 'genesis_before_loop', 'genesis_do_breadcrumbs' );
add_action( 'genesis_before_loop', 'hc_do_breadcrumbs' );
/*
 * Use WordPress SEO's breadcrumbs when available
 *
 * @since 2.3.11
 */
function hc_do_breadcrumbs() {

	if( !is_singular('post') )
		return;

	if( function_exists('yoast_breadcrumb') ) {
		yoast_breadcrumb( '<p class="breadcrumbs">', '</p>' );
	} else {
		genesis_do_breadcrumbs();
	}

}

add_filter( 'wp_seo_get_bc_title', 'hc_wp_seo_get_bc_title', 10, 2 );
function hc_wp_seo_get_bc_title( $title, $post_id ) {

	return HC()->entry->get_headline_title($post_id);

}
