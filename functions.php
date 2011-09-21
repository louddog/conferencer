<?php

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

if (!function_exists('deep_trim')) {
	function deep_trim($var) {
		if (is_array($var)) {
			$array = array();
			foreach ($var as $key => $value) {
				$array[$key] = deep_trim($value);
			}
			return $array;
		} else return trim($var);
	}
}

if (!function_exists('output_classes')) {
	function output_classes($classes) {
		if (count($classes)) echo ' class="'.implode(' ', $classes).'"';
	}
}