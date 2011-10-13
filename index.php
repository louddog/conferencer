<?php
/*
Plugin Name: Conferencer
Description: Creates a system of custom post types to create a conference schedule.
Author: Loud Dog
Version: 0.1
Author URI: http://conferencer.louddog.com/
*/

// TODO: Look into refactoring with get_post_custom()

session_start();

define('CONFERENCER_PATH', dirname(__FILE__));
define('CONFERENCER_URL', plugin_dir_url(__FILE__));
define('CONFERENCER_REGISTER_FILE', __FILE__);

include CONFERENCER_PATH.'/functions.php';

new Conferencer();
class Conferencer {
	static $post_types = array(); // constructed in custom post type constuctor
	
	function __construct() {
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('init', array(&$this, 'styles_and_scripts'));
		add_action('admin_notices', array(&$this, 'admin_notices'));
		register_activation_hook(CONFERENCER_REGISTER_FILE, array(&$this, 'activate'));
		register_deactivation_hook(CONFERENCER_REGISTER_FILE, array(&$this, 'deactivate'));
		add_theme_support('post-thumbnails');
		$this->include_files();
	}
	
	function include_files() {
		foreach (array(
			'/models/custom_post_type.php',
			'/models/session.php',
			'/models/speaker.php',
			'/models/company.php',
			'/models/room.php',
			'/models/time_slot.php',
			'/models/track.php',
			'/models/sponsor.php',
			'/models/sponsor_level.php',
			
			'/shortcodes/shortcode.php',
			'/shortcodes/agenda.php',
			'/shortcodes/session-meta.php',
			'/shortcodes/sessions.php',
			
			'/widgets/sponsors.php',
			
			'/settings/options.php',
			'/settings/order.php',
			'/settings/cache.php',
			'/settings/regenerate-logos.php',
		) as $include) include CONFERENCER_PATH.$include;
	}
	
	function styles_and_scripts() {
		wp_register_style('conferencer-jquery-ui', CONFERENCER_URL.'css/jquery-ui-1.8.16.custom.css', false, '1.8.16');
		wp_register_script('conferencer-jquery-ui', CONFERENCER_URL.'js/jquery-ui-1.8.16.custom.min.js', array('jquery'), '1.8.16', true);

		wp_register_style('conferencer-admin', CONFERENCER_URL.'css/admin.css', array('conferencer-jquery-ui'), '1.0');
		wp_register_script('conferencer-admin', CONFERENCER_URL.'js/admin.js', array('jquery'), '1.0', true);
		wp_register_script('conferencer-cpt', CONFERENCER_URL.'js/cpt.js', array('conferencer-jquery-ui'), '1.0', true);
		wp_register_script('conferencer-reorder', CONFERENCER_URL.'js/reorder.js', array('conferencer-jquery-ui'), '1.0', true);
		wp_register_script('conferencer-regenerate-logos', CONFERENCER_URL.'js/regenerate-logos.js', array('conferencer-jquery-ui'), '1.0', true);

		wp_register_script('conferencer-fadeshow', CONFERENCER_URL.'js/jquery.fadeshow.js', array('jquery'), '1.0', true);
		wp_register_style('conferencer', CONFERENCER_URL.'css/screen.css', false, '1.0.1');
		wp_register_script('conferencer', CONFERENCER_URL.'js/site.js', array('conferencer-fadeshow'), '1.0.1', true);
		
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
	
	// static user functions =========================================================
	
	function get_sponsors($session) {
		static $all_sponsors = false;
		if (!$all_sponsors) $all_sponsors = self::get_list('sponsor');
		
		$sponsors = array();
		$ids = get_post_meta($session->ID, 'conferencer_sponsors', true);
		if (!$ids) $ids = array();
		foreach ($ids as $id) {
			$sponsors[$id] = $all_sponsors[$id];
		}
		
		uasort($sponsors, array('Conferencer', 'order_sort'));
		
		return $sponsors;
	}
	
	function get_speakers($session) {
		static $all_speakers = false;
		if (!$all_speakers) $all_speakers = self::get_list('speaker');
		
		$speakers = array();
		$ids = get_post_meta($session->ID, 'conferencer_speakers', true);
		if (!$ids) $ids = array();
		foreach ($ids as $id) {
			$speakers[$id] = $all_speakers[$id];
		}
		uasort($speakers, array('Conferencer', 'order_sort'));
		
		return $speakers;
	}
	
	function attach_speakers(&$sessions) {
		$single = false;
		if (!is_array($sessions)) {
			$single = true;
			$sessions = array($sessions->ID => $sessions);
		}
		
		$speakers = Conferencer::get_list('speaker');
		
		foreach ($sessions as $session_id => $session) {
			$session->speakers = self::get_speakers($session);
		}
		
		if ($single) $sessions = array_pop($sessions);
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
	
	function get_sessions($post_ids) {
		if (!is_array($post_ids)) $post_ids = array($post_ids);
		
		$session_ids = array();
		
		static $all_sessions = false;
		if (!$all_sessions) $all_sessions = self::get_list('session');
		
		foreach ($post_ids as $post_id) {
			$post_type = get_post_type($post_id);
			
			if (in_array($post_type, array('speaker', 'sponsor'))) {
				foreach ($all_sessions as $session) {
					$related_post_ids = get_post_meta($session->ID, 'conferencer_'.$post_type.'s', true);
					if (in_array($post_id, $related_post_ids)) $session_ids[] = $session->ID;
				}
			} else {
				$query = new WP_Query(array(
					'post_type' => 'session',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => 'conferencer_'.$post_type,
							'value' => $post_id,
						)
					),
				));
				
				foreach ($query->posts as $session) {
					$session_ids[] = $session->ID;
				}
			}
		}
		
		$sessions = array();
		foreach ($session_ids as $session_id) {
			$sessions[$session_id] = $all_sessions[$session_id];
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