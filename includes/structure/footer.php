<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

remove_action( 'genesis_footer', 'genesis_do_footer' );
add_action( 'genesis_footer', 'hc_do_footer' );
function hc_do_footer() {

	?>
	<div class="footer-social-row clearfix">
		<div class="left">
			<i class="ico-instagram-logo"></i>
			<a href="https://www.instagram.com/honeycombers/" class="instagram-link">@honeycombers</a>

			<h4>Connect With Us</h4>
			<?php hc_do_social(); ?>
		</div>

		<div class="right hide-no-js clearfix">
			<?php
			$user_id = get_field( '_hc_instagram_user_id', 'option' );

			$transient_key = '_hc_instagram_' . $user_id;
			$transient_key = md5($transient_key);
			$images        = get_transient( $transient_key );
			if( false === $images ) {
				$user_id      = sanitize_text_field($user_id);
				$access_token = get_field( '_hc_instagram_access_token', 'option' );

				if( !empty($user_id) && !empty($access_token) ) {
					$url = add_query_arg(
						array(
							'access_token' => $access_token,
							'count'        => 7,
						),
						'https://api.instagram.com/v1/users/' . $user_id . '/media/recent'

					);

					$response = wp_remote_get( $url );
					if( !is_wp_error($response) &&
						isset($response['response']['code']) &&
						200 === $response['response']['code']
					) {
						$body = json_decode($response['body'], true);

						$images = array();
						foreach( $body['data'] as $data ) {
							$image           = array();
							$image['url']    = esc_url($data['link']);
							$image['src']    = esc_url($data['images']['low_resolution']['url']);
							$image['title']  = esc_attr( sanitize_text_field($data['caption']['text']) );
							$image['width']  = absint($data['images']['low_resolution']['width']);
							$image['height'] = absint($data['images']['low_resolution']['height']);
							$images[]        = $image;
						}

						set_transient( $transient_key, $images, HOUR_IN_SECONDS * 3 );
					}
				}
			}

			if( !empty($images) ) {
				$i = 1;
				foreach( $images as $image ) {
					$class = $i > 3 ? 'skip-image-on-mobile' : '';
					echo $i > 3 ? '<div class="hide-phone">' : '<div>';
						echo '<a href="' . $image['url'] . '" target="_blank" rel="nofollow">';
							$atts = array(
								'src'    => $image['src'],
								'alt'    => $image['title'],
								'width'  => $image['width'],
								'height' => $image['height'],
							);
							echo HC()->utilities->get_async_image_placeholder( $atts, $class );
						echo '</a>';
					echo '</div>';
					++$i;
				}
			}
			?>
		</div>
	</div>

	<div class="favicon-row">
		<i class="ico-favicon"></i>
	</div>

	<div class="footer-bottom">
		<?php

		wp_nav_menu(
			array(
				'menu_class'     => 'footer-menu',
				'theme_location' => 'footer',
				'depth'          => 1,
			)
		);

		?>
		<p>&copy; Honeycombers Pte Ltd <?php echo date('Y'); ?> All Rights Reserved</p>
	</div>
	<?php

}

// add_action( 'wp_footer', 'hc_disable_pointer_events_on_scroll', 99 );
/**
 * Disable pointer events when scrolling. Be careful using this with CSS :hover-enabled menus.
 *
 * See: https://gist.github.com/ossreleasefeed/7768761
 *
 * @since 2.0.20
 */
function hc_disable_pointer_events_on_scroll() {

	ob_start();
	?><script>
		if( window.addEventListener ) {
			var root = document.documentElement;
			var timer;

			window.addEventListener('scroll', function() {
				clearTimeout(timer);

				if (!root.style.pointerEvents) {
					root.style.pointerEvents = 'none';
				}

				timer = setTimeout(function() {
					root.style.pointerEvents = '';
				}, 250);
			}, false);
		}
	</script>
	<?php
	$output = ob_get_clean();
	echo preg_replace( '/\s+/', ' ', $output ) . "\n";

}

add_action( 'wp_footer', 'hc_ie_font_face_fix', 99 );
/**
 * Forces the main stylesheet to reload on document ready for IE8 and below.
 * This redraws any @font-face fonts, fixing the IE8 font loading bug.
 *
 * See: http://stackoverflow.com/questions/9809351/ie8-css-font-face-fonts-only-working-for-before-content-on-over-and-sometimes
 *
 * @since 2.0.13
 */
function hc_ie_font_face_fix() {

	ob_start();
	?><!--[if lt IE 9]>
		<script>
			jQuery(document).ready(function($) {
				var head = document.getElementsByTagName('head')[0],
					style = document.createElement('style');
				style.type = 'text/css';
				style.styleSheet.cssText = ':before,:after{content:none !important;}';
				head.appendChild(style);
				setTimeout(function(){
					head.removeChild(style);
				}, 0);
			});
		</script>
	<![endif]-->
	<?php
	$output = ob_get_clean();
	echo preg_replace( '/\s+/', ' ', $output ) . "\n";

}
