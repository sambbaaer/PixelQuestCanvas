<?php
/**
 * Canvas management for Pixel Quest Canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

class PQC_Canvas {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueueCanvasScript']);
    }
    
    /**
     * Enqueue canvas specific scripts
     */
    public function enqueueCanvasScript() {
        // Only enqueue on pages that contain the canvas shortcode
        if (!$this->pageHasCanvas()) {
            return;
        }
        
        wp_enqueue_script(
            'pqc-canvas',
            PQC_PLUGIN_URL . 'assets/js/canvas.js',
            ['jquery'],
            PQC_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('pqc-canvas', 'pqc_canvas', [
            'canvas_settings' => $this->getCanvasSettings(),
            'canvas_data' => $this->getCanvasData(),
            'nonce' => wp_create_nonce('pqc_canvas_nonce')
        ]);
    }
    
    /**
     * Check if current page has canvas shortcode
     */
    private function pageHasCanvas() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        return has_shortcode($post->post_content, 'pixel_canvas');
    }
    
    /**
     * Get canvas settings
     */
    private function getCanvasSettings() {
        return [
            'width' => get_option('pqc_canvas_width', 800),
            'height' => get_option('pqc_canvas_height', 600),
            'colors' => get_option('pqc_canvas_colors', $this->getDefaultColors()),
            'pixel_size' => 1, // 1x1 pixel
            'grid_enabled' => false
        ];
    }
    
    /**
     * Get current canvas data
     */
    private function getCanvasData() {
        $db = PQC_Database::getInstance();
        $pixels = $db->getCanvasPixels();
        
        $canvas_data = [];
        foreach ($pixels as $pixel) {
            $canvas_data[] = [
                'x' => (int) $pixel->x_position,
                'y' => (int) $pixel->y_position,
                'color' => $pixel->color
            ];
        }
        
        return $canvas_data;
    }
    
    /**
     * Render canvas HTML
     */
    public function renderCanvas($atts = []) {
        $atts = shortcode_atts([
            'width' => get_option('pqc_canvas_width', 800),
            'height' => get_option('pqc_canvas_height', 600),
            'show_palette' => 'true',
            'show_counter' => 'true'