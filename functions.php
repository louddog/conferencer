<?php

if (!function_exists('robustAtts')) {
		function robustAtts($atts) {
		if (!is_array($atts)) return $atts;
		$new_atts = array();
		foreach ($atts as $key => $value) {
			if (is_string($value) && $value == 'true') $value = true;
			if (is_string($value) && $value == 'false') $value = false;
			$new_atts[$key] = $value;
		}
		return $new_atts;
	}
}

if (!function_exists('comma_sep_links')) {
	function comma_sep_links($posts) {
		if (!is_array($posts)) return '';
		$links = array();
		foreach ($posts as $post) {
			$links[] = "<a href='".get_permalink($post->ID)."'>$post->post_title</a>";
		}
		return implode(', ', $links);
	}
}

if (!function_exists('comma_sep_titles')) {
	function comma_sep_titles($posts) {
		if (!is_array($posts)) return '';
		$titles = array();
		foreach ($posts as $post) {
			$titles[] = $post->post_title;
		}
		return implode(', ', $titles);
	}
}

if (!function_exists('deep_empty')) {
	function deep_empty($var) {
		if (is_array($var)) {
			foreach ($var as $value) {
				if (!deep_empty($value)) return false;
			}
			return true;
		} else return empty($var);
	}
}