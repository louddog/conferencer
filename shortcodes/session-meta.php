<?php

new Conferencer_Shortcode_Sesssion_Meta();
class Conferencer_Shortcode_Sesssion_Meta extends Conferencer_Shortcode {
	var $shortcode = 'session-meta';
	var $defaults = array(
		'show' => "time,speakers,room,track,sponsors",
		
		'show_time' => true,
		'show_speakers' => true,
		'show_room' => true,
		'show_track' => true,
		'show_sponsors' => true,

		'time_prefix' => "",
		'speaker_prefix' => "Presented by ",
		'room_prefix' => "Located in ",
		'track_prefix' => "In track ",
		'sponsor_prefix' => "Sponsored by ",

		'time_suffix' => "",
		'speaker_suffix' => "",
		'room_suffix' => "",
		'track_suffix' => "",
		'sponsor_suffix' => "",

		'date_format' => 'l, F j, Y',
		'time_format' => 'g:ia',
		'time_separator' => ' &ndash; ',
		
		'link_titles' => true,
		'link_speakers' => true,
		'link_room' => true,
		'link_track' => true,
		'link_sponsors' => true,
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
		if ($this->options['link_titles'] === false) {
			$this->options['link_speakers'] = false;
			$this->options['link_room'] = false;
			$this->options['link_track'] = false;
			$this->options['link_sponsors'] = false;
		}
		extract($this->options);
	
		global $post;
	
		$meta = array();
		foreach (explode(',', $show) as $type) {
			$type = trim($type);
			
			switch ($type) {
				case 'time':
					if ($time_slot_id = get_post_meta($post->ID, 'conferencer_time_slot', true)) {
						$starts = get_post_meta($time_slot_id, 'conferencer_starts', true);
						$ends = get_post_meta($time_slot_id, 'conferencer_ends', true);
						$html = date($date_format, $starts).", ".date($time_format, $starts).$time_separator.date($time_format, $ends);
						$meta[] = $time_prefix.$html.$time_suffix;
					}
					break;
		
				case 'speakers':
					if (count($speakers = Conferencer::get_speakers($post))) {
						$meta[] = $speaker_prefix.comma_separated($speakers, $link_speakers).$speaker_suffix;
					}
					break;
		

				case 'room':
					if ($room_id = get_post_meta($post->ID, 'conferencer_room', true)) {
						$html = get_the_title($room_id);
						if ($link_room) $html = "<a href='".get_permalink($room_id)."'>$html</a>";
						$meta[] = $room_prefix.$html.$room_suffix;
					}
					break;

				case 'track':
					if ($track_id = get_post_meta($post->ID, 'conferencer_track', true)) {
						$html = get_the_title($track_id);
						if ($link_track) $html = "<a href='".get_permalink($track_id)."'>$html</a>";
						$meta[] = $track_prefix.$html.$track_suffix;
					}
					break;

				case 'sponsors':
					if (count($sponsors = Conferencer::get_sponsors($post))) {
						$meta[] = $sponsor_prefix.comma_separated($sponsors, $link_sponsors).$sponsors_suffix;
					}
					break;
					
				default:
					$meta[] = "Unknown session attribute";
			}
		}

		return count($meta) ? "<p class='session-meta'>".implode("<br />", $meta)."</p>" : '';
	}
}