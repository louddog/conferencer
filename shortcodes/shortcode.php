<?php

abstract class Conferencer_Shortcode {
	var $shortcode = 'conferencer_shortcode';
	var $defaults = array();
	var $options = array();
	
	function __construct() {
		add_shortcode($this->shortcode, array(&$this, 'shortcode'));
		add_action('save_post', array(&$this, 'save_post'));
		add_action('trash_post', array(&$this, 'trash_post'));
		
		register_activation_hook(CONFERENCER_REGISTER_FILE, array(&$this, 'activate'));
		register_deactivation_hook(CONFERENCER_REGISTER_FILE, array(&$this, 'deactivate'));
		
		global $wpdb;
		$wpdb->conferencer_shortcode_cache = $wpdb->prefix.'conferencer_shortcode_cache';
	}
		
	function shortcode($options) {
		$this->options = shortcode_atts($this->defaults, $options);	
		$this->prep_options();
		
		if (!$content = $this->get_cache()) {
			$content = $this->content();
			$this->cache($content);
		}
		
		return $content;
	}
	
	function prep_options() {
		foreach ($this->options as $key => $value) {
			if (is_string($value)) {
				if ($value == 'true') $this->options[$key] = true;
				if ($value == 'false') $this->options[$key] = false;
			}
		}
	}
	
	abstract function content();
	
	// Caching ----------------------------------------------------------------
	
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
	
	function save_post($post_id) {
		if (!in_array(get_post_type($post_id), Conferencer::$post_types)) return;
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		self::clear_cache();
	}
		
	function trash_post($post_id) {
		if (!in_array(get_post_type($post_id), Conferencer::$post_types)) return;
		self::clear_cache();
	}
		
	function get_cache() {
		if (!get_option('conferencer_caching')) return false;
		
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare(
			"SELECT content
			from $wpdb->conferencer_shortcode_cache
			where shortcode = %s
			and options = %s",
			$this->shortcode,
			serialize($this->options)
		));
	}
	
	function cache($content) {
		global $wpdb;
		$wpdb->insert($wpdb->conferencer_shortcode_cache, array(
			'created' => current_time('mysql'),
			'shortcode' => $this->shortcode,
			'options' => serialize($this->options),
			'content' => $content,
		));
	}
	
	static function get_all_cache() {
		global $wpdb;
		return $wpdb->get_results("SELECT shortcode, count(id) AS count FROM $wpdb->conferencer_shortcode_cache GROUP BY shortcode", OBJECT);
	}

	static function clear_cache() {
		global $wpdb;
		$wpdb->query("TRUNCATE $wpdb->conferencer_shortcode_cache");
	}
}