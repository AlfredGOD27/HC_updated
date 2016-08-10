<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Subscriptions {
	public function __construct() {

		add_action( 'wp_ajax_hc_ajax_subscribe', array($this, 'ajax_subscribe') );
		add_action( 'wp_ajax_nopriv_hc_ajax_subscribe', array($this, 'ajax_subscribe') );

	}

	private function setup_api() {

		if( !isset($this->api_url) ) {
			$this->api_url = get_option( 'options__hc_mailchimp_api_url' );
			$this->api_url = untrailingslashit($this->api_url);
		}

		if( !isset($this->api_key) )
			$this->api_key = get_option( 'options__hc_mailchimp_api_key' );

		if( !isset($this->list_id) )
			$this->list_id = get_option( 'options__hc_mailchimp_list_id' );

	}

	public function get_subscriber_interests( $email ) {

		$this->setup_api();

		$url = $this->api_url . '/lists/' . $this->list_id . '/members/';
		$url .= md5( strtolower( $email ) );

		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'un_not_needed' . ':' . $this->api_key ),
			),
		);
		$response = wp_remote_get( $url, $args );

		if( is_wp_error($response) || 200 !== $response['response']['code'] )
			return array();

		$body = json_decode($response['body'], true);

		$interests = array();
		foreach( $body['interests'] as $interest_id => $enabled ) {
			if( !empty($enabled) )
				$interests[] = $interest_id;
		}

		return $interests;

	}

	public function subscribe( $email, $interests = array() ) {

		$this->setup_api();

		// Build query
		$url = $this->api_url . '/lists/' . $this->list_id . '/members/' . md5( strtolower($email) );

		$args = array(
			'method'  => 'PUT',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'un_not_needed' . ':' . $this->api_key ),
			),
			'body' => array(
				'status'        => 'subscribed',
				'email_address' => $email,
				'interests'     => $interests,
			),
		);

		$args['body'] = json_encode($args['body']);

		return wp_remote_post( $url, $args );

	}

	public function ajax_subscribe() {

		$output = array();

		$email = $_POST['email'];
		if( empty($email) ) {
			$output = array(
				'status'  => 'error',
				'message' => __( 'Email not valid.', CHILD_THEME_TEXT_DOMAIN ),
			);
		} else {
			$result = $this->subscribe( $email );

			if(200 === $result['response']['code'] ) {
				$output = array(
					'status'  => 'success',
					'message' => 'Successfully subscribed',
				);
			} else {
				$output = array(
					'status'  => 'error',
					'message' => 'Failed to subscribe',
				);
			}
		}

		$output = json_encode($output);
		echo $output;
		wp_die();

	}

	public function display_form( $location ) {

		?>
		<form class="subscribe-form">
			<label for="subscribe-email-<?php echo $location; ?>">Email</label>
			<div class="email-container">
				<input id="subscribe-email-<?php echo $location; ?>" type="email" required placeholder="Enter your email here">
			</div>

			<button type="submit" class="btn">Sign Me Up!</button>
		</form>
		<?php

	}

}

return new HC_Subscriptions();
