<?php

new Conferencer_Rengerate_Logos();
class Conferencer_Rengerate_Logos {
	function __construct() {
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('wp_ajax_regenerate_logo', array(&$this, 'ajax_regenerate_logo'));
	}
	
	function admin_menu() {
		add_submenu_page(
			'conferencer',
			"Regenerate Logos",
			"Regenerate Logos",
			'edit_posts',
			'conferencer_regenerate-logos',
			array(&$this, 'page')
		);
	}
	
	function page() {
		if (!current_user_can('edit_posts')) wp_die("You do not have sufficient permissions to access this page.");

		$query = new WP_Query(array(
			'post_type' => 'sponsor',
			'posts_per_page' => -1, // get all
		));
		
		$ids = array();
		foreach ($query->posts as $sponsor) $ids[] = $sponsor->ID;
		
		?>
		
		<div id="conferencer_regenerate_logos" class="wrap">
			<h2>Regenerate Logos</h2>
			
			<p>When you upload images to WordPress, the system generates several versions of the image at different sizes.  These images are then used in various places in the site.  Conferencer encourages you to upload featured images for conference post types.  These can be used in your templates, for example to show head shots for speaker, or logos for companies.  Depending on your theme, certain sizes are specified for WordPress to generate, like a 75x75 image for thumbnails or a 120x80 image for an archive post image.  Conferencer allows you to define image sizes for a <a href="<?php echo admin_url('widgets.php'); ?>">sponsor slideshow widget</a>.  The problem is that WordPress only generates files when you upload them.  So if change the slideshow image sizes, then any logos previously uploaded do not have the correctly sized image file generated.</p>
			<p>This page allows you to regenerate your images based on the current image size settings.  So, if you have made recent changes to size settings, you'll need to regenerate your images now.  Don't worry, if you do not run this program, WordPress will still show an image.  It just might not be the size you want until you regenerate.</p>
			
			<?php if (count($ids)) { ?>
				
				<p class="submit"><input type="submit" class="button-primary" name="conferencer_regenerate_logos" id="conferencer_regenerate_logos_button" value="Regenerate" /></p>
				<div id="conferencer_regenerate_logos_console"><!-- JS --></div>
				
				<script> conferencer_logo_regeneration_ids = [<?php echo implode(',', $ids); ?>]; </script>
				
			<?php } else { ?>
				
				<p>You'll need to create some sponsors before you can resize their logos.</p>
				
			<?php } ?>
				
		</div>
		
		<?php
	}
	
	function ajax_regenerate_logo() {
		@error_reporting(0); // Don't break the JSON result
		@set_time_limit(900); // 5 minutes per image
		
		header('Content-type: application/json');
		
		$titleLink = "<a href='".admin_url('post.php?action=edit&post='.$_REQUEST['id'])."'>".get_the_title($_REQUEST['id'])."</a>: ";
		
		if (Conferencer::regenerate_logo($_REQUEST['id'])) {
			die(json_encode(array('success' => $titleLink." successfully resized")));
		} else {
			die(json_encode(array('error' => $titleLink.Conferencer::$regenerate_logo_error)));
		}
	}
}