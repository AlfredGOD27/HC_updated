<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Widgets {
	private static $widgets = array(
		'ad'        => 'HC_Ad_Widget',
		'featured'  => 'HC_Featured_Widget',
		'follow'    => 'HC_Follow_Widget',
		'join'      => 'HC_Join_Widget',
		'subscribe' => 'HC_Subscribe_Widget',
	);

	public function __construct() {

		add_action( 'widgets_init', array($this, 'register') );

		foreach( self::$widgets as $key => $class_name )
			require_once CHILD_DIR . '/includes/classes/widgets/' . $key . '.php';

	}

	public function register() {

		foreach( self::$widgets as $key => $class_name )
			register_widget($class_name);

	}
}

return new HC_Widgets();
