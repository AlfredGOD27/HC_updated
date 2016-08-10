<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'admin_bar_menu', 'hc_clear_transients_node', 99 );
/**
 * Clear all transients with one click.
 *
 * @since 2.2.9
 */
function hc_clear_transients_node( $wp_admin_bar ) {

	if( !is_admin() || !current_user_can('manage_options') )
		return;

	global $wpdb;

	if( isset($_GET['clear-transients']) && 1 === (int) $_GET['clear-transients'] ) {
		$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_%') OR `option_name` LIKE ('_transient_timeout_%')" );
		wp_cache_flush();

		$wpdb->query( "UPDATE $wpdb->options SET autoload = 'no'" );

		$autoload_options = array('wpseo_onpage', '%options__hc_%_url', 'date_format', 'wpseo_rss', 'uninstall_plugins', 'category_base', 'widget_%', '_widget_%', 'sliderpro_%', 'wpseo_social', 'genesis-settings', 'show_on_front', 'siteurl', 'active_plugins', 'blog_public', '%options__hc_instagram_%', 'can_compress_scripts', 'stylesheet', 'siteurl', 'WPLANG', 'blogdescription', 'blog_charset', 'wpseo_permalinks', 'wpseo_xml', 'wp_user_roles', 'wpseo_titles', 'wpseo_internallinks', 'permalink_structure', 'rewrite_rules', 'theme_switched', 'link_manager_enabled', '%options__hc_%_page_id', 'use_smilies', 'sidebars_widgets', 'tag_base', 'page_on_front', 'template', 'html_type', 'posts_per_page', 'comments_per_page', 'sticky_posts', 'close_comments_for_old_posts', 'close_comments_days_old', 'db_version', 'cron', 'category_children', 'wpseo', 'page_for_posts', 'site_icon', 'default_ping_status', 'gmt_offset', 'uploads_use_yearmonth_folders', 'upload_url_path', 'upload_path', 'timezone_string', 'hack_file', 'thread_comments', 'home', 'avatar_default', 'avatar_rating', 'options__hc_facebook_app_id', 'options__hc_recaptcha_api_key', 'show_avatars', 'theme_mods_genesis-hc');

		foreach( $autoload_options as $option_name ) {
			$wpdb->query( "UPDATE $wpdb->options SET autoload = 'yes' WHERE option_name LIKE '$option_name'" );
		}
	}

	$count = $wpdb->query( "SELECT `option_name` FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_%')" );

	$label = __( 'Clear Transients', CHILD_THEME_TEXT_DOMAIN );
	$args  = array(
		'id'     => 'clear-transients',
		'title'  => !empty($count) ? $label . ' (' . $count . ')' : $label,
		'parent' => 'site-name',
		'href'   => get_admin_url() . '?clear-transients=1',
	);

	$wp_admin_bar->add_node( $args );

}

// add_action( 'admin_bar_menu', 'hc_cron_count_node', 99 );
/**
 * Show the length of the scheduled cron task list.
 *
 * @since 2.3.15
 */
function hc_cron_count_node( $wp_admin_bar ) {

	if( !is_admin() || !current_user_can('manage_options') )
		return;

	$count = get_option( 'cron' );
	$count = count($count);
	if( 0 === $count )
		return;

	$label = __( 'Cron Tasks:', CHILD_THEME_TEXT_DOMAIN );
	$args  = array(
		'id'    => 'cron-task-count',
		'title' => !empty($count) ? $label . ' ' . $count : $label,
	);

	$wp_admin_bar->add_node( $args );

}
