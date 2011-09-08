<?php

new Conferencer_Sponsor();
class Conferencer_Sponsor extends Conferencer_CustomPostType {
	var $slug = 'sponsor';
	var $archive_slug = 'sponsors';
	var $singular = "Sponsor";
	var $plural = "Sponsors";
	
	function set_options() {
		parent::set_options();
	
		$sponsor_level_query = new WP_Query(array(
			'post_type' => 'sponsor_level',
			'posts_per_page' => -1, // get all
		));
		
		$level_options = array();
		foreach ($sponsor_level_query->posts as $sponsor_level) {
			$level_options[$sponsor_level->ID] = $sponsor_level->post_title;
		}

		$this->options = array_merge($this->options, array(
			'url' => array(
				'type' => 'text',
				'label' => "URL",
			),
			'level' => array(
				'type' => 'select',
				'label' => "Level",
				'options' => $level_options,
			),
		));
	}
		
	function columns($columns) {
		$columns = parent::columns($columns);
		$columns['conferencer_sponsor_level'] = "Level";
		$columns['conferencer_sponsor_url'] = "URL";
		return $columns;
	}
	
	function column($column) {
		parent::column($column);
		
		global $post;
		
		switch (str_replace('conferencer_sponsor_', '', $column)) {
			case 'level':
				$sponsor_level_query = new WP_Query(array(
					'post_type' => 'sponsor_level',
					'posts_per_page' => -1, // get all
				));

				$sponsor_levels = array();

				foreach ($sponsor_level_query->posts as $sponsor_level) {
					$sponsor_levels[$sponsor_level->ID] = $sponsor_level;
				}
				
				echo $sponsor_levels[get_post_meta($post->ID, 'conferencer_level', true)]->post_title;
				break;
			case 'url':
				$url = get_post_meta($post->ID, 'conferencer_url', true);
				echo "<a href='$url' target='_blank'>$url</a>";
				break;
		}
	}
}