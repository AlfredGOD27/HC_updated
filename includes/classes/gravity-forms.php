<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Gravity_Forms {
	public function __construct() {

		add_action( 'gform_paypal_fulfillment', array($this, 'fulfill_paypal_order'), 10, 4 );

	}

	public function add_points( $post_type, $user_id, $level, $quantity, $entry_id ) {

		$key    = '_hc_' . $post_type . '_credits_' . $level;
		$points = get_user_meta( $user_id, $key, true );
		$points += absint($quantity);
		update_user_meta( $user_id, $key, $points );

		$data = array(
			'post_type'          => $post_type,
			'level'              => $level,
			'target_user_id'     => $user_id,
			'initiating_user_id' => $user_id,
			'ref_id'             => $entry_id,
			'amount'             => $quantity,
		);

		HC()->logs->add( $data );

	}

	public function fulfill_paypal_order( $entry, $feed, $transaction_id, $amount ) {

		if( 'Paid' !== $entry['payment_status'] )
			return;

		$user_id = $entry['created_by'];

		$form     = GFAPI::get_form( $entry['form_id'] );
		$input_id = false;
		foreach( $form['fields'] as $field ) {
			if( empty($field['inputName']) )
				continue;

			if( 'purchase_type' === $field['inputName'] ) {
				$input_id = $field['id'];
				break 1;
			}
		}

		if( false === $input_id )
			return;

		$value = !empty($entry[$input_id]) ? $entry[$input_id] : '';
		$value = explode( '|', $value );

		switch( $value[0] ) {
			case 'event-upgrade':
				$this->add_points( 'event', $user_id, 'upgrade', 3, $entry['id'] );
				break;
			case 'event-premium':
				$this->add_points( 'event', $user_id, 'premium', 5, $entry['id'] );
				break;
		}

	}
}

return new HC_Gravity_Forms();
