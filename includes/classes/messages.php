<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Messages {
	public function __construct() {

		$this->messages = array();

	}

	public function add( $type, $text ) {

		$this->messages[] = array(
			'type' => $type,
			'text' => $text,
		);

	}

	public function display() {

		if( 0 === count($this->messages) )
			return;

		echo '<div class="messages">';
			foreach( $this->messages as $message ) {
				echo '<div class="alert alert-' . $message['type'] . '">';
					echo $message['text'];
				echo '</div>';
			}
		echo '</div>';

		$this->messages = array();

	}

	public function add_and_display( $type, $text ) {

		$this->add( $type, $text );
		$this->display();

	}

}

return new HC_Messages();
