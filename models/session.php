<?php

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
			foreach (Conferencer::get_posts($key, false, 'title_sort') as $post) {
				$text = $post->post_title;
				
				if ($key == 'time_slot') {
					Conferencer::add_meta($post);
					if ($post->non_session) continue;
					if ($post->starts) {
						$text = date('n/j/y, g:iA', $post->starts);
						if ($post->ends) $text .= ' &ndash; '.date('g:iA', $post->ends);
					} else $text = 'unscheduled';
				}
				
				$this->options[$key]['options'][$post->ID] = $text;
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
					$oldIDs = get_post_meta($session->ID, $option, true);
					$newIDs = array();
				
					if (is_array($oldIDs)) {
						foreach ($oldIDs as $oldID) if ($oldID != $post_id) $newIDs[] = $oldID;
						update_post_meta($session->ID, $option, $newIDs);
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
				echo $post->keynote ? "keynote" : "";
				break;
			case 'speakers':
				$links = array();
				foreach (Conferencer::get_posts('speaker', $post->speakers) as $speaker) {
					$links[] =
						"<a href='post.php?action=edit&post=$speaker->ID'>".
						str_replace(' ', '&nbsp;', $speaker->post_title).
						"</a>";
				}
				
				echo implode(', ', $links);
				break;
			case 'sponsors':
				$links = array();
				foreach (Conferencer::get_posts('sponsor', $post->sponsors) as $sponsor) {
					$links[] =
						"<a href='post.php?action=edit&post=$sponsor->ID'>".
						str_replace(' ', '&nbsp;', $sponsor->post_title).
						"</a>";
				}
				
				echo implode(', ', $links);
				break;
			case 'track':
				if ($post->track) echo "<a href='post.php?action=edit&post=$post->track'>".get_the_title($post->track)."</a>";
				break;
			case 'room':
				if ($post->room) echo "<a href='post.php?action=edit&post=$post->room'>".get_the_title($post->room)."</a>";
				break;
			case 'time_slot':
				if ($post->time_slot) {
					$starts = floatVal(get_post_meta($post->time_slot, 'conferencer_starts', true));
					$ends = floatVal(get_post_meta($post->time_slot, 'conferencer_ends', true));
					
					echo "<a href='post.php?action=edit&post=$post->time_slot'>";
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