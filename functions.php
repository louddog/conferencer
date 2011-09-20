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

if (!function_exists('comma_seperated')) {
	function comma_seperated($posts, $link = true, $serial_and = true) {
		if (!is_array($posts)) return '';
		
		$items = array();
		$count = 0;
		foreach ($posts as $post) {
			$item = $post->post_title;
			if ($link) $item = "<a href='".get_permalink($post->ID)."'>$item</a>";
			if ($serial_and && ++$count > 1 && $count == count($posts)) $item = " and $item";
			$items[] = $item;
		}
		
		return implode(', ', $items);
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