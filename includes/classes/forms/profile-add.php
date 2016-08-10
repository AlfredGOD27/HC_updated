<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Profile_Add_Form extends HC_Form_Abstract {
	public function __construct() {

		$this->action = 'add';

		parent::__construct();

	}

	protected function setup_fields() {

		$this->fields = array();

		$this->fields[] = array(
			'slug'     => 'first_name',
			'label'    => 'First Name',
			'type'     => 'text',
			'table'    => 'users',
			'required' => true,
			'classes'  => array('first', 'one-half'),
		);
		$this->fields[] = array(
			'slug'     => 'last_name',
			'label'    => 'Last Name',
			'type'     => 'text',
			'table'    => 'users',
			'required' => true,
			'classes'  => array('one-half'),
		);
		$this->fields[] = array(
			'slug'     => 'user_email',
			'label'    => 'Email Address',
			'type'     => 'email',
			'table'    => 'users',
			'required' => true,
			'classes'  => array('first', 'one-half'),
		);
		$this->fields[] = array(
			'slug'     => '_hc_user_city',
			'label'    => 'Choose Your City',
			'type'     => 'select',
			'table'    => 'usermeta',
			'required' => true,
			'options'  => HC()->profiles->get_city_options(),
			'classes'  => array('one-half'),
		);
		$this->fields[] = array(
			'slug'        => 'user_pass',
			'label'       => 'Password',
			'type'        => 'password',
			'table'       => 'users',
			'required'    => true,
			'classes'     => array('first', 'one-half'),
			'description' => 'We recommend that you create a password of at least 8 characters, and contain at least one number and one symbol.',
		);
		$this->fields[] = array(
			'slug'     => 'user_pass_2',
			'label'    => 'Confirm Password',
			'type'     => 'password',
			'table'    => 'users',
			'required' => false,
			'classes'  => array('one-half'),
		);

		foreach( $this->fields as $idx => $field ) {
			if( !isset($field['placeholder']) )
				$this->fields[$idx]['placeholder'] = $field['label'];
		}

	}

	protected function set_nonce_key() {

		$this->nonce_key = 'add_new_user';

	}

	protected function do_after_save() {

		HC()->messages->add( 'success', 'Profile saved.' );

	}

	public function display_form() {

		?>
		<form method="post" class="hc-form entry-content">
			<div class="form-body clearfix">
				<?php
				foreach( $this->fields as $field )
					$this->display_field( $field );
				?>
			</div>

			<div class="form-footer clearfix">
				<div id="register-popup-captcha" class="captcha"></div>

				<button type="submit" name="hc_register" class="btn" disabled>Create Account</button>
			</div>
		</form>
		<?php

	}

}
