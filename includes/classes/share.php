<?php

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly



class HC_Share {
	public function display( $post_id ) {

		?>
		<button class="share-button btn btn-icon" id="postshare"><i class="ico-share"></i><span>Share</span></button>
        
        <?php $blog_id = get_current_blog_id();
			    if ($blog_id == 2) { $data_id = 'wid-vmsg7u0o'; }
			elseif ($blog_id == 3) { $data_id = 'wid-t7qeueqr'; }
			elseif ($blog_id == 4) { $data_id = 'wid-4t5arscc'; }
		?>
        <div class="pw-server-widget" data-id="<?php echo $data_id; ?>"></div>
        
        
		<?php

	}
}

return new HC_Share();
