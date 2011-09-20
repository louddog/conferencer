<?php
/*
Plugin Name: Conferencer
Description: Creates a system of custom post types to create a conference schedule.
Author: Matt DeClaire
Version: 1.0
Author URI: http://conferencer.louddog.com/
*/

if (!function_exists('debug')) {
	function debug($var) {
		echo "<pre style='background-color: #EEE; padding: 5px;'>";
		print_r($var);
		echo "</pre>";
	}
}

session_start();

define('CONFERENCER_PATH', dirname(__FILE__));
define('CONFERENCER_URL', plugin_dir_url(__FILE__));

include CONFERENCER_PATH.'/functions.php';

new Conferencer();
class Conferencer {
	function __construct() {
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('init', array(&$this, 'styles_and_scriptst'));
		add_action('admin_notices', array(&$this, 'admin_notices'));
		register_activation_hook(__FILE__, array(&$this, 'activate'));
		register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));
		add_theme_support('post-thumbnails');
		$this->include_files();
	}
	
	function include_files() {
		$includes = array(
			'/custom_post_type.php',
			'/shortcode.php',
			'/models/session.php',
			'/models/speaker.php',
			'/models/room.php',
			'/models/time_slot.php',
			'/models/track.php',
			'/models/sponsor.php',
			'/models/sponsor_level.php',
		);
		
		foreach ($includes as $include) include CONFERENCER_PATH.$include;
		
		foreach (array('settings', 'widgets', 'shortcodes') as $dir) {
			$d = dir(CONFERENCER_PATH."/$dir");
			while ($file = $d->read()) {
				if (in_array($file, array('.', '..'))) continue;
				include CONFERENCER_PATH."/$dir/$file";
			}
		}
	}
	
	function styles_and_scriptst() {
		wp_register_style(
			'jquery-ui',
			CONFERENCER_URL.'css/jquery-ui-1.8.16.custom.css',
			false,
			'1.8.16'
		);

		wp_register_style(
			'conferencer-admin',
			CONFERENCER_URL.'css/admin.css',
			array('jquery-ui'),
			'1.0'
		);

		wp_register_style(
			'conferencer',
			CONFERENCER_URL.'css/screen.css',
			array('jquery-ui'),
			'1.0'
		);

		wp_register_script(
			'jquery-ui',
			CONFERENCER_URL.'js/jquery-ui-1.8.16.custom.min.js',
			array('jquery'),
			'1.8.16',
			true
		);

		wp_register_script(
			'fadeshow',
			CONFERENCER_URL.'js/jquery.fadeshow.js',
			array('jquery'),
			'1.0',
			true
		);

		wp_register_script(
			'conferencer-admin',
			CONFERENCER_URL.'js/admin.js',
			array('jquery-ui'),
			'1.0',
			true
		);

		wp_register_script(
			'conferencer',
			CONFERENCER_URL.'js/site.js',
			array('fadeshow'),
			'1.0',
			true
		);
	
		if (is_admin()) {
			wp_enqueue_style('conferencer-admin');
			wp_enqueue_script('conferencer-admin');
		} else {
			wp_enqueue_style('conferencer');
			wp_enqueue_script('conferencer');
		}
	}
	
	function admin_menu() {
		add_menu_page(
	        "Conferencer",
	        "Conferencer",
	        'edit_posts',
	        'conferencer',
	        array(&$this, 'overview'),
	        false,
	        41
		);
		
		$GLOBALS['menu'][40] = array('', 'read', 'separator-2', '', 'wp-menu-separator');
	}
	
	function overview() {
		include CONFERENCER_PATH.'/markup/overview.php';
	}
	
	function admin_notices() {
		$messages = get_option('conferencer_messages', array());
		if (count($messages)) {
			foreach ($messages as $message) { ?>
				<div class="updated">
					<p><?php echo $message; ?></p>
				</div>
			<?php }
			delete_option('conferencer_messages');
		}
	}
	
	function add_admin_notice($message) {
		$messages = get_option('conferencer_messages', array());
		$messages[] = $message;
		update_option('conferencer_messages', $messages);
	}
	
	function activate() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
	
	function deactivate() {
		delete_option('conferencer_messages');
		delete_option('conferencer_logo_regeneration_needed');
		delete_option('conferencer_sponsors_widget_image_sizes');
	}
	
	static $regenerate_logo_error = false;
	
	function get_regenerate_logo_error() {
		return self::$regenerate_logo_error;
	}
	
	function regenerate_logo($sponsor = false) {
		self::$regenerate_logo_error = false;
		
		if (!current_user_can('manage_options')) {
			self::$regenerate_logo_error = "incorrect permissions";
			return false;
		}

		if (!$sponsor) $sponsor = get_post($GLOBALS['post']);
		if (is_numeric($sponsor)) $sponsor = get_post($sponsor);
		
		if (!$sponsor || 'sponsor' != $sponsor->post_type) {
			self::$regenerate_logo_error = "invalid sponsor";
			return false;
		}
		
		$thumbID = get_post_thumbnail_id($sponsor->ID);
		
		if (!$thumbID) {
			self::$regenerate_logo_error = "no thumbnail";
			return false;
		}

		$path = get_attached_file($thumbID);
		if (false === $path || !file_exists($path)) {
			self::$regenerate_logo_error = "original cannot be found.";
			return false;
		}

		$metadata = wp_generate_attachment_metadata($thumbID, $path);
		
		if (is_wp_error($metadata)) {
			self::$regenerate_logo_error = $metadata->get_error_message();
			return false;
		}
		
		if (empty($metadata)) {
			self::$regenerate_logo_error = "unknown error";
			return false;
		}

		// If this fails, then it just means that nothing was changed (old value == new value)
		if (!wp_update_attachment_metadata($thumbID, $metadata)) {
			self::$regenerate_logo_error = "no change";
			return false;
		}

		return true;
	}
	
	// static user functions =========================================================
	
	function attach_speakers(&$sessions) {
		$speakers = Conferencer::get_list('speaker');
		
		foreach ($sessions as $session_id => $session) {
			$session->speakers = array();
			$speaker_ids = unserialize(get_post_meta($session->ID, 'conferencer_speakers', true));
			if (!$speaker_ids) $speaker_ids = array();
			foreach ($speaker_ids as $speaker_id) {
				$sessions[$session_id]->speakers[$speaker_id] = $speakers[$speaker_id];
			}
			uasort($sessions[$session_id]->speakers, array('Conferencer', 'order_sort'));
		}
	}
	
	function get_list($post_type, $sort = 'order_sort') {
		$query = new WP_Query(array(
			'post_type' => $post_type,
			'posts_per_page' => -1, // get all
		));
		
		$list = array();
		foreach ($query->posts as $item) {
			$list[$item->ID] = $item;
		}
		
		if (method_exists('Conferencer', $sort)) uasort($list, array('Conferencer', $sort));
		
		return $list;
	}
	
	function get_sessions($post_id) {
		$post_type = get_post_type($post_id);
		$sessions = array();
		
		if (in_array($post_type, array('speaker', 'sponsor'))) {
			$query = new WP_Query(array(
				'post_type' => 'session',
				'posts_per_page' => -1, // get all
			));
			
			foreach ($query->posts as $session) {
				// if the pluralization ever doesn't work, this will need to be refactored
				$post_ids = unserialize(get_post_meta($session->ID, 'conferencer_'.$post_type.'s', true));
				if (in_array($post_id, $post_ids)) $sessions[$session->ID] = $session;
			}
		} else {
			$query = new WP_Query(array(
				'post_type' => 'session',
				'posts_per_page' => -1, // get all
				'meta_query' => array(
					array(
						'key' => 'conferencer_'.get_post_type($post_id),
						'value' => $post_id,
					)
				),
			));
		
			foreach ($query->posts as $session) {
				$sessions[$session->ID] = $session;
			}
		}
		
		uasort($sessions, array('Conferencer', 'order_sort'));
		uasort($sessions, array('Conferencer', 'start_time_sort'));
		
		return $sessions;
	}
	
	function order_sort($a, $b) {
		$aOrder = get_post_meta($a->ID, 'conferencer_order', true);
		$bOrder = get_post_meta($b->ID, 'conferencer_order', true);
		
		if ($aOrder == $bOrder) return 0;
		return $aOrder < $bOrder ? -1 : 1;
	}
	
	function title_sort($a, $b) {
		if ($a->post_title == $b->post_title) return 0;
		return strcmp($a->post_title, $b->post_title);
	}
	
	function start_time_sort($a, $b) {
		$aOrder = get_post_meta($a->ID, 'conferencer_starts', true);
		$bOrder = get_post_meta($b->ID, 'conferencer_starts', true);
		
		if ($aOrder == $bOrder) return 0;
		return $aOrder < $bOrder ? -1 : 1;
	}
}