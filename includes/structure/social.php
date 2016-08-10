<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function hc_do_social() {

	$sites = array(
		'facebook'  => 'Facebook',
		'youtube'   => 'YouTube',
		'instagram' => 'Instagram',
	);
	?>

	<div class="social">
		<?php
		foreach( $sites as $slug => $name ) {
			$url = get_field( '_hc_' . $slug . '_url', 'option' );
			if( empty($url) )
				continue;

			?>
			<a href="<?php echo esc_url($url); ?>" title="<?php echo $name; ?>"><i class="ico-<?php echo $slug; ?>"></i></a>
			<?php
		}
		?>
	</div>
	<?php

}
