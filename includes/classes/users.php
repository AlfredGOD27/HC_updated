<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Users {
	public function __construct() {

		add_action( 'wp_ajax_nopriv_hc_ajax_register', array($this, 'ajax_register') );
		add_action( 'wp_ajax_nopriv_hc_ajax_login', array($this, 'ajax_login') );
		add_action( 'wp_ajax_nopriv_hc_ajax_reset_password', array($this, 'ajax_reset_password') );
		add_action( 'wp_ajax_nopriv_hc_ajax_facebook_register_or_login', array($this, 'ajax_facebook') );
		add_action( 'wp_footer', array($this, 'display_modal') );

		add_filter('acf/update_value/name=_hc_profile_image_id', array($this, 'save_ms_user_image'), 99, 3);
		add_filter('acf/load_field/name=_hc_profile_image_id', array($this, 'load_ms_user_image_name'));

	}

	private function die_with_error( $message ) {

		$output = array(
			'status'  => 'error',
			'message' => $message,
		);
		echo json_encode($output);
		wp_die();

	}

	private function die_with_success( $message, $user_id, $redirect_params = array() ) {

		$url = add_query_arg(
			$redirect_params,
			HC()->profiles->get_url()
		);

		$output = array(
			'status'      => 'success',
			'message'     => $message,
			'redirect_to' => $url,
		);
		echo json_encode($output);
		wp_die();

	}

	private function verify_captcha( $code ) {

		$url = 'https://www.google.com/recaptcha/api/siteverify';

		$args = array(
			'body' => array(
				'secret'   => get_option( 'options__hc_recaptcha_api_secret' ),
				'response' => $code,
			),
		);

		$response = wp_remote_post( $url, $args );

		if( 200 !== $response['response']['code'] )
			return false;

		$body = json_decode($response['body'], true);

		return !empty($body['success']);

	}

	private function user_registered( $user_id ) {

		HC()->folders->create_default_folders_for_user( $user_id );

	}

	public function ajax_register() {

		if( empty($_POST['captcha']) )
			$this->die_with_error( 'Invalid CAPTCHA.' );

		$valid = $this->verify_captcha($_POST['captcha']);
		if( !$valid )
			$this->die_with_error( 'Invalid CAPTCHA.' );

		$form = new HC_Profile_Add_Form();

		// Stop if mismatches PW fields
		$passwords_match = $form->check_passwords_match();
		if( !$passwords_match )
			$this->die_with_error( 'Your passwords don\'t match.' );

		// Stop if mismatches PW fields
		$password_score = $form->check_password_strength();
		if( !$password_score )
			$this->die_with_error( 'You must choose a stronger password.' );

		$args = array(
			'users'    => array(),
			'usermeta' => array(),
		);

		// Populate fields
		foreach( $form->fields as $field ) {
			if( isset($field['disabled']) && $field['disabled'] )
				continue;

			switch( $field['type'] ) {
				case 'text':
				case 'email':
				case 'url':
				case 'number':
				case 'textarea':
				case 'select':
				case 'radio':
				case 'boolean':
					if( isset($_POST[ $field['slug'] ]) )
						$args[ $field['table'] ][ $field['slug'] ] = $form->sanitize_value( $field, $_POST[ $field['slug'] ] );
				case 'password':
					// Skip the '_2' version
					if( substr($field['slug'], -2) === '_2' )
						continue;

					if( !empty($_POST[ $field['slug'] ]) )
						$args[ $field['table'] ][ $field['slug'] ] = $form->sanitize_value( $field, $_POST[ $field['slug'] ] );
					break;
			}
		}

		// Stop if empty required fields
		$empty_required_fields = $form->check_required( $args );
		if( count($empty_required_fields) > 0 ) {
			foreach( $empty_required_fields as $field )
				$this->die_with_error( '<strong>' . $field['label'] . '</strong> is a required field.' );
		}

		if( email_exists($args['users']['user_email']) || username_exists($args['users']['user_email']) )
			$this->die_with_error( 'A user with this email already exists.' );

		$args['users']['role']       = 'subscriber';
		$args['users']['user_login'] = $args['users']['user_email'];
		$user_id                     = wp_insert_user( $args['users'] );

		if( is_wp_error($user_id) || 0 === $user_id ) {
			$this->die_with_error( 'An error occurred when creating your account. Please refresh the page and try again.' );
		} else {
			$this->user_registered( $user_id );

			// User created. Save meta-type fields
			foreach( $form->fields as $field ) {
				if( !isset($args[ $field['table'] ][ $field['slug'] ]) )
					continue;

				switch( $field['table'] ) {
					case 'usermeta':
						update_user_meta( $user_id, $field['slug'], $args[ $field['table'] ][ $field['slug'] ] );
						break;
				}
			}

			// Set login cookie
			wp_set_auth_cookie( $user_id );
			$this->die_with_success( 'Registration successful, redirecting...', $user_id );
		}

	}

	public function ajax_login() {

		if( empty($_POST['log']) || empty($_POST['pwd']) )
			$this->die_with_error( 'You must enter an email and password.' );

		if( empty($_POST['captcha']) )
			$this->die_with_error( 'Invalid CAPTCHA.' );

		$valid = $this->verify_captcha($_POST['captcha']);
		if( !$valid )
			$this->die_with_error( 'Invalid CAPTCHA.' );

		$email = sanitize_email($_POST['log']);
		$user  = get_user_by( 'email', $email );
		if( empty($user) )
			$this->die_with_error( 'Your email or password is incorrect.' );

		$_POST['log'] = $user->user_login;
		$signon       = wp_signon();
		if( is_wp_error($signon) ) {
			$this->die_with_error( 'Your email or password is incorrect.' );
		} else {
			$this->die_with_success( 'Login successful, redirecting...', $user->ID );
		}

	}

	public function ajax_reset_password() {

		global $wpdb, $wp_hasher;

		$output = array();

		if( empty($_POST['email']) ) {
			$this->die_with_error( 'You must enter a valid email.' );
		} else {
			$email = sanitize_email($_POST['email']);
		}

		if( empty($_POST['captcha']) )
			$this->die_with_error( 'Invalid CAPTCHA.' );

		$valid = $this->verify_captcha($_POST['captcha']);
		if( !$valid )
			$this->die_with_error( 'Invalid CAPTCHA.' );

		$user = get_user_by( 'email', $email );
		if( empty($user) )
			$this->die_with_error( 'There is no user registered with that email address.' );

		do_action( 'lostpassword_post' );

		$user_login = $user->user_login;
		$user_email = $user->user_email;

		do_action( 'retrieve_password', $user_login );

		$allow = apply_filters( 'allow_password_reset', true, $user->ID );
		if( !$allow )
			$this->die_with_error( 'You are not allowed to reset your password.' );

		$key = wp_generate_password( 20, false );
		do_action( 'retrieve_password_key', $user_login, $key );

		// Now insert the key, hashed, into the DB.
		if( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, true );
		}
		$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
		$wpdb->update( $wpdb->users, array('user_activation_key' => $hashed), array('user_login' => $user_login) );

		$url = add_query_arg(
			array(
				'key'   => $key,
				'login' => rawurlencode($user_login),
			),
			HC()->profiles->get_url('reset-password')
		);

		$message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
		$message .= network_home_url( '/' ) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
		$message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
		$message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
		$message .= $url . "\r\n";

		$message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$title    = sprintf( __('[%s] Password Reset'), $blogname );
		$title    = apply_filters( 'retrieve_password_title', $title );

		if( $message && !wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) )
			$this->die_with_error( 'The password reset email could not be sent. Please refresh the page and try again.' );

		$output = array(
			'status'  => 'success',
			'message' => 'Check your email for the password reset link.',
		);
		echo json_encode($output);
		wp_die();

	}

	public function ajax_facebook() {

		global $wpdb;

		// Stop if no token
		if( !isset($_POST['token']) )
			$this->die_with_error( 'Invalid Facebook authorization. Please refresh the page and try again.' );

		// Get user info from FB
		$url = add_query_arg(
			array(
				'access_token' => $_POST['token'],
				'fields'       => 'first_name,last_name,email',
			),
			'https://graph.facebook.com/me'
		);
		$response = wp_remote_get( $url );

		// Stop if not retreived
		if(
			is_wp_error($response) ||
			200 !== $response['response']['code']
		)
			$this->die_with_error( 'Invalid Facebook authorization. Please refresh the page and try again.' );

		// Build array
		$body = $response['body'];
		$body = json_decode( $body, true );

		// Stop if no FB ID
		if( !isset($body['id']) ) {
			$this->die_with_error( 'Facebook user not found. Please refresh the page and try again.' );
		} else {
			$facebook_id = sanitize_text_field($body['id']);
		}

		// Stop if no email
		if( !isset($body['email']) ) {
			$this->die_with_error( 'Your Facebook account doesn\'t appear to have an associated email address. Please register directly.' );
		} else {
			$email = $body['email'];
		}

		// Try to find FB ID in WP DB
		$result = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT user_id
				FROM $wpdb->usermeta
				WHERE meta_key = '_hc_facebook_id'
				AND meta_value = %s
				LIMIT 1
				",
				$facebook_id
			)
		);

		if( 0 === count($result) ) {
			// Register
			if( email_exists($email) || username_exists($email) ) {
				$this->die_with_error( 'A user with this email already exists.' );
			} else {
				// Save name, if available
				$first_name = $body['first_name'] ? sanitize_text_field($body['first_name']) : '';
				$last_name  = $body['last_name'] ? sanitize_text_field($body['last_name']) : '';

				$args = array(
					'user_login' => $email,
					'user_email' => $email,
					'user_pass'  => wp_generate_password( 32, false ),
					'role'       => 'subscriber',
					'first_name' => $first_name,
					'last_name'  => $last_name,
				);
				$user_id = wp_insert_user( $args );

				if( is_wp_error($user_id) || 0 === $user_id ) {
					$this->die_with_error( 'An error occurred when creating your account. Please refresh the page and try again.' );
				} else {
					$this->user_registered( $user_id );

					// Save FB ID
					update_user_meta( $user_id, '_hc_facebook_id', $facebook_id );

					// Set login cookie
					wp_set_auth_cookie( $user_id );
					$this->die_with_success( 'Registration successful, redirecting...', $user_id, array('welcome' => true) );
				}
			}
		} else {
			// Login
			$user = get_user_by( 'id', $result[0]->user_id );
			if( empty($user) || is_wp_error($user) ) {
				$this->die_with_error( 'User not found.' );
			} else {
				if( user_can( $user, 'edit_posts') ) {
					$this->die_with_error( 'Administrators cannot login via Facebook' );
				} else {
					// Set login cookie
					wp_set_auth_cookie( $user->ID );
					$this->die_with_success( 'Login successful, redirecting...', $user->ID );
				}
			}
		}

	}

	public function display_modal() {

		if( is_user_logged_in() )
			return;

		?>
		<aside id="login-popup" class="white-popup login-popup mfp-hide clearfix">
			<i class="ico-favicon"></i>

			<h2>Welcome to Honeycombers!</h2>

			<p class="lead">Discover, save and share interesting stories on Honeycombers.</p>

			<div class="messages"></div>

			<button type="button" class="btn btn-facebook btn-icon hide-no-fb"><i class="ico-facebook"></i> <span>Login Via Facebook</span></button>

			<span class="or hide-no-fb"><span>Or</span></span>

			<form action="#" method="post">
				<div class="field">
					<label for="user_login_email" class="screen-reader-text">Email</label>
					<input type="email" name="log" id="user_login_email" placeholder="Email" required>
				</div>

				<div class="field">
					<label for="user_login_password" class="screen-reader-text">Password</label>
					<input type="password" name="pwd" id="user_login_password" placeholder="Password" required>
				</div>

				<div class="forgot-remember-bar clearfix">
					<div class="one-half first left">
						<a href="#password-popup" class="open-popup-link" rel="nofollow">Forgot your password?</a>
					</div>

					<div class="one-half right">
						<label class="checkbox">
							<input name="rememberme" type="checkbox" value="forever">
							Stay logged in
						</label>
					</div>
				</div>

				<div id="login-popup-captcha" class="captcha"></div>

				<button type="submit" name="wp-submit" class="btn" disabled>Log In</button>

				<p class="join">Don't have an account? <a href="#register-popup" class="open-popup-link" rel="nofollow">Join here</a></p>
			</form>
		</aside>
		<?php

		?>
		<aside id="password-popup" class="white-popup password-popup mfp-hide clearfix">
			<h2>Forgot your password? No problem.</h2>

			<p class="lead">Instructions for resetting your password <br> will be sent to your email</p>

			<div class="messages"></div>

			<form action="#" method="post" autocomplete="off">
				<div class="field">
					<label for="forgot_password_email" class="screen-reader-text">Email</label>
					<input type="email" name="user_login" id="forgot_password_email" placeholder="Email" required>
				</div>

				<div id="password-popup-captcha" class="captcha"></div>

				<div class="form-footer">
					<button type="submit" name="wp-submit" class="btn" disabled>Send</button>
				</div>
			</form>
		</aside>
		<?php

		?>
		<aside id="register-popup" class="white-popup register-popup mfp-hide clearfix">
			<i class="ico-favicon"></i>

			<h2>Welcome to Honeycombers!</h2>

			<p class="lead">Discover, save and share interesting stories on Honeycombers.</p>

			<div class="messages"></div>

			<button type="button" class="btn btn-facebook btn-icon hide-no-fb"><i class="ico-facebook"></i> <span>Login Via Facebook</span></button>

			<span class="or hide-no-fb"><span>Or</span></span>

			<?php
			$form = new HC_Profile_Add_Form();
			$form->display_form();
			?>

			<p class="join">Already have an account? <a href="#login-popup" class="open-popup-link" rel="nofollow">Sign in here</a></p>
		</aside>
		<?php
	}

	public function get_ms_user_image_key() {

		$key = '_hc_profile_image_id';
		if( is_multisite() ) {
			$site_id = get_current_blog_id();
			if( 2 !== absint($site_id) )
				$key .= '_' . get_current_blog_id();
		}

		return $key;

	}

	public function save_ms_user_image( $value, $post_id, $field  ) {

		if( is_multisite() )
			update_user_meta( $post_id, $this->get_ms_user_image_key(), $value );

		return $value;

	}

	public function load_ms_user_image_name( $field ) {

		if( is_multisite() ) {
			$field['name']  = $this->get_ms_user_image_key();
			$field['_name'] = $this->get_ms_user_image_key();
		}

		return $field;

	}

}

return new HC_Users();
