<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Trending {
	public function __construct() {

		add_action( 'admin_init', array($this, 'manage_token') );

	}

	public function get_token() {

		if( is_multisite() ) {
			switch_to_blog(2);
			$token = get_option( '_hc_google_token' );
			restore_current_blog();
		} else {
			$token = get_option( '_hc_google_token' );
		}

		return $token;

	}

	public function manage_token() {

		// Google API
		if( !class_exists('Google_Service_Analytics') ) {
			set_include_path( CHILD_DIR . '/vendor/google/apiclient/src' );

			if( !class_exists('Google_Client') ) {
				include_once 'Google/Client.php';
			}

			include_once 'Google/Service/Analytics.php';
		}

		$token = $this->get_token();
		if( !empty($token) )
			return;

		if( !current_user_can('manage_options' ) )
			return;

		if( 101028 !== (int) get_current_user_id() )
			return;

		$id     = get_option('options__hc_ga_client_id');
		$secret = get_option('options__hc_ga_client_secret');
		if( empty($id) || empty($secret) )
			return;

		$client = new Google_Client();
		$client->setApplicationName('Honeycombers');
		$client->setClientId($id);
		$client->setClientSecret($secret);
		$client->setRedirectUri( get_bloginfo('url') );
		$client->setAccessType('offline');
		$client->setApprovalPrompt('force');
		$client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));

		if( !isset($_GET['code']) ) {
			$authUrl = $client->createAuthUrl();
			echo '<a class="button" href="' . $authUrl . '">Authorize Google Account</a>';
		} else {
			$client->authenticate( $_GET['code'] );

			$token = $client->getAccessToken();
			update_option( '_hc_google_token', $token );
			exit;
		}

	}

	public function get_trending( $count = 5 ) {

		// Google API
		if( !class_exists('Google_Service_Analytics') ) {
			set_include_path( CHILD_DIR . '/vendor/google/apiclient/src' );

			if( !class_exists('Google_Client') ) {
				include_once 'Google/Client.php';
			}

			include_once 'Google/Service/Analytics.php';
		}

		$token      = $this->get_token();
		$profile_id = get_option('options__hc_ga_profile_id');
		if( empty($token) || empty($profile_id) )
			return;

		$id     = get_option('options__hc_ga_client_id');
		$secret = get_option('options__hc_ga_client_secret');

		if( empty( $id ) || empty( $secret ) )
			return;

		$client = new Google_Client();

		try {
			$client->setApplicationName('Honeycombers');
			$client->setClientId($id);
			$client->setClientSecret($secret);
			$client->setAccessToken($token);

			$analytics = new Google_Service_Analytics($client);
			$results   = $analytics->data_ga->get(
				'ga:' . $profile_id,
				date( 'Y-m-d', strtotime('-10 days') ),
				date( 'Y-m-d', current_time('timestamp') ),
				'ga:uniquePageviews',
				array(
					'dimensions'  => 'ga:pagePath,ga:pageTitle',
					'sort'        => '-ga:uniquePageviews',
					'max-results' => $count * 2,
				)
			);

			if( empty($results) )
				return;

			$rows = $results->getRows();

			$post_ids = array();
			foreach( $rows as $row ) {
				if( count($post_ids) >= $count )
					break 1;

				$path = explode('/', $row[0]);
				$path = array_filter($path);

				if( count($path) < 2 )
					continue;

				$slug = array_pop($path);

				$args = array(
					'post_type'      => 'post',
					'posts_per_page' => 1,
					'name'           => sanitize_title($slug),
					'fields'         => 'ids',
				);
				$posts = get_posts( $args );
				if( empty($posts) )
					continue;

				$post_ids[] = $posts[0];
			}

			return $post_ids;
		} catch( Google_ServiceException $e ) {
			$error = 'Google API Error code :' . $e->getCode() . "\n";
			$error .= 'Google API Error message: ' . $e->getMessage() . "\n";
			error_log( $error );
		} catch (Google_Exception $e) {
			error_log( 'An error occurred: (' . $e->getCode() . ') ' . $e->getMessage() . "\n" );
		}

	}

}

return new HC_Trending();
