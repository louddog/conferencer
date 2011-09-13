<div id="conferencer_settings" class="wrap">
	<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
		<?php wp_nonce_field('conferencer_priorities_nonce'); ?>
		
		<h2>Settings</h2>
		<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
		
		<p class="submit"><input type="submit" class="button-primary" value="Save Changes" /></p>
		
		<hr />
		
		<h3>Priority Ordering</h3>
		<p>Drag rows up and down to re-order posts' priority.</p>
		
		<div class="post_types">
			<?php foreach (self::$priority_post_types as $slug => $heading) {
				$query = new WP_Query(array(
					'post_type' => $slug,
					'posts_per_page' => -1, // get all
				));
			
				$posts = $query->posts;
				uasort($posts, array('Conferencer', 'order_sort'));
				?>
			
				<?php if (count($posts)) { ?>
					<div class="post_type">
						<h4><?php echo $heading; ?></h4>
						<table class="levels" cellspacing="0">
							<tbody class="rows">
								<?php foreach ($posts as $post) { ?>
									<tr class="ui-state-default">
										<td>
											<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>
											<input class="post-id" type="hidden" name="conferencer_<?php echo $slug; ?>_id[]" value="<?php echo $post->ID; ?>" />
										</td>

										<td><?php echo $post->post_title; ?></td>

										<td><a href="<?php echo admin_url('post.php?action=edit&post='.$post->ID); ?>">edit</a></td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div> <!-- .post_type -->
				<?php } // if?>
			<?php } // foreach ?>
		</div> <!-- .post_types -->
		
		<hr />

		<p class="submit"><input type="submit" class="button-primary" value="Save Changes" /></p>
	</form>
</div>