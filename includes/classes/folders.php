<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Folders {
	public function __construct() {

		$this->slug   = 'folder';
		$this->editor = new HC_Post_Type_Editor('folder', array('add', 'edit'), 'HC_Folder_Editor' );

		add_action( 'init', array($this, 'register_post_type') );
		add_action( 'wp', array($this, 'init_single') );
		add_action( 'wp_ajax_hc_ajax_add_item_to_folder', array($this, 'ajax_add_item_to_folder') );

	}

	public function register_post_type() {

		register_post_type(
			'folder',
			array(
				'labels' => array(
					'name'               => __( 'Folders', 'post type general name', CHILD_THEME_TEXT_DOMAIN ),
					'singular_name'      => __( 'Folder', 'post type singular name', CHILD_THEME_TEXT_DOMAIN ),
					'add_new'            => __( 'Add New', 'custom post type item', CHILD_THEME_TEXT_DOMAIN ),
					'add_new_item'       => __( 'Add New Folder', CHILD_THEME_TEXT_DOMAIN ),
					'edit'               => __( 'Edit', CHILD_THEME_TEXT_DOMAIN ),
					'edit_item'          => __( 'Edit Folder', CHILD_THEME_TEXT_DOMAIN ),
					'new_item'           => __( 'New Folder', CHILD_THEME_TEXT_DOMAIN ),
					'view_item'          => __( 'View Folder', CHILD_THEME_TEXT_DOMAIN ),
					'search_items'       => __( 'Search Folders', CHILD_THEME_TEXT_DOMAIN ),
					'not_found'          => __( 'Nothing found in the database.', CHILD_THEME_TEXT_DOMAIN ),
					'not_found_in_trash' => __( 'Nothing found in Trash', CHILD_THEME_TEXT_DOMAIN ),
					'parent_item_colon'  => '',
				),
				'public'    => true,
				'show_ui'   => 101028 === get_current_user_id() && current_user_can('manage_options'),
				'menu_icon' => 'dashicons-portfolio',
				'rewrite'   => array('slug' => $this->slug),
				'supports'  => array('title', 'author'),
		 	)
		);

	}

	public function display_add_button( $post_id, $icon_only = false, $use_modal = false ) {

		$post_id = absint($post_id);

		switch( get_post_type( $post_id ) ) {
			case 'event':
				$label = 'Save Event';
				break;
			case 'listing':
				$label = 'Save Venue';
				break;
			default:
				$label = 'Save Article';
				break;
		}

		if( is_user_logged_in() ) {
			$folder_ids = $this->get_user_folder_ids( get_current_user_id() );

			$add_url = add_query_arg(
				array(
					'add_post_id' => $post_id,
				),
				$this->editor->get_add_url()
			);

			$added = false;
			foreach( $folder_ids as $folder_id ) {
				$items = $this->get_items_in_folder( $folder_id );
				if( in_array($post_id, $items, true) ) {
					$added = true;
					break 1;
				}
			}

			$modal_html = '';
			if( $use_modal ) {
				ob_start();
				?>
				<div class="bookmarks-popup">
					<ul>
						<?php
						foreach( $folder_ids as $folder_id ) {
							$items = $this->get_items_in_folder( $folder_id );

							?>
							<li class="<?php echo in_array($post_id, $items, true) ? 'added' : ''; ?>">
								<a href="#" class="add-to-folder" data-item_id="<?php echo $post_id; ?>" data-folder_id="<?php echo $folder_id; ?>">
									<span class="name"><?php echo get_the_title($folder_id); ?></span>
									<i class="ico-check"></i>
									<span class="count"><?php echo count($items); ?></span>
								</a>
							</li>
							<?php
						}
						?>
					</ul>
					<a href="<?php echo $add_url; ?>" class="btn add-new">Create New</a>
				</div>
				<?php
				$modal_html = ob_get_clean();
			}

			?>
			<div class="bookmarks-button-container <?php echo $icon_only ? 'icon-only' : ''; ?>">
				<nav class="button-nav favorites-nav">
					<button class="bookmarks-button btn btn-icon <?php echo $use_modal ? 'use-modal' : ''; ?>" data-modal-html="<?php echo esc_attr($modal_html); ?>">
						<i class="<?php echo $added ? 'ico-heart-filled' : 'ico-heart'; ?>"></i>
						<?php
						if( !$icon_only ) {
							?>
							<span><?php echo $label; ?></span>
							<?php
						}
						?>
					</button>

					<?php
					if( !$use_modal ) {
						?>
						<div class="sub">
							<ul>
								<?php
								$i = 1;
								foreach( $folder_ids as $folder_id ) {
									$items = $this->get_items_in_folder( $folder_id );

									?>
									<li class="<?php echo $i > 3 ? 'hide' : ''; ?> <?php echo in_array($post_id, $items, true) ? 'added' : ''; ?>">
										<a href="#" class="add-to-folder" data-item_id="<?php echo $post_id; ?>" data-folder_id="<?php echo $folder_id; ?>">
											<span class="name"><?php echo get_the_title($folder_id); ?></span>
											<i class="ico-check"></i>
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
							<a href="<?php echo $add_url; ?>" class="btn add-new">Create New</a>
						</div>
						<?php
					}
					?>
				</nav>
			</div>
			<?php
		} else {
			?>
			<div class="bookmarks-button-container <?php echo $icon_only ? 'icon-only' : ''; ?>">
				<button class="bookmarks-button btn btn-icon open-popup-link" data-mfp-src="#login-popup">
					<i class="ico-heart"></i>
					<?php
					if( !$icon_only ) {
						?>
						<span><?php echo $label; ?></span>
						<?php
					}
					?>
				</button>
			</div>
			<?php
		}

	}

	public function create_default_folders_for_user( $user_id ) {

		$folders = array();

		$folders[] = array(
			'name'        => 'Favourites',
			'description' => 'Save articles to come back to again and again!',
			'icon'        => 'heart',
		);

		$folders[] = array(
			'name'        => 'Reading List',
			'description' => 'Don’t have time to read it now, save it here for later!',
			'icon'        => 'book',
		);

		$folders[] = array(
			'name'        => 'Itineraries',
			'description' => 'Planning a trip? Save your favourite travel articles here!',
			'icon'        => 'globe',
		);

		foreach( $folders as $folder ) {
			$args = array(
				'post_title'   => $folder['name'],
				'post_content' => $folder['description'],
				'post_status'  => 'publish',
				'post_author'  => $user_id,
				'post_type'    => 'folder',
			);
			$post_id = wp_insert_post( $args );

			if( isset($folder['icon']) )
				update_post_meta( $post_id, '_hc_folder_icon', $folder['icon'] );

			update_post_meta( $post_id, '_hc_folder_is_public', 'No' );
			update_post_meta( $post_id, '_hc_folder_is_curated', 'No' );

		}

	}

	public function is_public( $folder_id ) {

		$is_public = get_post_meta( $folder_id, '_hc_folder_is_public', true );

		return 'Yes' === $is_public;

	}

	public function can_edit( $folder_id ) {

		if( current_user_can( 'manage_options' ) )
			return true;

		$folder = get_post( $folder_id );
		if( (int) $folder->post_author === (int) get_current_user_id() )
			return true;

		return false;

	}

	public function can_view( $folder_id ) {

		if( $this->can_edit($folder_id) )
			return true;

		return $this->is_public( $folder_id );

	}

	public function get_user_folder_ids( $user_id, $public_only = false ) {

		$args = array(
			'post_type'      => 'folder',
			'posts_per_page' => -1,
			'author'         => $user_id,
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if( $public_only ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_hc_folder_is_public',
					'value' => 'Yes',
				),
			);
		}

		$folders = get_posts( $args );

		$folders = array_map( 'absint', $folders );

		return $folders;

	}

	public function get_user_curated_folder_ids( $user_id ) {

		$args = array(
			'post_type'      => 'folder',
			'posts_per_page' => -1,
			'author__not_in' => $user_id,
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'   => '_hc_folder_is_public',
					'value' => 'Yes',
				),
				array(
					'key'   => '_hc_folder_is_curated',
					'value' => 'Yes',
				),
			),
		);

		$folders = get_posts( $args );

		$folders = array_map( 'absint', $folders );

		return $folders;

	}

	public function get_items_in_folder( $folder_id ) {

		$item_ids = get_post_meta( $folder_id, '_hc_folder_item_ids', true );

		return !empty($item_ids) ? $item_ids : array();

	}

	public function item_can_be_bookmarked( $item_id ) {

		$item = get_post( $item_id );
		if( empty($item) )
			return false;

		if( !in_array( $item->post_type, array('post', 'event', 'listing'), true ) )
			return false;

		if( 'publish' !== $item->post_status )
			return false;

		return true;

	}

	public function add_item_to_folder( $item_id, $folder_id ) {

		$item_id = absint($item_id);

		// Make sure item is a valid post object
		$post = get_post( $item_id );
		if(
			empty($post) ||
			!in_array( $post->post_type, array('post', 'event', 'listing'), true )
		)
			return;

		$items   = get_post_meta( $folder_id, '_hc_folder_item_ids', true );
		$items   = !empty($items) ? array_map( 'absint', $items ) : array();
		$items[] = $item_id;
		$items   = array_unique($items);
		update_post_meta( $folder_id, '_hc_folder_item_ids', $items );

	}

	public function ajax_add_item_to_folder() {

		$result = array(
			'status'  => '',
			'message' => '',
		);

		if( !is_user_logged_in() ) {
			$result['status']  = 'error';
			$result['message'] = 'You must login to save items.';
			echo json_encode($result);
			wp_die();
		}

		$user_id = get_current_user_id();

		if(
			empty($_POST['folder_id']) ||
			empty($_POST['item_id'])
		) {
			$result['status']  = 'error';
			$result['message'] = 'Incomplete information.';
			echo json_encode($result);
			wp_die();
		}

		$folder_id = absint($_POST['folder_id']);
		$folder    = get_post( $folder_id );
		if( empty($folder) ) {
			$result['status']  = 'error';
			$result['message'] = 'Folder not found.';
			echo json_encode($result);
			wp_die();
		}

		if( (int) $user_id !== (int) $folder->post_author ) {
			$result['status']  = 'error';
			$result['message'] = 'You don\'t have permission to edit this folder.';
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

		if( !$this->item_can_be_bookmarked( $item_id ) ) {
			$result['status']  = 'error';
			$result['message'] = 'Item cannot be bookmarked.';
			echo json_encode($result);
			wp_die();
		}

		$this->add_item_to_folder( $item_id, $folder_id );

		$result['status'] = 'success';
		echo json_encode($result);
		wp_die();

	}

	public function init_single() {

		global $post;

		if( !is_singular('folder') )
			return;

		remove_action( 'genesis_loop', 'genesis_do_loop' );
		add_action( 'wpseo_robots', array($this, 'noindex') );

		if( !$this->is_public($post->ID) && !$this->can_edit( $post->ID ) ) {
			// Not authorized
			$url = get_bloginfo('url');
			wp_redirect($url);
			exit;
		} else {
			// Authorized
			HC()->profiles->user_id = $post->post_author;
			HC()->profiles->user    = get_user_by( 'id', $post->post_author );

			if( isset($_GET['add']) )
				HC()->messages->add( 'success', 'Folder added.' );

			add_action( 'wp_enqueue_scripts', array(HC()->profiles, 'load_assets') );
			add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
			add_filter( 'body_class', array(HC()->profiles, 'body_classes') );
			add_action( 'genesis_loop', array(HC()->profiles, 'display_heading') );
			add_action( 'genesis_loop', array(HC()->messages, 'display') );
			add_action( 'genesis_loop', array($this, 'display_single') );
		}

	}

	public function noindex() {

		return 'noindex,nofollow';

	}

	public function display_single() {

		global $post;

		$is_own_folder = (int) get_current_user_id() === (int) $post->post_author;
		$folders       = $this->get_user_folder_ids( $post->post_author, !$is_own_folder );

		?>
		<div class="clearfix">
			<aside class="one-fourth first folders-list">
				<?php
				if( !empty($folders) ) {
					?>
					<h4>Folders:</h4>

					<ul>
						<?php
						foreach( $folders as $folder_id ) {
							?>
							<li>
								<a href="<?php echo get_permalink( $folder_id ); ?>" class="<?php echo $folder_id === $post->ID ? 'current' : ''; ?>"><?php echo get_the_title($folder_id); ?></a>
							</li>
							<?php
						}
						?>
					</ul>

					<?php
				}

				if( $is_own_folder ) {
					?>
					<h4><a href="<?php echo $this->editor->get_add_url(); ?>"><i class="ico-plus"></i> Create New Folder</a></h4>
					<?php
				}
				?>
			</aside>

			<div class="three-fourths">
				<header class="folder-header">
					<h1><?php the_title(); ?></h1>

					<?php
					if( $this->can_edit( $post->ID ) ) {
						?>
						<a href="<?php echo $this->editor->get_edit_url( $post->ID ); ?>" class="edit-folder-link">Edit</a>
						<?php
					}
					?>
				</header>

				<div class="clearfix">
					<?php
					$items = $this->get_items_in_folder( $post->ID );

					$i = 1;
					foreach( $items as $item_id ) {
						$item = get_post( $item_id );

						echo 1 === $i % 2 ? '<div class="one-half first">' : '<div class="one-half">';
							HC()->archives->display_entry( $item_id, 'medium' );
						echo '</div>';
						++$i;
					}
					?>
				</div>
			</div>
		</div>
		<?php

	}

}

return new HC_Folders();
