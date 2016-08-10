<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'genesis_search_text', 'hc_search_text' );
/**
 * Customize the search form input box text.
 *
 * See: http://www.briangardner.com/code/customize-search-form/
 *
 * @since 2.0.0
 */
function hc_search_text() {

	$time = current_time('timestamp');
	$d    = date( 'l', $time );
	$d    = strtolower($d);

	$h = date( 'G', $time );
	$h = absint($h);

	if( $h >= 6 && $h < 12 ) {
		$cycle = 'morning';
	} elseif( $h >= 12 && $h < 18 ) {
		$cycle = 'afternoon';
	} elseif( $h >= 18 || $h < 6 ) {
		$cycle = 'evening';
	}

	$placeholders = get_field( '_hc_placeholders_' . $d, 'option' );

	return !empty($placeholders[0][$cycle]) ? sanitize_text_field($placeholders[0][$cycle]) : '';

}

// add_filter( 'genesis_search_button_text', 'hc_search_button_text' );
/**
 * Customize the search form input button text.
 *
 * See: http://www.briangardner.com/code/customize-search-form/
 *
 * @since 2.0.0
 */
function hc_search_button_text( $text ) {

	return esc_attr( __( 'Click Here...', CHILD_THEME_TEXT_DOMAIN ) );

}

// add_action( 'template_redirect', 'hc_redirect_single_search_result' );
/**
 * Redirect to the result itself, if only one search result is returned.
 *
 * See: http://www.developerdrive.com/2013/07/5-quick-and-easy-tricks-to-improve-your-wordpress-theme/
 *
 * @since 2.0.5
 */
function hc_redirect_single_search_result() {

	if( is_search() ) {
		global $wp_query;

		if( $wp_query->post_count === 1) {
			wp_redirect( get_permalink( $wp_query->posts['0']->ID ) );
		}
	}

}

add_filter( 'pre_get_posts', 'hc_only_search_posts' );
/**
 * Limit searching to just posts, excluding pages and CPTs.
 *
 * See: http://www.mhsiung.com/2009/11/limit-wordpress-search-scope-to-blog-posts/
 *
 * @since 2.0.18
 */
function hc_only_search_posts( $query ) {

	if( $query->is_search && !(defined( 'DOING_AJAX' ) && DOING_AJAX) ) {
		$query->set( 'post_type', array('post', 'page', 'event', 'listing') );
	}

	return $query;

}

remove_filter( 'get_search_form', 'genesis_search_form' );
add_filter( 'get_search_form', 'hv_search_form' );
function hv_search_form() {

	$search_text = get_search_query() ? apply_filters( 'the_search_query', get_search_query() ) : apply_filters( 'genesis_search_text', __( 'Search this website', 'genesis' ) . ' &#x02026;' );

	$button_text = apply_filters( 'genesis_search_button_text', esc_attr__( 'Search', 'genesis' ) );

	$value_or_placeholder = ( get_search_query() === '' ) ? 'placeholder' : 'value';

	$label = apply_filters( 'genesis_search_form_label', '' );
	if( empty($label) )
		$label = apply_filters( 'genesis_search_text', __( 'Search this website', 'genesis' ) );

	$form_id = uniqid( 'searchform-' );

	$form = sprintf( '<form %s>', genesis_attr( 'search-form' ) );

	$form .= sprintf(
		'<meta itemprop="target" content="%s"><label class="search-form-label screen-reader-text" for="%s">%s</label><i class="ico-search"></i><input itemprop="query-input" type="search" name="s" id="%s" %s="%s" /><input type="submit" value="%s" /></form>',
		home_url( '/?s={s}' ),
		esc_attr( $form_id ),
		esc_html( $label ),
		esc_attr( $form_id ),
		$value_or_placeholder,
		esc_attr( $search_text ),
		esc_attr( $button_text )
	);

	return apply_filters( 'genesis_search_form', $form, $search_text, $button_text, $label );

}
