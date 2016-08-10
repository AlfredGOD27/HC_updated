<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'gallery_style', 'hc_gallery_style' );
/**
 * Remove the injected styles for the [gallery] shortcode.
 *
 * @since 1.x
 */
function hc_gallery_style( $css ) {

	return preg_replace( "!<style type='text/css'>(.*?)</style>!s", '', $css );

}

/*
 * Allow pages to have excerpts.
 *
 * @since 2.2.5
 */
// add_post_type_support( 'page', 'excerpt' );

add_filter( 'the_content_more_link', 'hc_more_tag_excerpt_link' );
/**
 * Customize the excerpt text, when using the <!--more--> tag.
 *
 * See: http://my.studiopress.com/snippets/post-excerpts/
 *
 * @since 2.0.16
 */
function hc_more_tag_excerpt_link() {

	return ' <a class="more-link" href="' . get_permalink() . '">' . __( 'Read more &rarr;', CHILD_THEME_TEXT_DOMAIN ) . '</a>';

}

add_filter( 'excerpt_more', 'hc_truncated_excerpt_link' );
add_filter( 'get_the_content_more_link', 'hc_truncated_excerpt_link' );
/**
 * Customize the excerpt text, when using automatic truncation.
 *
 * See: http://my.studiopress.com/snippets/post-excerpts/
 *
 * @since 2.0.16
 */
function hc_truncated_excerpt_link() {

	return '... <a class="more-link" href="' . get_permalink() . '">' . __( 'Read more &rarr;', CHILD_THEME_TEXT_DOMAIN ) . '</a>';

}

remove_action( 'genesis_entry_footer', 'genesis_post_meta' );
add_action( 'genesis_entry_footer', 'hc_entry_footer' );
function hc_entry_footer() {

	global $post;

	if( !is_singular('post') )
		return;

	$credit = get_post_meta( $post->ID, '_hc_image_credit', true );
	if( !empty($credit) ) {
		?>
		<p class="image-credit">Image Credit: <?php echo sanitize_text_field($credit); ?></p>
		<?php
	}

	?>
	<div class="date-share-row clearfix">
		<?php echo do_shortcode('[post_date format="l j F, Y"]'); ?>

		<div class="share">
			<?php HC()->folders->display_add_button( $post->ID ); ?>
			<?php HC()->share->display( $post->ID ); ?>
		</div>
	</div>
	<?php

}

add_filter( 'genesis_prev_link_text', 'hc_prev_link_text' );
/**
 * Customize the post navigation prev text
 * (Only applies to the 'Previous/Next' Post Navigation Technique, set in Genesis > Theme Options).
 *
 * @since 2.0.0
 */
function hc_prev_link_text( $text ) {

	return html_entity_decode('&#10216;') . ' ';

}

add_filter( 'genesis_next_link_text', 'hc_next_link_text' );
/**
 * Customize the post navigation next text
 * (Only applies to the 'Previous/Next' Post Navigation Technique, set in Genesis > Theme Options).
 *
 * @since 2.0.0
 */
function hc_next_link_text( $text ) {

	return ' ' . html_entity_decode('&#10217;');

}

/*
 * Remove the post title
 *
 * @since 2.0.9
 */
// remove_action( 'genesis_entry_header', 'genesis_do_post_title' );

/*
 * Remove the post edit links (maybe you just want to use the admin bar)
 *
 * @since 2.0.9
 */
add_filter( 'edit_post_link', '__return_false' );

/*
 * Hide the author box
 *
 * @since 2.0.18
 */
// add_filter( 'get_the_author_genesis_author_box_single', '__return_false' );
// add_filter( 'get_the_author_genesis_author_box_archive', '__return_false' );

/*
 * Adjust the default WP password protected form to support keeping the input and submit on the same line
 *
 * @since 2.2.18
 */
add_filter( 'the_password_form', 'hc_password_form' );
function hc_password_form( $post = 0 ) {

	$post       = get_post( $post );
	$label      = 'pwbox-' . ( empty($post->ID) ? rand() : $post->ID );
	$output     = '<form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" class="post-password-form" method="post">';
		$autofocus = is_singular() ? 'autofocus' : '';
		$output .= '<input name="post_password" id="' . $label . '" type="password" size="20" placeholder="' . __( 'Password', CHILD_THEME_TEXT_DOMAIN ) . '" ' . $autofocus . '>';
		$output .= '<input type="submit" name="' . __( 'Submit', CHILD_THEME_TEXT_DOMAIN ) . '" value="' . esc_attr__( 'Submit' ) . '">';
	$output .= '</form>';

	return $output;

}

// add_filter( 'the_content', 'hc_highlight_non_breaking_spaces' );
/*
 * Highlight non-breaking spaces in drafts to give the author a chance to correct them
 *
 * @since 2.3.8
 */
function hc_highlight_non_breaking_spaces( $content ) {

	global $post;

	// Stop if post is published
	$unpublished_statuses = array('pending', 'draft', 'future');
	if( !in_array( $post->post_status, $unpublished_statuses, true ) )
		return $content;

	// Stop if user can't edit post
	if( !current_user_can( 'edit_post', $post->ID ) )
		return $content;

	// Highlight non-breaking spaces
	return str_replace('&nbsp;', '<mark title="' . __( 'Non-breaking space', CHILD_THEME_TEXT_DOMAIN ) . '">&nbsp;</mark>', $content);

}

add_action( 'genesis_after_loop', 'hc_entry_related' );
function hc_entry_related() {

	global $post;

	if( !is_singular('post') )
		return;

	HC()->related->display_related_content( $post, 'aside' );

}