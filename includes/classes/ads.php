<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Ads {
	public function __construct() {

		$this->ads = array();
		add_action( 'wp', array($this, 'setup_applicable_ads') );
		add_action( 'wp_head', array($this, 'head_script'), 99 );

	}

	private function ad_field_to_array( $field ) {

		if( empty($field[0]['head_code']) || empty($field[0]['body_code']) )
			return false;

		return array(
			'head' => $field[0]['head_code'],
			'body' => $field[0]['body_code'],
		);

	}

	public function setup_applicable_ads() {

		global $post;

		if( is_singular('page') ) {
			switch( get_page_template_slug($post->ID) ) {
				case 'page_templates/page_home.php':
					// Home Ads
					$ads = array(
						'mpu-1'         => '_hc_home_mpu_1',
						'mpu-2-desktop' => '_hc_home_mpu_2_desktop',
						'mpu-2-mobile'  => '_hc_home_mpu_2_mobile',
						'leaderboard-1' => '_hc_home_leaderboard',
					);

					foreach( $ads as $position => $key ) {
						$ads    = get_field( $key );
						$result = $this->ad_field_to_array( $ads );
						if( false !== $result )
							$this->ads[$position] = $result;
					}
					break;
				case 'page_templates/page_calendar.php':
					// Calendar Ads
					$ads = array(
						'leaderboard-1' => '_hc_events_leaderboard',
					);
					foreach( $ads as $position => $key ) {
						$ads    = get_field( $key );
						$result = $this->ad_field_to_array( $ads );
						if( false !== $result )
							$this->ads[$position] = $result;
					}
					break;
			}
		} elseif( is_search() ) {
			// Search Ads
			$ads = get_field( '_hc_search_mpu_1', 'option' );
			$ad  = $this->ad_field_to_array( $ads );
			if( false !== $ad )
				$this->ads['mpu-1'] = $ad;
			$ads_2 = get_field( '_hc_search_mpu_2', 'option' );
			$ad_2  = $this->ad_field_to_array( $ads_2 );
			if( false !== $ad_2 )
				$this->ads['mpu-2'] = $ad_2;
		} elseif( is_author() ) {
			// Author Ads
			$ads = get_field( '_hc_author_mpu_1', 'option' );
			$ad  = $this->ad_field_to_array( $ads );
			if( false !== $ad )
				$this->ads['mpu-1'] = $ad;
			$ads_2 = get_field( '_hc_author_mpu_2', 'option' );
			$ad_2  = $this->ad_field_to_array( $ads_2 );
			if( false !== $ad_2 )
				$this->ads['mpu-2'] = $ad_2;
		} elseif( is_singular('post') ) {
			// Post Ads
			$ads = array(
				'mpu-1' => '_hc_mpu_1',
				'mpu-2' => '_hc_mpu_2',
			);

			foreach( $ads as $position => $key ) {
				$ads    = get_field( $key );
				$result = $this->ad_field_to_array( $ads );
				if( false !== $result ) {
					$this->ads[$position] = $result;
				} else {
					$term = HC()->utilities->get_primary_term( $post->ID, 'category' );
					if( !empty($term) ) {
						$ads    = get_field( $key, $term );
						$result = $this->ad_field_to_array( $ads );
						if( false !== $result ) {
							$this->ads[$position] = $result;
						} elseif( $term->parent > 0 ) {
							$parent = get_term_by( 'id', $term->parent, $term->taxonomy );
							$ads    = get_field( $key, $parent );
							$result = $this->ad_field_to_array( $ads );
							if( false !== $result )
								$this->ads[$position] = $result;
						}
					}
				}
			}
		} elseif( is_archive() ) {
			// Archive Ads
			$term = get_queried_object();

			$ads = array(
				'mpu-1' => '_hc_mpu_1',
				'mpu-2' => '_hc_mpu_2',
			);

			foreach( $ads as $position => $key ) {
				$ads    = get_field( $key, $term );
				$result = $this->ad_field_to_array( $ads );
				if( false !== $result ) {
					$this->ads[$position] = $result;
				} elseif( $term->parent > 0 ) {
					$parent = get_term_by( 'id', $term->parent, $term->taxonomy );
					$ads    = get_field( $key, $parent );
					$result = $this->ad_field_to_array( $ads );
					if( false !== $result )
						$this->ads[$position] = $result;
				}
			}
		}

	}

	public function head_script() {

		if( empty($this->ads) )
			return;

		ob_start();
		?>
		<script>
			var googletag = googletag || {};
			googletag.cmd = googletag.cmd || [];
			(function() {
				var gads = document.createElement('script');
				gads.async = true;
				gads.type = 'text/javascript';
				var useSSL = 'https:' == document.location.protocol;
				gads.src = (useSSL ? 'https:' : 'http:') + '//www.googletagservices.com/tag/js/gpt.js';
				var node = document.getElementsByTagName('script')[0];
				node.parentNode.insertBefore(gads, node);
			})();

			<?php
			foreach( $this->ads as $ad )
				echo $ad['head'];
			?>
		</script>

		<?php
		$output = ob_get_clean();
		echo preg_replace( '/\s+/', ' ', $output ) . "\n";

	}

	private function get_ad_in_position( $position ) {

		if( isset($this->ads[$position]) )
			return $this->ads[$position];

		return false;

	}

	public function get_ad_container( $position ) {

		$ad = $this->get_ad_in_position( $position );
		if( empty($ad) )
			return;

		return preg_replace( '/\s+/', ' ', $ad['body'] ) . "\n";

	}

}

return new HC_Ads();
