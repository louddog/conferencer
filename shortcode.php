<?php

abstract class Conferencer_Shortcode {
	var $shortcode = 'conferencer_shortcode';
	var $defaults = array();
	var $options = array();
	
	function __construct() {
		add_shortcode($this->shortcode, array(&$this, 'content'));
	}
	
	abstract function content($options);
	
	function set_options($options) {
		if (is_array($options)) {
			$new_options = array();
			foreach ($options as $key => $value) {
				if (is_string($value) && $value == 'true') $value = true;
				if (is_string($value) && $value == 'false') $value = false;
				$new_options[$key] = $value;
			}
			$options = $new_options;
		}

		$this->options = shortcode_atts($this->defaults, $options);
	}
}