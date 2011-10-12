<?php

new Conferencer_Shortcode_Sesssion_Meta();
class Conferencer_Shortcode_Sesssion_Meta extends Conferencer_Shortcode {
	var $shortcode = 'session_meta';
	var $defaults = array(
		'post_id' => false,
		
		'show' => "time,speakers,room,track,sponsors",
		
		'title_prefix' => "",
		'time_prefix' => "",
		'speakers_prefix' => "Presented by ",
		'room_prefix' => "Located in ",
		'track_prefix' => "In track ",
		'sponsors_prefix' => "Sponsored by ",

		'title_suffix' => "",
		'time_suffix' => "",
		'speaker_suffix' => "",
		'room_suffix' => "",
		'track_suffix' => "",
		'sponsor_suffix' => "",

		'date_format' => 'l, F j, Y',
		'time_format' => 'g:ia',
		'time_separator' => ' &ndash; ',
		
		'link_all' => true,
		'link_title' => true,
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
					: do_shortcode('[session_meta]');
			$content = $meta.$content;
		}
		return $content;
	}

	function content() {
		if ($this->options['link_all'] === false) {
			$this->options['link_title'] = false;
			$this->options['link_speakers'] = false;
			$this->options['link_room'] = false;
			$this->options['link_track'] = false;
			$this->options['link_sponsors'] = false;
		}
		extract($this->options);
	
		$post = $post_id ? get_post($post_id) : $GLOBALS['post'];
		if (!$post) return "[Shortcode error (session_meta): If not used within a session page, you must provide a session ID using 'post_id'.]";
		if (get_post_type($post) != 'session') {
			if ($post_id) return "[Shortcode error (session_meta): <a href='".get_permalink($post_id)."'>".get_the_title($post_id)."</a> (ID: $post_id, type: ".get_post_type($post_id).") is not a session.]";
			else return "[Shortcode error (session_meta): This post is not a session.  Maybe you meant to supply a session using post_id.]";
		}

		$meta = array();
		foreach (explode(',', $show) as $type) {
			$type = trim($type);
			
			switch ($type) {
				case 'title':
					$html = $post->post_title;
					if ($link_title) $html = "<a href='".get_permalink($post->ID)."'>$html</a>";
					$meta[] = "<span class='title'>".$title_prefix.$html.$title_suffix."</span>";
					break;
				
				case 'time':
					if ($time_slot_id = get_post_meta($post->ID, 'conferencer_time_slot', true)) {
						$starts = get_post_meta($time_slot_id, 'conferencer_starts', true);
						$ends = get_post_meta($time_slot_id, 'conferencer_ends', true);
						$html = date($date_format, $starts).", ".date($time_format, $starts).$time_separator.date($time_format, $ends);
						$meta[] = "<span class='time'>".$time_prefix.$html.$time_suffix."</span>";
					}
					break;
		
				case 'speakers':
					if (count($speakers = Conferencer::get_speakers($post))) {
						$html = comma_separated_post_titles($speakers, $link_speakers);
						$meta[] = "<span class='speakers'>".$speakers_prefix.$html.$speaker_suffix;
					}
					break;
		

				case 'room':
					if ($room_id = get_post_meta($post->ID, 'conferencer_room', true)) {
						$html = get_the_title($room_id);
						if ($link_room) $html = "<a href='".get_permalink($room_id)."'>$html</a>";
						$meta[] = "<span class='room'>".$room_prefix.$html.$room_suffix."</span>";
					}
					break;

				case 'track':
					if ($track_id = get_post_meta($post->ID, 'conferencer_track', true)) {
						$html = get_the_title($track_id);
						if ($link_track) $html = "<a href='".get_permalink($track_id)."'>$html</a>";
						$meta[] = "<span class='track'>".$track_prefix.$html.$track_suffix."</span>";
					}
					break;

				case 'sponsors':
					if (count($sponsors = Conferencer::get_sponsors($post))) {
						$html = comma_separated_post_titles($sponsors, $link_sponsors);
						$meta[] = "<span class='sponsors'>".$sponsors_prefix.$html.$sponsors_suffix."</span>";
					}
					break;
					
				default:
					$meta[] = "Unknown session attribute";
			}
		}

		return count($meta) ? "<p class='session_meta'>".implode("<br />", $meta)."</p>" : '';
	}
}