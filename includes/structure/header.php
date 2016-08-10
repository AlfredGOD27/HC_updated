<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
 * Cleanup <head>
 *
 * @since 2.0.0
 */
remove_action( 'wp_head', 'rsd_link' );									// RSD link
remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );				// Parent rel link
remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );				// Start post rel link
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );	// Adjacent post rel link
remove_action( 'wp_head', 'wp_generator' );								// WP Version
remove_action( 'wp_head', 'wlwmanifest_link');							// WLW Manifest
// remove_action( 'wp_head', 'feed_links', 2 ); 
remove_action( 'wp_head', 'feed_links_extra', 3 ); 						// Remove comment feed links

// Remove WP-API <head> material
// See: https://wordpress.stackexchange.com/questions/211467/remove-json-api-links-in-header-html
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'template_redirect', 'rest_output_link_header', 11, 0 );

remove_action( 'genesis_doctype', 'genesis_do_doctype' );
add_action( 'genesis_doctype', 'hc_do_doctype' );
/**
 * Overrides the default Genesis doctype with IE and JS identifier classes.
 *
 * See: http://html5boilerplate.com/
 *
 * @since 2.2.4
 */
function hc_do_doctype() {

	if( genesis_html5() ) {
?>
<!DOCTYPE html>
<!--[if IE 8]> <html class="no-js lt-ie9" <?php language_attributes( 'html' ); ?>> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" <?php language_attributes( 'html' ); ?>> <!--<![endif]-->
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<?php
	} else {
		genesis_xhtml_doctype();
	}

}

add_action( 'wp_head', 'hc_fetch_dns', 1 );
/**
 * Prefetch the DNS for external resource domains. Better browser support than preconnect.
 *
 * See: https://www.igvita.com/2015/08/17/eliminating-roundtrips-with-preconnect/
 *
 * @since 2.3.19
 */
function hc_fetch_dns() {

	$hrefs = array(
		'//ajax.googleapis.com',
		'//fonts.googleapis.com',
	);

	foreach( $hrefs as $href )
		echo '<link rel="dns-prefetch" href="' . $href . '">' . "\n";

}

remove_action( 'genesis_meta', 'genesis_load_stylesheet' );
remove_action( 'wp_enqueue_scripts', 'genesis_register_scripts' );
add_action( 'wp_enqueue_scripts', 'hc_load_assets' );
/**
 * Overrides the default Genesis stylesheet with child theme specific CSS and JS.
 *
 * Only load these styles on the front-end.
 *
 * @since 2.0.0
 */
function hc_load_assets() {

	$use_production_assets = genesis_get_option('hc_production_on');
	$use_production_assets = !empty($use_production_assets);

	$assets_version = genesis_get_option('hc_assets_version');
	$assets_version = !empty($assets_version) ? absint($assets_version) : null;

	$stylesheet_dir = get_stylesheet_directory_uri();

	// Main theme stylesheet
	$src = $use_production_assets ? '/build/css/style.min.css' : '/build/css/style.css';
	wp_enqueue_style( 'hc', $stylesheet_dir . $src, array(), $assets_version );

	// Google Fonts
	// Consider async loading: https://github.com/typekit/webfontloader
	wp_enqueue_style(
		'google-fonts',
		'//fonts.googleapis.com/css?family=Montserrat%7COpen+Sans:400,400italic,600,700%7CNoto+Serif:400italic',
		array(),
		null
	);

 	// Dequeue comment-reply if no active comments on page
	wp_dequeue_script( 'comment-reply' );

	// Override WP default self-hosted jQuery with version from Google's CDN
	wp_deregister_script( 'jquery' );
	$src = $use_production_assets ? '//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js' : '//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.js';
	wp_register_script( 'jquery', $src, array(), null, true );
	add_filter( 'script_loader_src', 'hc_jquery_local_fallback', 10, 2 );

	// Main script file (in footer)
	$src = $use_production_assets ? '/build/js/scripts.min.js' : '/build/js/scripts.js';
	wp_enqueue_script( 'hc', $stylesheet_dir . $src, array('jquery'), $assets_version, true );
	wp_localize_script(
		'hc',
		'grunticon_paths',
		array(
			'svg'      => $stylesheet_dir . '/build/svgs/icons.data.svg.css',
			'png'      => $stylesheet_dir . '/build/svgs/icons.data.png.css',
			'fallback' => $stylesheet_dir . '/build/svgs/icons.fallback.css',
		)
	);

	wp_localize_script(
		'hc',
		'hc_settings',
		array(
			'facebook_app_id' => get_option( 'options__hc_facebook_app_id' ),
			'recaptcha_key'   => get_option( 'options__hc_recaptcha_api_key' ),
		)
	);

	$spinner = '<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>';
	wp_localize_script(
		'hc',
		'hc_strings',
		array(
			'spinner'     => $spinner,
			'more_button' => '<button type="button" class="il-load-more"><span><i class="ico-arrow-down"></i><label>more cool stuff</label></span></button>',
			'loading'     => '<div class="il-loading">' . $spinner . '</div>',
			'prev_arrow'  => '<button type="button" class="slick-prev" title="Previous"><i class="ico-arrow-left"></i></button>',
			'next_arrow'  => '<button type="button" class="slick-next" title="Next"><i class="ico-arrow-right"></i></button>',
		)
	);
	wp_localize_script( 'hc', 'ajax_object', array('ajaxurl' => admin_url( 'admin-ajax.php' )) );

	$page_template_slug = get_page_template_slug();
	if( 'page_templates/page_calendar.php' === $page_template_slug ) {
		$src = $use_production_assets ? '/build/js/calendar.min.js' : '/build/js/calendar.js';
		wp_enqueue_script( 'hc-calendar', $stylesheet_dir . $src, array('jquery', 'hc'), $assets_version, true );
	}

	if( !is_singular('post') ) {
		wp_dequeue_style( 'sliderpro-plugin-style' );
		wp_dequeue_style( 'sliderpro-plugin-custom-style' );
	}

}

add_filter( 'script_loader_tag', 'hc_ie_script_conditionals', 10, 3 );
/**
 * Conditionally load jQuery v1 on old IE.
 *
 * @since 2.3.1
 */
function hc_ie_script_conditionals( $tag, $handle, $src ) {

	if( 'jquery' === $handle ) {
		$output = '<!--[if !IE]> -->' . "\n" . $tag . '<!-- <![endif]-->' . "\n";
		$output .= '<!--[if gt IE 8]>' . "\n" . $tag . '<![endif]-->' . "\n";

		$use_production_assets = genesis_get_option('hc_production_on');
		$use_production_assets = !empty($use_production_assets);
		$src                   = $use_production_assets ? '//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js' : '//ajax.googleapis.com/ajax/libs/jquery/1/jquery.js';
		$fallback_script       = '<script type="text/javascript" src="' . $src . '"></script>';
		$output .= '<!--[if lte IE 8]>' . "\n" . $fallback_script . '<![endif]-->' . "\n";
	} elseif( 'hc-google-maps' === $handle ) {
		$output = str_replace( '<script ', '<script async defer ', $tag );
	}else {
		$output = $tag;
	}

	return $output;

}

/*
 * jQuery local fallback, if Google CDN is unreachable
 *
 * See: https://github.com/roots/roots/blob/aa59cede7fbe2b853af9cf04e52865902d2ff1a9/lib/scripts.php#L37-L52
 *
 * @since 2.0.20
 */
add_action( 'wp_head', 'hc_jquery_local_fallback' );
function hc_jquery_local_fallback( $src, $handle = null ) {

	static $add_jquery_fallback = false;

	if( $add_jquery_fallback ) {
		echo '<script>window.jQuery || document.write(\'<script src="' . includes_url() . 'js/jquery/jquery.js"><\/script>\')</script>' . "\n";
		$add_jquery_fallback = false;
	}

	if( $handle === 'jquery' ) {
		$add_jquery_fallback = true;
	}

	return $src;

}

// add_filter( 'genesis_pre_load_favicon', 'hc_pre_load_favicon' );
/**
 * Simple favicon override to specify your favicon's location.
 *
 * @since 2.0.0
 */
function hc_pre_load_favicon() {

	return get_stylesheet_directory_uri() . '/images/favicon.ico';

}

remove_action( 'wp_head', 'genesis_load_favicon' );
add_action( 'wp_head', 'hc_load_favicons' );
/**
 * Show the best favicon, within reason.
 *
 * See: http://www.jonathantneal.com/blog/understand-the-favicon/
 *
 * @since 2.0.4
 */
function hc_load_favicons() {

	$stylesheet_dir     = get_stylesheet_directory_uri();
	$favicon_path       = $stylesheet_dir . '/images/favicons';
	$favicon_build_path = $stylesheet_dir . '/build/images/favicons';

	// Set to false to disable, otherwise set to a hex color
	$color = '#fe862c';

	// Use a 192px X 192px PNG for the homescreen for Chrome on Android
	echo '<link rel="icon" type="image/png" href="' . $favicon_build_path . '/favicon-192.png" sizes="192x192">';

	// Use a 180px X 180px PNG for the latest iOS devices, also setup app styles
	echo '<link rel="apple-touch-icon" sizes="180x180" href="' . $favicon_build_path . '/favicon-180.png">';

	// Give IE <= 9 the old favicon.ico (16px X 16px)
	echo '<!--[if IE]><link rel="shortcut icon" href="' . $favicon_path . '/favicon.ico"><![endif]-->';

	// Use a 144px X 144px PNG for Windows tablets
	echo '<meta name="msapplication-TileImage" content="' . $favicon_build_path . '/favicon-144.png">';

	if( false !== $color ) {
		// Windows icon background color
		echo '<meta name="msapplication-TileColor" content="#ffffff">';

		// Chrome for Android taskbar color
		echo '<meta name="theme-color" content="#ffffff">';

		// Safari 9 pinned tab color
		echo '<link rel="mask-icon" href="' . $favicon_build_path . '/favicon.svg" color="' . $color . '">';
	}

}

/*
 * Remove the header
 *
 * @since 2.0.9
 */
// remove_action( 'genesis_header', 'genesis_do_header' );

/*
 * Remove the site title and/or description
 *
 * @since 2.0.9
 */
// remove_action( 'genesis_site_title', 'genesis_seo_site_title' );
// remove_action( 'genesis_site_description', 'genesis_seo_site_description' );
add_action( 'genesis_before', 'hc_ga_content_grouping' );
function hc_ga_content_grouping() { 
	?>
	<?php
	function gtm_posttype() {
		global $wp_query;
		global $post;
		$term =	$wp_query->queried_object;
		$cat = '(not set)';
	 
		if ( $wp_query->is_page && $wp_query->is_front_page ) {
			$cat = 'Home Page';
		} elseif ( $wp_query->is_page()) {
			$cat = is_page('calendar') ? 'Calendar' : 'Page';
		} elseif ( $wp_query->is_home ) {
			$cat = 'Home Page';
		} elseif ( $wp_query->is_category ) {
			$cat = get_category(get_query_var('cat'))->name;
		} elseif ( $wp_query->is_tag ) {
			$cat = single_tag_title("", false);
		} elseif ( $wp_query->is_tax ) {
			$cat = $term->name;
		} elseif ( $wp_query->is_archive ) {
			$cat = 'Archive';
		} elseif ( $wp_query->is_search ) {
			$cat = 'Search';
		} elseif ( $wp_query->is_404 ) {
			$cat = '404 Page';
		}
	 
		return $cat;
	}
	?>
	 
	<script>
		<?php
			global $post;
			if (is_single()) {
				$term_list = wp_get_post_terms($post->ID, array('category','event-category'), array("fields" => "all"));
				foreach($term_list as $term_single) {				
		?>
					ga('set', 'contentGroup1', '<?php echo $term_single->name; ?>'); 
		<?php	
				}
			} else {
		?>
			ga('set', 'contentGroup1', '<?php echo gtm_posttype(); ?>'); 
		<?php } ?>
	</script>
	
<?php }


add_action( 'genesis_before_header', 'hc_site_top' );
function hc_site_top() {

	?>
</script>
<section class="site-top">
		<div class="wrap">
			<div class="left">
				<?php
				wp_nav_menu(
					array(
						'menu_class'     => 'sites-nav',
						'theme_location' => 'top',
						'depth'          => 1,
					)
				);
				?>
			</div>

			<div class="right">
				<?php hc_do_social(); ?>

				<div class="nav-or-popup-link">
					<?php
					if( !is_user_logged_in() ) {
						?>
						<button class="open-popup-link" data-mfp-src="#login-popup">Sign In <i class="ico-exit"></i></button>
						<?php
					} else {
						HC()->profiles->display_top_menu();
					}
					?>
				</div>
			</div>
		</div>
	</section>
	<?php

}

add_action( 'genesis_site_title', 'hc_site_logo' );
function hc_site_logo() {

	echo '<a href="' . trailingslashit( home_url() ) . '" title="' . get_bloginfo( 'name' ) . '" class="site-logo"><img src="' . get_stylesheet_directory_uri() . '/build/images/logo.svg" alt="' . get_bloginfo( 'name' ) . '" width="319" height="57"><i class="ico-favicon"></i></a>';

}

add_action( 'genesis_header', 'hc_mobile_menu_toggle', 8 );
function hc_mobile_menu_toggle() {

	?>
	<div class="mobile-header-right">
		<?php
		if( !is_user_logged_in() ) {
			?>
			<button class="btn btn-bordered open-popup-link" data-mfp-src="#login-popup">Login</button>
			<?php
		} else {
			?>
			<a href="<?php echo HC()->profiles->get_url(); ?>" class="btn btn-bordered">Hello, <?php echo HC()->profiles->get_first_name( get_current_user_id() ); ?></a>
			<?php
		}
		?>
		<button type="button" class="btn toggle-nav" title="Toggle Menu">
			<i class="ico-menu"></i>
			<i class="ico-close"></i>
		</button>
	</div>
	<?php

}

add_action( 'genesis_header_right', 'hc_header_right' );
function hc_header_right() {

	echo get_search_form( false );

}

add_action( 'wp_footer', 'hc_sticky_header' );
function hc_sticky_header() {

	$use_sticky = 'page_templates/page_directory.php' !== get_page_template_slug();

	?>
	<section class="sticky-header <?php echo $use_sticky ? 'use-sticky' : ''; ?>">
		<div class="wrap">
			<div class="left">
				<a href="<?php echo get_bloginfo('url'); ?>" title="<?php echo get_bloginfo('name'); ?>">
					<i class="ico-favicon"></i>
				</a>
			</div>
			<div class="right">
				<div class="top">
					<?php
					echo get_search_form( false );
					?>

					<nav class="sites-nav">
						<button type="button" class="btn btn-icon"><span><?php echo get_bloginfo('name'); ?></span> <i class="ico-arrow-down"></i></button>
						<?php
						wp_nav_menu(
							array(
								'theme_location' => 'top',
								'depth'          => 1,
							)
						);
						?>
					</nav>

					<?php
					hc_do_social();
					?>

					<div class="user-menu">
						<?php
						if( !is_user_logged_in() ) {
							?>
							<button class="btn btn-icon open-popup-link" data-mfp-src="#login-popup"><span>Sign In</span> <i class="ico-exit"></i></button>
							<?php
						} else {
							?>
							<a href="<?php echo HC()->profiles->get_url(); ?>" class="btn btn-icon"><span>Hello, <?php echo HC()->profiles->get_first_name( get_current_user_id() ); ?></span></a>
							<?php
						}
						?>
					</div>

					<?php
					if( $use_sticky ) {
						?>
						<div class="scroll-to-top">
							<button type="button" class="btn btn-icon"><i class="ico-arrow-up"></i></button>
						</div>
						<?php
					}
					?>
				</div>
				<div class="bottom">
					<?php
					HC()->menu->display();
					?>
				</div>
			</div>
		</div>
	</section>
	<?php

}

add_action( 'genesis_header', 'hc_header_nav_wrap_open', 4 );
function hc_header_nav_wrap_open() {

	?>
	<div class="header-navigation-container">
	<?php

}

add_action( 'genesis_after_header', 'hc_header_nav_wrap_close', 14 );
function hc_header_nav_wrap_close() {

	?>
	</div>
	<?php

}
