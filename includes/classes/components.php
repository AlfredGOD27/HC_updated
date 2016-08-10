<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HC_Components {
	public function __construct() {

		add_action( 'init', array($this, 'register') );
		add_action( 'admin_head', array($this, 'admin_object') );
		add_shortcode( 'hc_component', array($this, 'shortcode') );

	}

	public function register() {

		register_post_type( 'component',
			array(
				'labels' => array(
					'name'               => __('Components', 'post type general name'),
					'singular_name'      => __('Component', 'post type singular name'),
					'add_new'            => __('Add New', 'custom post type item'),
					'add_new_item'       => __('Add New Component'),
					'edit'               => __( 'Edit' ),
					'edit_item'          => __('Edit Component'),
					'new_item'           => __('New Component'),
					'view_item'          => __('View Component'),
					'search_items'       => __('Search Components'),
					'not_found'          => __('Nothing found in the Database.'),
					'not_found_in_trash' => __('Nothing found in Trash'),
					'parent_item_colon'  => '',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'query_var'           => true,
				'has_archive'         => false,
				'capability_type'     => 'post',
				'hierarchical'        => false,
				'menu_icon'           => 'dashicons-grid-view',
				'supports'            => array('title'),
		 	)
		);

	}

	public function admin_object() {

		$args = array(
			'post_type'              => 'component',
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'update_post_meta_cache' => false,
		);
		$slides = get_posts($args);

		$list = array();
		foreach( $slides as $slide ) {
			$list[] = array(
				'text'  => $slide->post_title,
				'value' => $slide->ID,
			);
		}

		$list = array_values($list);

		?>
		<script>
			window.components = <?php echo json_encode($list); ?>
		</script>
		<?php

	}

	public function shortcode( $atts, $content ) {

		if( empty($atts['id']) )
			return;

		$component_id = absint($atts['id']);
		$type         = get_post_meta( $component_id, '_hc_component_type', true );

		switch( $type ) {
			case 'slideshow':
				$slides = get_field( '_hc_component_slides', $component_id );
				if( empty($slides) )
					return;

				$i    = 1;
				$html = '<div class="component-slideshow hide-no-js">';
					foreach( $slides as $slide ) {
						$html .= '<div>';
							$html .= '<figure class="wp-caption">';
								$html .= wp_get_attachment_image(
									$slide['image_id'],
									'featured'
								);

								if( !empty($slide['caption']) )
									$html .= '<figcaption class="wp-caption-text">' . sanitize_text_field($slide['caption']) . '</figcaption>';
							$html .= '</figure>';
						$html .= '</div>';
					}
				$html .= '</div>';

				return $html;
		}

	}

}

return new HC_Components();
