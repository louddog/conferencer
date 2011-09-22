<?php

new Conferencer_Shortcode_Sesssions();
class Conferencer_Shortcode_Sesssions extends Conferencer_Shortcode {
	var $shortcode = 'sessions';
	var $defaults = array(
		'post_id' => false,
		'post_ids' => false,
		'title' => false,
		'no_sessions_message' => "There aren't any sessions scheduled for this yet.",
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
		if (empty($post_ids) && $GLOBAL['post']->ID) $post_id = $GLOBAL['post']->ID;
		if (empty($post_ids)) return "[Shortcode error (sessions): No session ID provided.]";
		
		$content = '';

		foreach ($post_ids as $post_id) {
			$post = get_post($post_id);
			
			if (!$post) {
				$content .= "[Shortcode error (sessions): $post_id is not a valid post ID.]";
			} else if (!in_array(get_post_type($post), self::$post_types_with_sessions)) {
				$content .= "[Shortcode error (sessions): <a href='".get_permalink($post->ID)."'>$post->post_title</a> is not the correct type of post.  It is of type $post->post_type.  If not used within a Conferencer page of type speaker, room, time_slot, track, or sponsor), you must provide a post ID using 'post_id'.]";
			} else {
				$sessions = Conferencer::get_sessions($post->ID);
				Conferencer::attach_speakers($sessions);

				ob_start(); ?>

					<?php if (!empty($sessions) || !empty($no_sessions_message)) { ?>
						<div class="session-list">
							<<?php echo $title_tag; ?>>
								<?php
									if (!$title) {
										echo "Sessions ";

										switch ($post->post_type) {
											case 'speaker': echo 'with'; break;
											case 'room': echo 'in'; break;
											case 'time_slot': echo 'at'; break;
											case 'track': echo 'in'; break;
											case 'sponsor': echo 'sponsored by'; break;
											default: echo "for";
										}
										
										$html = $post->post_title;
										if ($link_post) $html = "<a href='".get_permalink($post->ID)."'>$html</a>";
										echo " ".$html;
									} else echo $title;
								?>
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
					<?php } ?>

				<?php $content .= ob_get_clean();
			}
		}
		
		return $content;
	}
}