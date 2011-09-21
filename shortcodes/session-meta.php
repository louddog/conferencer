<?php

new Conferencer_Shortcode_Sesssion_Meta();
class Conferencer_Shortcode_Sesssion_Meta extends Conferencer_Shortcode {
	var $shortcode = 'session-meta';
	var $defaults = array(
	);

	function __construct() {
		parent::__construct();
		add_filter('the_content', array(&$this, 'add_to_page'));
	}
	
	function add_to_page($content) {
		if (get_post_type() == 'session') {
			$meta = function_exists('conferencer_session_meta')
					? conferencer_session_meta($post)
					: do_shortcode('[session-meta]');
			$content = $meta.$content;
		}
		return $content;
	}

	function content($options) {
		if (get_post_type() != 'session') return "Error: Shortcode: 'session-meta' can only be used within Conferencer Sessions.";

		$this->set_options($options);
		extract($this->options);
	
		global $post;
	
		ob_start(); 
		
		$html = array();
		
		// Time
		if ($time_slot_id = get_post_meta($post->ID, 'conferencer_time_slot', true)) {
			$starts = get_post_meta($time_slot_id, 'conferencer_starts', true);
			$ends = get_post_meta($time_slot_id, 'conferencer_ends', true);
			$html[] = date('l, F j, Y', $starts).", ".date('g:ia', $starts)." to ".date('g:ia', $ends);
		}
		
		// Speakers
		if (count($speakers = Conferencer::get_speakers($post))) {
			$html[] = "Presented by ".comma_separated($speakers);
		}
		
		// Room
		if ($room_id = get_post_meta($post->ID, 'conferencer_room', true)) {
			$html[] = "Located in <a href='".get_permalink($room_id)."'>".get_the_title($room_id)."</a>";
		}
		
		// Track
		if ($track_id = get_post_meta($post->ID, 'conferencer_track', true)) {
			$html[] = "In track <a href='".get_permalink($track_id)."'>".get_the_title($track_id)."</a>";
		}
		
		// Sponsors
		if (count($sponsors = Conferencer::get_sponsors($post))) {
			$html[] = "Sponsored by ".comma_separated($sponsors);
		}
		
		// Glue it together
		if (count($html)) {
			echo "<p class='session-meta'>".implode("<br />", $html)."</p>";
		}
		
		return ob_get_clean();
	}
}