<?php

// TODO: add details to session page, speaker, track, time, room, sponsor

new Conferencer_Session();
class Conferencer_Session extends Conferencer_CustomPostType {
	var $slug = 'session';
	var $archive_slug = 'sessions';
	var $singular = "Session";
	var $plural = "Sessions";
	
	function set_options() {
		parent::set_options();
	
		$this->options = array_merge($this->options, array(
			'keynote' => array(
				'type' => 'boolean',
				'label' => "Keynote",
			),
			'room' => array(
				'type' => 'select',
				'label' => "Room",
				'options' => array(), // set below
			),
			'time_slot' => array(
				'type' => 'select',
				'label' => "Time Slot",
				'options' => array(), // set below
			),
			'track' => array(
				'type' => 'select',
				'label' => "Track",
				'options' => array(), // set below
			),
			'speakers' => array(
				'type' => 'multi-select',
				'label' => "Speakers",
				'options' => array(), // set below
			),
			'sponsors' => array(
				'type' => 'multi-select',
				'label' => "Sponsors",
				'options' => array(), // set below
			),
		));

		foreach ($this->options as $key => $option) {
			$post_type = $key;
			if ($post_type == 'speakers') $post_type = 'speaker';
			if ($post_type == 'sponsors') $post_type = 'sponsor';
			
			$query = new WP_Query(array(
				'post_type' => $post_type,
				'posts_per_page' => -1, // show all
				'orderby' => 'title',
				'order' => 'ASC',
			));

			foreach ($query->posts as $item) {
				$text = '';
				
				if ($key == 'time_slot') {
					if (get_post_meta($item->ID, 'conferencer_non_session', true)) continue;
					
					$starts = floatVal(get_post_meta($item->ID, 'conferencer_starts', true));
					$ends = floatVal(get_post_meta($item->ID, 'conferencer_ends', true));
					
					if ($starts) {
						$text = date('n/j/y, g:iA', $starts);
						if ($ends) $text .= ' &ndash; '.date('g:iA', $ends);
					} else $text = 'unscheduled';
					$text .= ' ('.get_the_title($item->ID).')';
				} else $text = get_the_title($item->ID);
				
				$this->options[$key]['options'][$item->ID] = $text;
			}
		}
	}

	function detail_trash($post_id) {
		parent::detail_trash($post_id);
		
		$post = get_post($post_id);
		$types = array(
			'speaker' => 'conferencer_speakers',
			'sponsor' => 'conferencer_sponsors',
		);
		
		$messages = get_option('conferencer_messages', array());
		
		if (array_key_exists($post->post_type, $types)) {
			$query = new WP_Query(array(
				'post_type' => 'session',
				'posts_per_page' => -1, // get all
			));
			
			foreach ($query->posts as $session) {
				foreach ($types as $type => $option) {				
					$oldIDs = unserialize(get_post_meta($session->ID, $option, true));
					$newIDs = array();
				
					if (is_array($oldIDs)) {
						foreach ($oldIDs as $oldID) if ($oldID != $post_id) $newIDs[] = $oldID;
						update_post_meta($session->ID, $option, serialize($newIDs));
						if (count($oldIDs) != count($newIDs)) $messages[] = "Removed this $type from <a href='post.php?post=$session->ID&action=edit' target='_blank'>$session->post_title</a>.";
					}
				}
			}
		}

		if (in_array($post->post_type, array_keys($this->options))) {
			$meta_key = 'conferencer_'.$post->post_type;
			
			$query = new WP_Query(array(
				'post_type' => 'session',
				'posts_per_page' => -1, // get all
				'meta_query' => array(
					array(
						'key' => $meta_key,
						'value' => $post->ID,
					),
				),
			));
			
			foreach ($query->posts as $session) {
				update_post_meta($session->ID, $meta_key, false);
				$messages[] = "Removed this ".$this->options[$post->post_type]['label']." from <a href='post.php?post=$session->ID&action=edit' target='_blank'>$session->post_title</a>.";
			}
		}
		
		update_option('conferencer_messages', $messages);
	}
	
	function columns($columns) {
		$columns = parent::columns($columns);
		$columns['conferencer_session_keynote'] = "Keynote";
		$columns['conferencer_session_speakers'] = "Speakers";
		$columns['conferencer_session_track'] = "Track";
		$columns['conferencer_session_room'] = "Room";
		$columns['conferencer_session_time_slot'] = "Time Slot";
		$columns['conferencer_session_sponsors'] = "Sponsors";
		return $columns;
	}
	
	var $column_session_cache = array();
	function column($column) {
		parent::column($column);
		
		global $post;
		
		switch (str_replace('conferencer_session_', '', $column)) {
			case 'keynote':
				echo get_post_meta($post->ID, 'conferencer_keynote', true) ? "keynote" : "";
				break;
			case 'speakers':
				$speaker_ids = unserialize(get_post_meta($post->ID, 'conferencer_speakers', true));
				if (!$speaker_ids) $speaker_ids = array();
			
				$speaker_query = new WP_Query(array(
					'post_type' => 'speaker',
					'post_per_page' => -1, // get all
				));
				
				$speakerLinks = array();
				foreach ($speaker_query->posts as $speaker) {
					if (!in_array($speaker->ID, $speaker_ids)) continue;
					$speakerLinks[] =
						"<a href='post.php?action=edit&post=$speaker->ID'>".
						str_replace(' ', '&nbsp;', $speaker->post_title).
						"</a>";
				}
				
				echo implode(', ', $speakerLinks);
				break;
			case 'sponsors':
				$sponsor_ids = unserialize(get_post_meta($post->ID, 'conferencer_sponsors', true));
				if (!$sponsor_ids) $sponsor_ids = array();
			
				$sponsor_query = new WP_Query(array(
					'post_type' => 'sponsor',
					'post_per_page' => -1, // get all
				));
				
				$sponsorLinks = array();
				foreach ($sponsor_query->posts as $sponsor) {
					if (!in_array($sponsor->ID, $sponsor_ids)) continue;
					$sponsorLinks[] =
						"<a href='post.php?action=edit&post=$sponsor->ID'>".
						str_replace(' ', '&nbsp;', $sponsor->post_title).
						"</a>";
				}
				
				echo implode(', ', $sponsorLinks);
				break;
			case 'track':
				if ($id = intVal(get_post_meta($post->ID, 'conferencer_track', true))) {
					$related_post = get_post($id);
					echo "<a href='post.php?action=edit&post=$id'>$related_post->post_title</a>";
				}
				break;
			case 'room':
				if ($id = intVal(get_post_meta($post->ID, 'conferencer_room', true))) {
					$related_post = get_post($id);
					echo "<a href='post.php?action=edit&post=$id'>$related_post->post_title</a>";
				}
				break;
			case 'time_slot':
				if ($id = intVal(get_post_meta($post->ID, 'conferencer_time_slot', true))) {
					$related_post = get_post($id);
					$starts = floatVal(get_post_meta($related_post->ID, 'conferencer_starts', true));
					$ends = floatVal(get_post_meta($related_post->ID, 'conferencer_ends', true));
					
					echo "<a href='post.php?action=edit&post=$id'>";
					echo date('n/j/y', $starts);
					echo '<br />';
					echo date('g:ia', $starts);
					if ($ends) echo '&mdash;'.date('g:ia', $ends);
					echo "</a>";
				}

				break;
		}
	}
}