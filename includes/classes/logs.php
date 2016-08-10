<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Logs {
	public function __construct() {

		global $wpdb;

		$this->table_name = $wpdb->prefix . 'logs';

		$this->keys = array(
			'_hc_event_credits_upgrade' => array(
				'post_type' => 'event',
				'level'     => 'upgrade',
			),
			'_hc_event_credits_premium' => array(
				'post_type' => 'event',
				'level'     => 'premium',
			),
		);
		$this->values_to_check = array();

		add_action( 'acf/save_post', array($this, 'before_profile_save'), 8 );
		add_action( 'acf/save_post', array($this, 'after_profile_save'), 12 );

	}

	private function maybe_create_table() {

		global $wpdb;

		$table_exists = $wpdb->get_results("SHOW TABLES LIKE '$this->table_name';");

		if( empty($table_exists) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $this->table_name (
				post_type varchar(32) NOT NULL,
				level varchar(32) NOT NULL,
				target_user_id bigint(20) NOT NULL,
				initiating_user_id bigint(20) NOT NULL,
				ref_id bigint(20) NOT NULL,
				amount bigint(20) NOT NULL,
				note varchar(255),
				timestamp TIMESTAMP NOT NULL
			) $charset_collate;";
			dbDelta( $sql );
		}

	}

	public function add( $data ) {

		global $wpdb;

		$this->maybe_create_table();

		$wpdb->insert(
			$this->table_name,
			array(
				'post_type'          => $data['post_type'],
				'level'              => $data['level'],
				'target_user_id'     => $data['target_user_id'],
				'initiating_user_id' => $data['initiating_user_id'],
				'ref_id'             => $data['ref_id'],
				'amount'             => $data['amount'],
				'note'               => !empty($data['note']) ? $data['note'] : '',
				'timestamp'          => date( 'Y-m-d H:i:s', time() ),
			),
			array(
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
			)
		);

	}

	public function before_profile_save( $post_id ) {

	    if( empty($_POST['acf']) )
	        return;

		if( 0 !== strpos($post_id, 'user_') )
			return;

		$user_id = str_replace( 'user_', '', $post_id );

		foreach( $this->keys as $key => $info )
			$this->values_to_check[$key] = (int) get_user_meta( $user_id, $key, true );

	}

	public function after_profile_save( $post_id ) {

	    if( empty($_POST['acf']) )
	        return;

		if( 0 !== strpos($post_id, 'user_') )
			return;

		$user_id = str_replace( 'user_', '', $post_id );

		foreach( $this->keys as $key => $info ) {
			$new_value = (int) get_user_meta( $user_id, $key, true );
			if( $this->values_to_check[$key] !== $new_value ) {

				$data = array(
					'post_type'          => $info['post_type'],
					'level'              => $info['level'],
					'target_user_id'     => $user_id,
					'initiating_user_id' => get_current_user_id(),
					'ref_id'             => '',
					'amount'             => $new_value - $this->values_to_check[$key],
				);

				HC()->logs->add( $data );
			}

		}

	}

}

return new HC_Logs();
