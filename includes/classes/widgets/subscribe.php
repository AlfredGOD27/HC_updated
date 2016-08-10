<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Subscribe_Widget extends WP_Widget {
	public function __construct() {

		parent::__construct(false, $name = 'HC: Subscribe');

	}

	public function widget( $args, $instance ) {

		extract($args);

		echo $before_widget;
			$title = get_field( '_hc_title', 'widget_' . $widget_id );
			if( !empty($title) )
				echo $before_title . sanitize_text_field($title) . $after_title;

			$above_text = get_field( '_hc_above_text', 'widget_' . $widget_id );
			if( !empty($above_text) )
				echo '<div class="above-text">' . wpautop( sanitize_text_field($above_text) ) . '</div>';

			HC()->subscriptions->display_form( 'widget-' . $widget_id );
		echo $after_widget;

	}

	public function form( $instance ) {

	}

}
