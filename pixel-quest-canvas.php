<?php

/**
 * Plugin Name: Pixel Quest Canvas
 * Plugin URI: https://yourwebsite.com/pixel-quest-canvas
 * Description: Ein interaktives Plugin das Besucher durch Pixel-sammeln zum kollaborativen Malen motiviert
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * Text Domain: pixel-quest-canvas
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten
define('PQC_VERSION', '1.0.0');
define('PQC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PQC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PQC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Haupt-Plugin-Klasse
class Pixel_Quest_Canvas {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->define_hooks();
    }
    
    private function define_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('plugins_loaded', [$this, 'init']);
    }
    
    public function init() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    private function load_dependencies() {
        require_once PQC_PLUGIN_PATH . 'includes/class-pqc-database.php';
        require_once PQC_PLUGIN_PATH . 'includes/class-pqc-pixel-collector.php';
        require_once PQC_PLUGIN_PATH . 'includes/class-pqc-canvas.php';
        require_once PQC_PLUGIN_PATH . 'includes/class-pqc-admin.php';
        require_once PQC_PLUGIN_PATH . 'includes/class-pqc-shortcodes.php';
        require_once PQC_PLUGIN_PATH . 'includes/class-pqc-ajax.php';
        
        if (did_action('elementor/loaded')) {
            require_once PQC_PLUGIN_PATH . 'includes/class-pqc-elementor.php';
        }
    }
    
    private function set_locale() {
        load_plugin_textdomain('pixel-quest-canvas', false, 
            dirname(PQC_PLUGIN_BASENAME) . '/languages');
    }
    
    private function define_admin_hooks() {
        $admin = new PQC_Admin();
        add_action('admin_enqueue_scripts', [$admin, 'enqueue_styles']);
        add_action('admin_enqueue_scripts', [$admin, 'enqueue_scripts']);
        add_action('admin_menu', [$admin, 'add_plugin_admin_menu']);
    }
    
    private function define_public_hooks() {
        $pixel_collector = new PQC_Pixel_Collector();
        $canvas = new PQC_Canvas();
        $shortcodes = new PQC_Shortcodes();
        $ajax = new PQC_Ajax();
        
        add_action('wp_enqueue_scripts', [$pixel_collector, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$pixel_collector, 'enqueue_scripts']);
        add_action('wp_footer', [$pixel_collector, 'render_pixel_icons']);
        
        add_action('wp_enqueue_scripts', [$canvas, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$canvas, 'enqueue_scripts']);
        
        // AJAX-Hooks
        add_action('wp_ajax_pqc_save_pixel', [$ajax, 'save_pixel']);
        add_action('wp_ajax_nopriv_pqc_save_pixel', [$ajax, 'save_pixel']);
        add_action('wp_ajax_pqc_get_canvas_data', [$ajax, 'get_canvas_data']);
        add_action('wp_ajax_nopriv_pqc_get_canvas_data', [$ajax, 'get_canvas_data']);
    }
    
    public function activate() {
        PQC_Database::create_tables();
        $this->set_default_options();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('pqc_cleanup_sessions');
    }
    
    private function set_default_options() {
        $defaults = [
            'pqc_max_session_pixels' => 40,
            'pqc_min_pixels_per_page' => 2,
            'pqc_max_pixels_per_page' => 4,
            'pqc_canvas_width' => 800,
            'pqc_canvas_height' => 600,
            'pqc_canvas_colors' => ['#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF'],
            'pqc_session_timeout' => 30
        ];
        
        foreach ($defaults as $option => $value) {
            if (false === get_option($option)) {
                add_option($option, $value);
            }
        }
    }
}

// Plugin initialisieren
function pqc_run() {
    $plugin = Pixel_Quest_Canvas::get_instance();
}
pqc_run();

?>