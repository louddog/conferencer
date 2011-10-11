<?php

abstract class Conferencer_Shortcode {
	var $shortcode = 'conferencer_shortcode';
	var $defaults = array();
	var $options = array();
	
	function __construct() {
		add_shortcode($this->shortcode, array(&$this, 'pre_content'));
		add_action('save_post', array(&$this, 'save_post'));
		add_action('trash_post', array(&$this, 'trash_post'));
		
		register_activation_hook(CONFERENCER_REGISTER_FILE, array(&$this, 'activate'));
		register_deactivation_hook(CONFERENCER_REGISTER_FILE, array(&$this, 'deactivate'));
		
		global $wpdb;
		$wpdb->conferencer_shortcode_cache = $wpdb->prefix.'conferencer_shortcode_cache';
	}
	
	function pre_content($options) {
		$content = $this->get_cache($options);
		
		if (!$content) {
			$content = $this->content($options);
			$this->cache($options, $content);
		}
		
		return $content;
	}
	
	abstract function content($options);
	
	function set_options($options) {
		if (is_array($options)) {
			$new_options = array();
			foreach ($options as $key => $value) {
				if (is_string($value)) {
					if ($value == 'true') $value = true;
					if ($value == 'false') $value = false;
				}
				$new_options[$key] = $value;
			}
			$options = $new_options;
		}

		$this->options = shortcode_atts($this->defaults, $options);
	}
	
	function save_post($post_id) {
		if (!in_array(get_post_type($post_id), Conferencer::$post_types)) return;
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		$this->clear_cache();
	}
		
	function trash_post($post_id) {
		if (!in_array(get_post_type($post_id), Conferencer::$post_types)) return;
		$this->clear_cache();
	}
		
	function clear_cache() {
		global $wpdb;
		$wpdb->query("TRUNCATE $wpdb->conferencer_shortcode_cache");
	}
	
	function activate() {
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta("CREATE TABLE $wpdb->conferencer_shortcode_cache (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			shortcode text NOT NULL,
			options text NOT NULL,
			content text NOT NULL,
			UNIQUE KEY id(id)
		);");
	}
	
	function get_cache($options) {
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare(
			"SELECT content
			from $wpdb->conferencer_shortcode_cache
			where shortcode = %s
			and options = %s",
			$this->shortcode,
			serialize($options)
		));
	}
	
	function cache($options, $content) {
		global $wpdb;
		$wpdb->insert($wpdb->conferencer_shortcode_cache, array(
			'created' => current_time('mysql'),
			'shortcode' => $this->shortcode,
			'options' => serialize($options),
			'content' => $content,
		));
	}
}