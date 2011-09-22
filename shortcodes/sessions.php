<?php

new Conferencer_Shortcode_Sesssions();
class Conferencer_Shortcode_Sesssions extends Conferencer_Shortcode {
	var $shortcode = 'sessions';
	var $defaults = array(
		'post_id' => false,
		'post_ids' => false,
		'title' => false,
		'no_sessions_message' => "There aren't any sessions for this yet.",
		'link_post' => true,
		'link_sessions' => true,
		'title_tag' => 'h3',
	);

	static $post_types_with_sessions = array('speaker', 'room', 'time_slot', 'track', 'sponsor');
	
	function __construct() {
		parent::__construct();
		add_filter('the_content', array(&$this, 'add_to_page'));
	}
	
	function add_to_page($content) {
		if (in_array(get_post_type(), self::$post_types_with_sessions)) {
			$content .= do_shortcode('[sessions]');
		}
		return $content;
	}

	function content($options) {
		$this->set_options($options);
		extract($this->options);
		
		$post_ids = $post_ids ? explode(',', $post_ids) : array();
		if ($post_id) $post_ids = array_merge($post_ids, explode(',', $post_id));
		$post_ids = array_unique($post_ids);
		if (empty($post_ids) && $GLOBALS['post']->ID) $post_ids = array($GLOBALS['post']->ID);
		if (empty($post_ids)) return "[Shortcode error (sessions): No session ID provided.]";
		
		$content = '';
		
		$errors = array();
		foreach ($post_ids as $post_id) {
			$post = get_post($post_id);
			
			if (!$post) {
				$errors[] = "$post_id is not a valid post ID";
			} else if (!in_array(get_post_type($post), self::$post_types_with_sessions)) {
				$errors[] = "<a href='".get_permalink($post->ID)."'>$post->post_title</a> is not the correct type of post";
			}
		}
		if (count($errors)) return "[Shortcode errors (sessions): ".implode(', ', $errors)."]";
		
		$sessions = Conferencer::get_sessions($post_ids);
		Conferencer::attach_speakers($sessions);

		ob_start();

		if (!empty($sessions) || !empty($no_sessions_message)) { ?>

			<div class="session-list">
				<<?php echo $title_tag; ?>>
					<?php if (!$title) { ?>
						Sessions for
						<?php
							$titles = array();
							foreach ($post_ids as $post_id) {
								$html = get_the_title($post_id);
								if ($link_post) $html = "<a href='".get_permalink($post_id)."'>$html</a>";
								$titles[] = $html;
							}
							echo implode_with_serial_and($titles);
						?>
					<?php } else echo $title; ?>
				</<?php echo $title_tag; ?>>

				<?php if (empty($sessions)) { ?>
					<p><?php echo $no_sessions_message; ?></p>
				<?php } else { ?>
					<ul>
						<?php foreach ($sessions as $session) { ?>
							<li>
								<?php
									$html = $session->post_title;
									if ($link_sessions) $html = "<a href='".get_permalink($session->ID)."'>$html</a>";
									echo $html;
								?>
							</li>
						<?php } ?>
					</ul>
				<?php } ?>
			</div>
			
		<?php }
		
		return ob_get_clean();
	}
}