<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Join_Widget extends WP_Widget {
	public function __construct() {

		parent::__construct(false, $name = 'HC: Join');

	}

	public function widget( $args, $instance ) {

		extract($args);

		echo $before_widget;
			?>
			<i class="ico-heart"></i>

			<?php
			if( is_user_logged_in() ) {
				$title = get_option( 'options__hc_join_user_title' );
				$text  = get_option( 'options__hc_join_user_text' );

				echo !empty($title) ? '<h3>' . $title . '</h3>' : '';
				echo !empty($text) ? wpautop($text) : '';
			} else {
				$title = get_option( 'options__hc_join_visitor_title' );
				$text  = get_option( 'options__hc_join_visitor_text' );

				echo !empty($title) ? '<h3>' . $title . '</h3>' : '';
				?>
				<button class="btn open-popup-link" data-mfp-src="#login-popup">Sign Up <i class="ico-exit"></i></button>
				<?php
				echo !empty($text) ? wpautop($text) : '';
			}
		echo $after_widget;

	}

	public function form( $instance ) {

	}

}
