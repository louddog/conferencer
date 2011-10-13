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
				'type' => 'date-time',
				'label' => "Start Time",
			),
			'ends' => array(
				'type' => 'date-time',
				'label' => "End Time",
			),
			'non_session' => array(
				'type' => 'boolean',
				'label' => "Non Session",
			),
			'link' => array(
				'type' => 'text',
				'label' => "Link",
			),
		));
	}
	
	function options($post, $modified = array()) {
		$query = new WP_Query(array(
			'post_type' => 'time_slot',
			'posts_per_page' => 1,
			'meta_key' => 'conferencer_starts',
			'order' => 'ASC',
			'orderby' => 'meta_value_num',
		));
		
		$this->earliest_time_slot_date = $query->post_count ? get_post_meta($query->posts[0]->ID, 'conferencer_starts', true) : false;
		
		parent::options($post, $modified);
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
		
		switch (str_replace('conferencer_time_slot_', '', $column)) {
			case 'day':
				if ($post->starts) echo date('n/j/y', $post->starts).' &ndash; '.date('D.', $post->starts);
				break;
			case 'time':
				if ($post->starts) echo date('g:ia', $post->starts);
				if ($post->ends) echo ' &ndash; '.date('g:ia', $post->ends);
				break;
		}
	}
}