<?php

new Conferencer_Shortcode_Speaker_Meta();
class Conferencer_Shortcode_Speaker_Meta extends Conferencer_Shortcode {
	var $shortcode = 'speaker_meta';
	var $defaults = array(
		'post_id' => false,
		
		'show' => "title,company",
		
		'title_prefix' => "",
		'company_prefix' => "",

		'title_suffix' => "",
		'company_suffix' => "",
		
		'link_companies' => true,
	);

	var $buttons = array('speaker_meta');

	function add_to_page($content) {
		if (get_post_type() == 'speaker') {
			$meta = function_exists('conferencer_speaker_meta')
					? conferencer_speaker_meta($post)
					: do_shortcode('[speaker_meta]');
			$content = $meta.$content;
		}
		return $content;
	}

	function prep_options() {
		parent::prep_options();
		
		if (!$this->options['post_id'] && isset($GLOBALS['post'])) {
			$this->options['post_id'] = $GLOBALS['post']->ID;
		}
	}
	
	function content() {
		extract($this->options);
	
		$post = get_post($post_id);
		if (!$post) return "[Shortcode error (speaker_meta): Invalid post_id.  If not used within a speaker page, you must provide a speaker ID using 'post_id'.]";
		if ($post->post_type != 'speaker') {
			if ($post_id) return "[Shortcode error (speaker_meta): <a href='".get_permalink($post_id)."'>$post->post_title</a> (ID: $post_id, type: $post->post_type) is not a speaker.]";
			else return "[Shortcode error (speaker_meta): This post is not a speaker.  Maybe you meant to supply a speaker using post_id.]";
		}
		
		Conferencer::add_meta($post);

		$meta = array();
		foreach (explode(',', $show) as $type) {
			$type = trim($type);
			
			switch ($type) {
				case 'title':
					$html = $post->title;
					if ($link_title) $html = "<a href='".get_permalink($post->ID)."'>$html</a>";
					$meta[] = "<span class='title'>".$title_prefix.$html.$title_suffix."</span>";
					break;
				
				case 'company':
					if (count($companies = Conferencer::get_posts('company', $post->company))) {
						$html = comma_separated_post_titles($companies, $link_companies);
						$meta[] = "<span class='companies'>".$company_prefix.$html.$company_suffix;
					}
					break;

				default:
					$meta[] = "Unknown speaker attribute";
			}
		}

		return count($meta) ? "<p class='speaker_meta'>".implode("<br />", $meta)."</p>" : '';
	}
}