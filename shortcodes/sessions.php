<?php

new Conferencer_Sesssions_Shortcode();
class Conferencer_Sesssions_Shortcode {
	var $shortcode = 'sessions';
	static $post_types_with_sessions = array('speaker', 'room', 'time_slot', 'track', 'sponsor');
	
	function __construct() {
		add_filter('the_content', array(&$this, 'add_to_content'));
		add_shortcode($this->shortcode, array(&$this, 'content'));
	}
	
	function add_to_content($content) {
		if (in_array(get_post_type(), self::$post_types_with_sessions)) {
			$content .= do_shortcode('[sessions]');
		}
		return $content;
	}

	function content($options) {
		$options = shortcode_atts(array(
			'title' => "Sessions",
			'no_sessions_message' => "There aren't any sessions scheduled for this yet.",
			'show_speakers' => true,
			'link_sessions' => true,
			'link_speakers' => true,
		), robustAtts($options));

		extract($options);
	
		global $post;
		if (!in_array($post->post_type, self::$post_types_with_sessions)) {
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
									<?php
										$html = $session->post_title;
										if ($link_sessions) $html = "<a href='".get_permalink($session->ID)."'>$html</a>";
										if ($show_speakers) {
											if ($post->post_type == 'speaker') {
												$speakers = $session->speakers;
												foreach ($speakers as $id => $speaker) {
													if ($id == $post->ID) unset($speakers[$id]);
												}
												if (count($speakers)) $html .= ", with ".comma_seperated($speakers, $link_speakers);
											} else {
												$html .= ", by ".comma_seperated($session->speakers, $link_speakers);
											}
										}
										echo $html;
									?>
								</li>
							<?php } ?>
						</ul>
					<?php } ?>
				</div>
			<?php } ?>
	
		<?php return ob_get_clean();
	}
}