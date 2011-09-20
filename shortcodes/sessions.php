<?php

add_shortcode('sessions', 'conferencer_sessions_shortcode');

function conferencer_sessions_shortcode($options) {
	$options = shortcode_atts(array(
		'title' => "Sessions",
		'no_sessions_message' => "There aren't any sessions scheduled for this yet.",
		'show_speakers' => true,
		'link_sessions' => true,
		'link_speakers' => true,
	), robustAtts($options));

	extract($options);
	
	global $post;
	if (!in_array($post->post_type, Conferencer::$post_types_with_sessions)) {
		return "Error: [sessions] can only be used within Conferencer post types: speaker, room, time_slot, track, and sponsor.";
	}
	
	$sessions = Conferencer::get_sessions($post->ID);
	Conferencer::attach_speakers($sessions);
	
	ob_start(); ?>
	
		<?php if (!empty($sessions) || !empty($no_sessions_message)) { ?>
			<div class="session-list">
				<h2><?php echo $title; ?></h2>
				
				<?php if (empty($sessions)) { ?>
					<p><?php echo $no_sessions_message; ?></p>
				<?php } else { ?>
					<ul>
						<?php foreach ($sessions as $session) { ?>
							<li>
								<?php if ($link_sessions) { ?>
									<a href="<?php echo get_permalink($session->ID); ?>">
								<?php } ?>
									<?php echo $session->post_title; ?>
								<?php if ($link_sessions) { ?>
									</a>
								<?php } ?>
								
								<?php if ($show_speakers) { ?>
									<?php // TODO: this comma needs to be up against the title ?>
									, by <?php echo comma_seperated($session->speakers, $link_speakers); ?>
								<?php } ?>
							</li>
						<?php } ?>
					</ul>
				<?php } ?>
			</div>
		<?php } ?>
	
	<?php return ob_get_clean();
}