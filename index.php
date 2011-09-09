<?php
/*
Plugin Name: Conferencer
Description: Creates a system of custom post types to create a conference schedule.
Author: Matt DeClaire
Version: 1.0
Author URI: http://conferencer.louddog.com/
*/

// TODO: make admin menu positions more robust (both items and separator)

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

new Conferencer();
class Conferencer {
	function __construct() {
		add_action('admin_menu', array(&$this, 'admin_menu'));
		include CONFERENCER_PATH.'/functions.php';
		$this->include_files();
		add_action('init', array(&$this, 'styles_and_scriptst'));
		add_action('admin_init', array(&$this, 'save_settings'));
		add_action('admin_notices', array(&$this, 'admin_notices'));
		register_activation_hook(__FILE__, array(&$this, 'activate'));
		add_theme_support('post-thumbnails');
	}
	
	function include_files() {
		include CONFERENCER_PATH.'/custom_post_type.php';
		foreach (array('models', 'widgets', 'shortcodes') as $dir) {
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
			CONFERENCER_URL.'css/jquery-ui-1.8.14.custom.css',
			false,
			'1.8.14'
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
			'conferencer-admin',
			CONFERENCER_URL.'js/admin.js',
			array('jquery-ui-sortable'),
			'1.0',
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
		
		add_submenu_page(
			'conferencer',
			"Settings",
			"Settings",
			'edit_posts',
			'conferencer_settings',
			array(&$this, 'settings')
		);
		
		$GLOBALS['menu'][40] = array('', 'read', 'separator-2', '', 'wp-menu-separator');
	}
	
	static $priority_post_types = array(
		'track' => "Tracks",
		'room' => "Rooms",
		'speaker' => "Speakers",
		'sponsor' => "Sponsors",
		'sponsor_level' => "Sponsor Levels",
		'company' => "Companies",
	);
	
	function overview() {
		include CONFERENCER_PATH.'/markup/overview.php';
	}
	
	function settings() {
		if (!current_user_can('edit_posts')) wp_die("You do not have sufficient permissions to access this page.");
		include CONFERENCER_PATH.'/markup/settings.php';
	}
	
	function save_settings() {
		if (isset($_POST['conferencer_sponsor_level_id'])) {
			foreach (self::$priority_post_types as $slug => $heading) {
				foreach ($_POST['conferencer_'.$slug.'_id'] as $order => $id) {
					update_post_meta(intVal($id), 'conferencer_order', $order);
				}
			}

			Conferencer::add_admin_message("Settings Saved");
			header("Location: ".$_SERVER['REQUEST_URI']);
			die;
		}
	}

	function admin_notices() {
		if (is_array($_SESSION['conferencer-notices'])) {
			echo '<div class="updated"><p>'.implode('</p><p>', $_SESSION['conferencer-notices']).'</p></div>';
			unset($_SESSION['conferencer-notices']);
			?>
			<script>
				jQuery(function($) {
					setTimeout(function() {
						$('.updated').slideUp();
					}, 5000);
				});
			</script>
			<?php
		}
	}
	
	function add_admin_message($message) {
		$_SESSION['conferencer-notices'][] = $message;
	}
	
	function activate() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
	
	// static user functions =========================================================
	
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
	
	function order_sort($a, $b) {
		$aOrder = get_post_meta($a->ID, $a->post_type.'_order', true);
		$bOrder = get_post_meta($b->ID, $b->post_type.'_order', true);
		
		if ($aOrder == $bOrder) return 0;
		return $aOrder < $bOrder ? -1 : 1;
	}
	
	function title_sort($a, $b) {
		if ($a->post_title == $b->post_title) return 0;
		return strcmp($a->post_title, $b->post_title);
	}
	
	function start_time_sort($a, $b) {
		$aOrder = get_post_meta($a->ID, $a->post_type.'_starts', true);
		$bOrder = get_post_meta($b->ID, $b->post_type.'_starts', true);
		
		if ($aOrder == $bOrder) return 0;
		return $aOrder < $bOrder ? -1 : 1;
	}
}