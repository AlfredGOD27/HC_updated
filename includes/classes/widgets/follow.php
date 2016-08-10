<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Follow_Widget extends WP_Widget {
	public function __construct() {

		parent::__construct(false, $name = 'HC: Follow');

	}

	public function widget( $args, $instance ) {

		extract($args);

		echo $before_widget;
			$title = get_field( '_hc_title', 'widget_' . $widget_id );
			if( !empty($title) )
				echo $before_title . '<span>' . sanitize_text_field($title) . '</span>' . $after_title;

			hc_do_social();
		echo $after_widget;

	}

	public function form( $instance ) {

	}

}
