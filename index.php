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
		add_theme_support('post-thumbnails');
		$this->include_files();
	}
	
	function include_files() {
		$includes = array(
			'/custom_post_type.php',
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
			'jquery-ui',
			CONFERENCER_URL.'js/jquery-ui-1.8.16.custom.min.js',
			array('jquery'),
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