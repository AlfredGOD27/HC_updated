<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Ratings {
	public function __construct() {

		global $wpdb;

		$this->table_name = $wpdb->prefix . 'ratings';

		add_action( 'wp_ajax_hc_ajax_set_rating', array($this, 'ajax_set_rating') );

	}

	public function get_item_rating_info( $post_id ) {

		global $wpdb;

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT rating
				FROM $this->table_name
				WHERE post_id = %d
				",
				$post_id
			)
		);

		if( empty($result) )
			return false;

		$ratings = array();
		foreach( $result as $row )
			$ratings[] = $row->rating;

		return array(
			'average' => array_sum($ratings) / count($ratings),
			'count'   => count($ratings),
		);

	}

	public function get_user_rating_for_item( $post_id, $user_id ) {

		global $wpdb;

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT rating
				FROM $this->table_name
				WHERE post_id = %d
				AND user_id = %d
				LIMIT 1
				",
				$post_id,
				$user_id
			)
		);

		return !empty($result) ? absint($result[0]->rating) : false;

	}

	public function display( $item_id ) {

		$rating = $this->get_item_rating_info( $item_id );

		echo '<div class="star-rating-container">';
			if( false !== $rating ) {
				echo '<div class="star-rating" title="' . sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating['average'] ) . '">';
					echo '<span style="width:' . ( ( $rating['average'] / 5 ) * 100 ) . '%"><strong class="rating">' . $rating['average'] . '</strong> ' . __( 'out of 5', 'woocommerce' ) . '</span>';
				echo '</div>';

				echo '<span>' . number_format($rating['average'], 1) . ' Based on ' . $rating['count'] . ' ' . _n( 'review', 'reviews', $rating['count'] ) . '</span>';
			} else {
				?>
				<div class="star-rating"></div>
				<span>Be the first to review</span>
				<?php
			}
		echo '</div>';

	}

	public function display_button( $post_id, $icon_only = false ) {

		$post_id = absint($post_id);

		if( is_user_logged_in() ) {
			$current_rating = $this->get_user_rating_for_item( $post_id, get_current_user_id() );
			?>
			<nav class="button-nav button-nav-above ratings-nav">
				<button class="calendar-button btn btn-icon">
					<i class="ico-star-o"></i>
					<?php
					if( !$icon_only ) {
						?>
						<span>Rate</span>
						<?php
					}
					?>
				</button>

				<div class="sub">
					<label for="rating">Your Rating</label>
					<select name="rating" id="rating" data-item_id="<?php echo $post_id; ?>">
						<option value="">Rate&hellip;</option>
						<option value="5" <?php selected(5, $current_rating, true ); ?>>Perfect</option>
						<option value="4" <?php selected(4, $current_rating, true ); ?>>Good</option>
						<option value="3" <?php selected(3, $current_rating, true ); ?>>Average</option>
						<option value="2" <?php selected(2, $current_rating, true ); ?>>Not that bad</option>
						<option value="1" <?php selected(1, $current_rating, true ); ?>>Very Poor</option>
					</select>
				</div>
			</nav>
			<?php
		} else {
			?>
			<button class="calendar-button btn btn-icon open-popup-link" data-mfp-src="#login-popup">
				<i class="ico-star-o"></i>
				<?php
				if( !$icon_only ) {
					?>
					<span>Rate</span>
					<?php
				}
				?>
			</button>
			<?php
		}

	}

	private function maybe_create_table() {

		global $wpdb;

		$table_exists = $wpdb->get_results("SHOW TABLES LIKE '$this->table_name';");

		if( empty($table_exists) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $this->table_name (
				post_id bigint(20) NOT NULL,
				user_id bigint(20) NOT NULL,
				rating tinyint NOT NULL,
				timestamp TIMESTAMP
			) $charset_collate;";
			dbDelta( $sql );
		}

	}

	private function upsert_rating( $post_id, $user_id, $rating ) {

		global $wpdb;

		$this->maybe_create_table();

		// Delete old rating
		$wpdb->delete(
			$this->table_name,
			array(
				'post_id' => $post_id,
				'user_id' => $user_id,
			),
			array(
				'%d',
				'%d',
			)
		);

		$wpdb->insert(
			$this->table_name,
			array(
				'post_id'   => $post_id,
				'user_id'   => $user_id,
				'rating'    => $rating,
				'timestamp' => date( 'Y-m-d H:i:s', time() ),
			),
			array(
				'%d',
				'%d',
				'%d',
				'%s',
			)
		);

	}

	public function item_can_be_rated( $item ) {

		if( !in_array( $item->post_type, array('listing'), true ) )
			return false;

		if( 'publish' !== $item->post_status )
			return false;

		return true;

	}

	public function ajax_set_rating() {

		$result = array(
			'status'  => '',
			'message' => '',
		);

		if( !is_user_logged_in() ) {
			$result['status']  = 'error';
			$result['message'] = 'You must login to rate items.';
			echo json_encode($result);
			wp_die();
		}

		$user_id = get_current_user_id();

		if(
			empty($_POST['rating']) ||
			empty($_POST['item_id'])
		) {
			$result['status']  = 'error';
			$result['message'] = 'Incomplete information.';
			echo json_encode($result);
			wp_die();
		}

		$rating = absint($_POST['rating']);
		if( $rating < 1 || $rating > 5 ) {
			$result['status']  = 'error';
			$result['message'] = 'Invalid rating.';
			echo json_encode($result);
			wp_die();
		}

		$item_id = absint($_POST['item_id']);
		$item    = get_post( $item_id );
		if( empty($item) ) {
			$result['status']  = 'error';
			$result['message'] = 'Item not found.';
			echo json_encode($result);
			wp_die();
		}

		if( !$this->item_can_be_rated( $item ) ) {
			$result['status']  = 'error';
			$result['message'] = 'Item cannot be rated.';
			echo json_encode($result);
			wp_die();
		}

		$this->upsert_rating( $item_id, $user_id, $rating );

		$result['status'] = 'success';
		echo json_encode($result);
		wp_die();

	}
}

return new HC_Ratings();
