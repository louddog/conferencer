<?php

new Conferencer_Speaker();
class Conferencer_Speaker extends Conferencer_CustomPostType {
	var $slug = 'speaker';
	var $archive_slug = 'speakers';
	var $singular = "Speaker";
	var $plural = "Speakers";
	
	var $speaker_cache = false;
	
	function set_options() {
		parent::set_options();
	
		$company_query = new WP_Query(array(
			'post_type' => 'company',
			'posts_per_page' => -1, // show all
			'orderby' => 'title',
			'order' => 'ASC',
		));
		
		$company_options = array();
		foreach ($company_query->posts as $company) {
			$company_options[$company->ID] = get_the_title($company->ID);
		}

		$this->options = array_merge($this->options, array(
			'title' => array(
				'type' => 'text',
				'label' => "Title",
			),
			'company' => array(
				'type' => 'text',
				'label' => "Company",
			),
		));
	}

	function columns($columns) {
		$columns = parent::columns($columns);
		$columns['title'] = "Name";
		$columns['conferencer_speaker_title'] = "Title";
		$columns['conferencer_speaker_company'] = "Company";
		$columns['conferencer_speaker_sessions'] = "Sessions";
		return $columns;
	}
	
	function column($column) {
		parent::column($column);
		
		global $post;
		
		switch (str_replace('conferencer_speaker_', '', $column)) {
			case 'title':
				echo get_post_meta($post->ID, 'conferencer_title', true);
				break;
			case 'company':
				if ($company_id = get_post_meta($post->ID, 'conferencer_company', true)) {
					echo "<a href='post.php?action=edit&post=$company_id'>".get_the_title($company_id)."</a>";
				}
				break;
			case 'sessions':
				if (!$this->speaker_cache) {
					$session_query = new WP_Query(array(
						'post_type' => 'session',
						'posts_per_page' => -1, // get all
					));

					$this->speaker_cache = array();
					foreach ($session_query->posts as $session) {
						$speakers = unserialize(get_post_meta($session->ID, 'conferencer_speakers', true));
						if (!$speakers) $speakers = array();
						foreach ($speakers as $speaker_id) {
							$this->speaker_cache[$speaker_id][] = $session->ID;
						}
					}
				}

				if (array_key_exists($post->ID, $this->speaker_cache)) {
					$sessions = array();
					foreach ($this->speaker_cache[$post->ID] as $session_id) {
						$sessions[] = "<a href='post.php?action=edit&post=$session_id'>".get_the_title($session_id)."</a>";
					}
					echo implode(', ', $sessions);
				}
				
				break;
		}
	}
}