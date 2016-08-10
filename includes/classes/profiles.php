<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use ZxcvbnPhp\Zxcvbn;

class HC_Profiles {
	public function __construct() {

		$this->base_url  = 'profile';
		$this->endpoints = array(
			'edit',
			'calendar-events',
			'reset-password',
			'logout',
		);

		add_action( 'init', array($this, 'rewrites'), 1 );
		add_action( 'wp', array($this, 'init'), 99 );

	}

	public function rewrites() {

		$page_id = get_option( 'page_on_front' );

		add_rewrite_tag( '%hc_profile_endpoint%', '([^&]+)' );

		add_rewrite_rule(
			'^' . $this->base_url . '/?$',
			'index.php?p=' . $page_id . '&hc_profile_endpoint=base',
			'top'
		);

		foreach( $this->endpoints as $endpoint ) {
			add_rewrite_rule(
				'^' . $this->base_url . '/' . $endpoint . '/?$',
				'index.php?p=' . $page_id . '&hc_profile_endpoint=' . $endpoint,
				'top'
			);
		}

	}

	public function init() {

		global $wp_query;

		$this->endpoint = get_query_var( 'hc_profile_endpoint' );
		if( empty($this->endpoint) )
			return;

		if( 'base' !== $this->endpoint && !in_array($this->endpoint, $this->endpoints, true) )
			return;

		$logged_in = is_user_logged_in();

		$wp_query->is_404 = false;
		status_header(200);

		add_action( 'template_include', array($this, 'do_seo') );

		add_action( 'wp_enqueue_scripts', array($this, 'load_assets') );
		add_filter( 'body_class', array($this, 'body_classes') );
		add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
		remove_action( 'genesis_before_loop', 'genesis_do_posts_page_heading' );
		remove_action( 'genesis_loop', 'genesis_do_loop' );

		if( isset($_GET['password_reset']) && $_GET['password_reset'] ) {
			// Welcome message
			HC()->messages->add( 'success', 'Your password has been reset. <a href="#login-popup" class="open-popup-link">Login?</a>' );
		}

		if( 'reset-password' === $this->endpoint ) {
			if( $logged_in ) {
				wp_redirect( $this->get_url() );
				exit;
			} else {
				$result = $this->handle_password_reset();
				add_action( 'genesis_loop', array(HC()->messages, 'display') );
				if( false !== $result )
					add_action( 'genesis_loop', array($this, 'display_password_reset_form') );

				return;
			}
		}

		if( !$logged_in ) {
			HC()->messages->add( 'error', 'You must <a href="#" class="open-popup-link" data-mfp-src="#login-popup">login</a> to edit your profile.' );
			add_action( 'genesis_loop', array(HC()->messages, 'display') );

			return;
		} else {
			$this->user_id = get_current_user_id();
			$this->user    = get_user_by( 'id', $this->user_id );
		}

		if( isset($_GET['event_added']) )
			HC()->messages->add( 'success', 'Your event has been submitted and is pending review.' );

		if( isset($_GET['listing_added']) )
			HC()->messages->add( 'success', 'Your listing has been submitted and is pending review.' );

		if( isset($_GET['deleted']) )
			HC()->messages->add( 'success', 'Item deleted.' );

		add_action( 'genesis_loop', array($this, 'display_heading') );
		add_action( 'genesis_loop', array(HC()->messages, 'display') );

		switch( $this->endpoint ) {
			case 'logout':
				$this->handle_logout();
				break;
			case 'base':
				add_action( 'genesis_loop', array($this, 'display_landing') );
				break;
			case 'edit':
				$this->form = new HC_Profile_Edit_Form( $this->user );
				add_action( 'genesis_loop', array($this, 'display_edit') );
				break;
			case 'calendar-events':
				add_action( 'genesis_loop', array($this, 'display_events') );
				break;
		}

	}

	public function get_url( $endpoint = false ) {

		$url = get_bloginfo( 'url' );
		$url = trailingslashit($url);
		$url .= $this->base_url . '/';

		if( false !== $endpoint && in_array( $endpoint, $this->endpoints, true ) )
			$url .= $endpoint . '/';

		return $url;

	}

	public function get_city_options() {

		return array(
			'Singapore',
			'Jakarta',
			'Bali',
		);

	}

	public function get_first_name( $user_id = false ) {

		if( false === $user_id ) {
			$user = $this->user;
		} else {
			$user = get_user_by( 'id', $user_id );
		}

		if( !empty($user->first_name))
			return $user->first_name;

		if( !empty($user->display_name))
			return $user->display_name;

		return $user->user_login;

	}

	public function get_full_name( $user_id = false ) {

		if( false === $user_id ) {
			if( empty($this->user) )
				return;

			$user = $this->user;
		} else {
			$user = get_user_by( 'id', $user_id );
		}

		if( empty($user) )
			return;

		if( !empty($user->first_name) && !empty($user->last_name) )
			return $user->first_name . ' ' . $user->last_name;

		if( !empty($user->display_name))
			return $user->display_name;

		return $user->user_login;

	}

	public function display_top_menu() {

		?>
		<nav class="button-nav user-menu">
			<button class="btn btn-icon">
				Hello, <?php echo HC()->profiles->get_first_name( get_current_user_id() ); ?>
				<i class="ico-arrow-down"></i>
			</button>

			<div class="sub">
				<div class="top">
					<a href="<?php echo $this->get_url(); ?>" class="btn btn-icon"><i class="ico-heart"></i> <span>Favorite List</span></a>
				</div>

				<div class="middle">
					<ul>
						<?php
						$folder_ids = HC()->folders->get_user_folder_ids( get_current_user_id() );
						$i          = 1;
						foreach( $folder_ids as $folder_id ) {
							$items = HC()->folders->get_items_in_folder( $folder_id );

							?>
							<li class="<?php echo $i > 3 ? 'hide' : ''; ?>">
								<a href="<?php echo get_permalink($folder_id); ?>">
									<span class="name"><?php echo get_the_title($folder_id); ?></span>
									<span class="count"><?php echo count($items); ?></span>
								</a>
							</li>
							<?php
							++$i;
						}

						if( $i > 4 ) {
							?>
							<li class="view-all">
								<button type="button" class="view-all btn btn-icon">
									<span>View All</span>
									<i class="ico-arrow-down"></i>
								</button>
							</li>
							<?php
						}
						?>
					</ul>

					<a href="<?php echo $this->get_url(); ?>" class="btn">Account Settings</a>
				</div>

				<div class="bottom">
					<a href="<?php echo $this->get_url('logout'); ?>">Log out</a>
				</div>
			</div>
		</nav>
		<?php

	}

	private function check_password_reset_key() {

		if( !isset($_GET['key']) || !isset($_GET['login']) )
			return false;

		$check_key = check_password_reset_key( $_GET['key'], $_GET['login'] );

		return empty($check_key) || is_wp_error($check_key) ? false : $check_key;

	}

	public function check_password_strength( $password, $is_admin ) {

		if( $is_admin ) {
			$zxcvbn   = new Zxcvbn();
			$strength = $zxcvbn->passwordStrength( $password );

			return $strength['score'] >= 3;
		} else {
			return true;
		}

	}

	private function do_password_reset( $user ) {

		// Reset PW action?
		if( !isset($_POST['pass1']) || !isset($_POST['pass2']) ) {
			HC()->messages->add( 'error', 'You must set and confirm a new password.' );
		} else {
			if( $_POST['pass1'] !== $_POST['pass2'] ) {
				HC()->messages->add( 'error', 'Your passwords don\'t match.' );
			} else {
				$require_strong = user_can( $user, 'edit_posts' );
				$strong         = $this->check_password_strength( $_POST['pass1'], $require_strong );
				if( !$strong ) {
					HC()->messages->add( 'error', 'You must choose a stronger password.' );
				} else {
					reset_password( $user, $_POST['pass1'] );

					$url = add_query_arg(
						array(
							'password_reset' => true,
						),
						HC()->profiles->get_url()
					);
					wp_redirect( $url );
					exit;
				}
			}
		}

	}

	private function handle_password_reset() {

		$user = $this->check_password_reset_key();

		if( false === $user ) {
			HC()->messages->add( 'error', 'Invalid password reset link. Please <a href="#password-popup" class="open-popup-link">try again</a>.' );

			return false;
		}

		if( isset($_POST['do_reset']) )
			$this->do_password_reset( $user );

	}

	public function display_password_reset_form() {

		$url = add_query_arg(
			array(
				'key'   => $_GET['key'],
				'login' => $_GET['login'],
			),
			HC()->profiles->get_url('reset-password')
		);
		?>
		<form action="<?php echo $url; ?>" method="post" autocomplete="off" class="hc-form one-half first">
			<input type="hidden" id="user_login" value="<?php echo esc_attr( $_GET['login'] ); ?>">
			<input type="hidden" name="rp_key" value="<?php echo esc_attr( $_GET['key'] ); ?>">

			<div class="field">
				<label for="pass1">New password</label>
				<input type="password" name="pass1" id="pass1" required>
			</div>

			<div class="field">
				<label for="pass2">Confirm new password</label>
				<input type="password" name="pass2" id="pass2" required>
			</div>

			<div class="form-footer">
				<p class="description">Password must be at least 8 characters, and contain at least one number and one symbol.</p>

				<button type="submit" name="do_reset" class="btn">Reset Password</button>
			</div>
		</form>
		<?php

	}

	private function handle_logout() {

		wp_logout();

		wp_redirect( get_bloginfo('url') );
		exit;

	}

	public function do_seo() {

		// If WordPress SEO is installed, overwrite everything. Otherwise, just replace the <title>
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if( is_plugin_active('wordpress-seo/wp-seo.php') || is_plugin_active('wordpress-seo-premium/wp-seo-premium.php') ) {
			add_action( 'wpseo_robots', array($this, 'noindex') );
			add_filter( 'wpseo_canonical', array($this, 'seo_canonical') );
			add_filter( 'wpseo_title', array($this, 'seo_title') );
		} else {
			add_filter( 'wp_title', array($this, 'seo_title') );
		}

		return get_query_template( 'index' );

	}

	public function noindex() {

		return 'noindex,nofollow';

	}

	public function seo_canonical( $canonical ) {

		return $this->get_url( $this->endpoint );

	}

	public function seo_title( $title ) {

		$titles = get_option( 'wpseo_titles' );
		$title  = str_replace( '%%title%%', $this->get_full_name(), $titles['title-folder'] );

		return wpseo_replace_vars( $title, array() );

	}

	public function load_assets() {

		$use_production_assets = genesis_get_option('hc_production_on');
		$use_production_assets = !empty($use_production_assets);

		$assets_version = genesis_get_option('hc_assets_version');
		$assets_version = !empty($assets_version) ? absint($assets_version) : null;

		$stylesheet_dir = get_stylesheet_directory_uri();

		$src = $use_production_assets ? '/build/css/profiles.min.css' : '/build/css/profiles.css';
		wp_enqueue_style( 'hc-profiles', $stylesheet_dir . $src, array('hc'), $assets_version );

		$src = $use_production_assets ? '/build/js/forms.min.js' : '/build/js/forms.js';
		wp_enqueue_script( 'hc-forms', $stylesheet_dir . $src, array('jquery'), $assets_version, true );

	}

	public function body_classes( $classes ) {

		$classes[] = 'profile';
		$classes[] = 'profile-' . $this->endpoint;

		return $classes;

	}

	public function display_heading() {

		$is_own_profile = (int) $this->user_id === (int) get_current_user_id();

		?>
		<heading class="profile-heading clearfix">
			<div class="left two-thirds first">
				<?php
				echo get_avatar( $this->user_id, 120 );
				?>

				<div class="profile-welcome">
					<?php
					if( $is_own_profile ) {
						?>
						<span>Welcome</span>
						<h1><a href="<?php echo $this->get_url(); ?>"><?php echo $this->get_full_name(); ?></a></h1>
						<nav class="profile-nav">
							<a href="<?php echo $this->get_url('edit'); ?>">Edit profile</a>
						</nav>
						<?php
					} else {
						?>
						<h2><?php echo $this->get_full_name(); ?></h2>
						<?php
					}
					?>
				</div>
			</div>

			<?php
			if( $is_own_profile && 'base' === $this->endpoint ) {
				$url = get_bloginfo('url');
				$url = untrailingslashit($url);
				$url .= '/how-to-save-articles-videos-and-pretty-much-everything-you-see-on-honeycombers-to-view-later/';

				?>
				<div class="right one-third">
					<div class="profile-bookmarks-info">
						<i class="ico-heart"></i>
						<p class="orange">Put articles, listings or any editorial content you like in your profile
by clicking on the heart or save icon</p>
						<p class="black"><a href="<?php echo $url; ?>">Click through to learn more about what you can do with your account</a></p>
					</div>
				</div>
				<?php
			}
			?>
		</heading>
		<?php

	}

	public function display_landing() {

		$boxes = array();

		$events = $this->get_user_event_ids( $this->user_id );
		if( !empty($events) ) {
			$boxes[] = array(
				'name'        => 'Event Submission',
				'description' => 'Manage your events submissions',
				'url'         => $this->get_url('calendar-events'),
				'icon'        => 'calendar',
				'class'       => 'invert-colors',
			);
		}

		$curated_folders = HC()->folders->get_user_curated_folder_ids( $this->user_id );
		$folders         = HC()->folders->get_user_folder_ids( $this->user_id );
		$folders         = array_merge( $curated_folders, $folders );
		foreach( $folders as $folder_id ) {
			$folder = get_post( $folder_id );

			$items = HC()->folders->get_items_in_folder( $folder_id );

			$boxes[] = array(
				'id'          => $folder_id,
				'name'        => $folder->post_title,
				'description' => $folder->post_content,
				'url'         => get_permalink($folder_id),
				'icon'        => get_post_meta( $folder_id, '_hc_folder_icon', true ),
				'image_id'    => isset($items[0]) && has_post_thumbnail($items[0]) ? get_post_thumbnail_id($items[0]) : '',
			);
		}

		$boxes[] = array(
			'name'        => 'Create Your Own Folder',
			'description' => 'Ideas for a night out, a dinner date or a quick getaway!',
			'url'         => HC()->folders->editor->get_add_url(),
			'icon'        => 'plus',
		);

		?>
		<div class="profile-boxes clearfix">
			<?php
			$i = 1;
			foreach( $boxes as $box ) {
				$class = isset($box['class']) ? $box['class'] : '';
				echo 1 === $i % 3 ? '<div class="one-third first box ' . $class . '">' : '<div class="one-third box ' . $class . '">';
					?>
					<a href="<?php echo $box['url']; ?>" class="top">
						<?php
						if( !empty($box['image_id']) ) {
							echo wp_get_attachment_image( $box['image_id'], 'archive' );
						} else {
							?>
							<div class="placeholder"></div>
							<?php
						}

						if( !empty($box['icon']) )
							echo '<i class="ico-' . $box['icon'] . '"></i>';
						?>
					</a>

					<div class="bottom">
						<h3><a href="<?php echo $box['url']; ?>"><?php echo $box['name']; ?></a></h3>

						<?php
						if( !empty($box['description']) ) {
							?>
							<p><?php echo $box['description']; ?></p>
							<?php
						}
						?>

						<?php
						if(
							isset($box['id']) &&
							HC()->folders->is_public( $box['id'] )
						) {
							HC()->share->display( $box['id'] );
						}
						?>
					</div>
					<?php
				echo '</div>';
				++$i;
			}
			?>
		</div>
		<?php

	}

	public function display_edit() {

		?>
		<h2 class="profile-page-title">Edit Profile</h2>
		<?php
		$this->form->display_form();

	}

	private function get_user_event_ids( $user_id ) {

		$args = array(
			'post_type'  => 'event',
			'meta_query' => array(
				array(
					'key'     => '_hc_event_submitter_id',
					'value'   => $user_id,
					'compare' => 'NUMERIC',
				),
			),
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		);

		return get_posts( $args );

	}

	public function display_events() {

		$user_id = get_current_user_id();

		?>
		<div class="clearfix">
			<aside class="one-fourth first folders-list">
				<?php
				$folders = HC()->folders->get_user_folder_ids( $user_id, false );
				if( !empty($folders) ) {
					?>
					<h4><a href="<?php echo $this->get_url('calendar-events'); ?>">Calendar Listings</a></h4>

					<hr>

					<h4>Folders:</h4>

					<ul>
						<?php
						foreach( $folders as $folder_id ) {
							?>
							<li>
								<a href="<?php echo get_permalink( $folder_id ); ?>"><?php echo get_the_title($folder_id); ?></a>
							</li>
							<?php
						}
						?>
					</ul>

					<?php
				}

				?>
				<h4><a href="<?php echo HC()->folders->editor->get_add_url(); ?>"><i class="ico-plus"></i> Create New Folder</a></h4>
			</aside>

			<div class="three-fourths">
				<header class="folder-header">
					<h1>Calendar Listings</h1>
				</header>

				<?php
				$items = $this->get_user_event_ids( $user_id );

				if( !empty($items) ) {
					?>
					<table class="responsive">
						<thead>
							<tr>
								<th>Event Title</th>
								<th>Type</th>
								<th>Date</th>
								<th>Status</th>
								<th>Hero Listing</th>
								<th>Editor's Pick</th>
							</tr>
						</thead>

						<tbody>
							<?php
							foreach( $items as $item_id ) {
								$status = get_post_status($item_id);
								?>
								<tr>
									<th>
										<?php
										$title = HC()->entry->get_headline_title($item_id);
										if( 'publish' === $status ) {
											echo '<a href="' . get_permalink($item_id) . '">' . $title . '</a>';
										} else {
											echo $title;
										}
										?>
									</th>

									<td data-title="Type">
										<?php
										$level = get_post_meta( $item_id, '_hc_event_level', true );
										echo ucfirst($level);
										?>
									</td>

									<td data-title="Date">
										<?php
										$date = HC()->events->get_event_date_info( $item_id );
										echo HC()->events->get_date_string( $date, 'F j' );
										?>
									</td>

									<td data-title="Status">
										<?php
										echo ucfirst($status);
										?>
									</td>

									<td data-title="Hero Listing">
									</td>

									<td data-title="Editor's Pick">
									</td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
					<?php
				} else {
					HC()->messages->add_and_display( 'info', 'You have submitted no events.' );
				}
				?>

				<a href="<?php echo HC()->events->editor->get_add_url(); ?>" class="add-more-link">Add more events ></a>
			</div>
		</div>
		<?php

	}

}

return new HC_Profiles();
