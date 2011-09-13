<?php

new Conferencer_Rengerate_Logos();
class Conferencer_Rengerate_Logos {
	function __construct() {
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('wp_ajax_regenerate_logos', array(&$this, 'ajax_regenerate_logos'));
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
			
			<?php if (count($ids)) { ?>
				
				<p class="submit"><input type="submit" class="button-primary" name="conferencer_regenerate_logos" value="Regenerate" /></p>
				<div id="regenerate_logos_console"><!-- JS --></div>
				
				<script>
					jQuery(function($) {
						var ids = [<?php echo implode(',', $ids); ?>];

						function regenerateNextLogo() {
							var id = ids.shift();

							if (id) {
								$('#regenerate_logos_console').append('<br />resizing #' + id + ': ');
								$.post(ajaxurl, { action: "regenerate_logos", id: id }, function(response) {
									$('#regenerate_logos_console').append(response.success ? response.success : response.error);
									regenerateNextLogo();
								});
							} else {
								$('#regenerate_logos_console').append('<br />complete');
							}
						}

						$('[name=conferencer_regenerate_logos]').click(function() {
							regenerateNextLogo();
						});
					});
				</script>
				
			<?php } else { ?>
				
				<p>You'll need to create some sponsors before you can resize their logos.</p>
				
			<?php } ?>
				
		</div>
		
		<?php
	}
	
	function ajax_regenerate_logos() {
		@error_reporting(0); // Don't break the JSON result
		@set_time_limit(900); // 5 minutes per image
		
		header('Content-type: application/json');

		if (!current_user_can('manage_options')) die(json_encode(array('error' => "You do not have the correct permissions to resize logos.")));

		$id = $_REQUEST['id'];
		$sponsor = get_post($id);
		if (!$sponsor || 'sponsor' != $sponsor->post_type) die(json_encode(array('error' => "Failed resize: $id is an invalid sponsor ID.")));

		$thumbID = get_post_thumbnail_id($sponsor->ID);
		if (!$thumbID) die(json_encode(array('error' => "This sponsor does not have a thumbnail.")));

		$path = get_attached_file($thumbID);
		if (false === $path || !file_exists($path)) die(json_encode(array('error' => "The originally uploaded file cannot be found.")));

		$metadata = wp_generate_attachment_metadata($thumbID, $path);
		if (is_wp_error($metadata)) die(json_encode(array('error' => $metadata->get_error_message())));
		if (empty($metadata)) die(json_encode(array('error' => "Unknown logo resizing failure.")));

		// If this fails, then it just means that nothing was changed (old value == new value)
		wp_update_attachment_metadata($thumbID, $metadata);

		die(json_encode(array('success' => "The logo for ".get_the_title($sponsor->ID)." was successfully resized.")));
	}
}