<?php

new Conferencer_TimeSlot();
class Conferencer_TimeSlot extends Conferencer_CustomPostType {
	var $slug = 'time_slot';
	var $archive_slug = 'time-slots';
	var $singular = "Time Slot";
	var $plural = "Time Slots";
	
	function set_options() {
		parent::set_options();
		
		$this->options = array_merge($this->options, array(
			'starts' => array(
				'type' => 'text',
				'label' => "Start Time",
			),
			'ends' => array(
				'type' => 'text',
				'label' => "End Time",
			),
			'no_sessions' => array(
				'type' => 'boolean',
				'label' => "No Sessions",
			),
			'link' => array(
				'type' => 'text',
				'label' => "Link",
			),
		));
	}
	
	function options($post, $modified = array()) {
		if ($starts = floatVal(get_post_meta($post->ID, 'conferencer_starts', true))) {
			$modified['conferencer_starts'] = date('n/j/y, g:ia', $starts);
		}
		
		if ($ends = floatVal(get_post_meta($post->ID, 'conferencer_ends', true))) {
			$modified['conferencer_ends'] = date('n/j/y, g:ia', $ends);
		}
		
		parent::options($post, $modified);
	}
	
	
	function save_post($post_id) {
		$_POST['conferencer_starts'] = strtotime($_POST['conferencer_starts']);
		$_POST['conferencer_ends'] = strtotime($_POST['conferencer_ends']);
		parent::save_post($post_id);
	}

	function columns($columns) {
		$columns = parent::columns($columns);
		$columns['conferencer_time_slot_day'] = "Day";
		$columns['conferencer_time_slot_time'] = "Time";
		$columns['conferencer_time_slot_session_count'] = "Sessions";
		return $columns;
	}
	
	function column($column) {
		parent::column($column);

		global $post;
		
		$starts = floatVal(get_post_meta($post->ID, 'conferencer_starts', true));
		$ends = floatVal(get_post_meta($post->ID, 'conferencer_ends', true));
				
		switch (str_replace('conferencer_time_slot_', '', $column)) {
			case 'day':
				if ($starts) echo date('n/j/y', $starts).' &ndash; '.date('D.', $starts);
				break;
			case 'time':
				if ($starts) echo date('g:ia', $starts);
				if ($ends) echo ' &ndash; '.date('g:ia', $ends);
				break;
		}
	}
}