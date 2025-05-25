<?php
// ==========================================
// DATEI: includes/class-pqc-shortcodes.php
// ==========================================

if (!defined('ABSPATH')) {
    exit;
}

class PQC_Shortcodes
{

    public function __construct()
    {
        add_action('init', [$this, 'register_shortcodes']);
    }

    public function register_shortcodes()
    {
        add_shortcode('pixel_count', [$this, 'pixel_count_shortcode']);
        add_shortcode('pixel_canvas', [$this, 'pixel_canvas_shortcode']);
        add_shortcode('pixel_toggle', [$this, 'pixel_toggle_shortcode']);
    }

    public function pixel_count_shortcode($atts)
    {
        $atts = shortcode_atts([
            'format' => 'Gesammelte Pixel: {count}'
        ], $atts);

        return '<span class="pqc-pixel-count" data-format="' . esc_attr($atts['format']) . '">0</span>';
    }

    public function pixel_canvas_shortcode($atts)
    {
        $atts = shortcode_atts([
            'width' => get_option('pqc_canvas_width', 800),
            'height' => get_option('pqc_canvas_height', 600),
            'show_palette' => 'true',
            'show_counter' => 'true'
        ], $atts);

        wp_enqueue_script('pqc-canvas');
        wp_enqueue_style('pqc-canvas');

        ob_start();
?>
        <div id="pqc-canvas-container" class="pqc-canvas-wrapper">
            <?php if ($atts['show_counter'] === 'true'): ?>
                <div class="pqc-canvas-header">
                    <div class="pqc-pixel-counter">
                        <span>Verfügbare Pixel: <span id="pqc-available-pixels">0</span></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($atts['show_palette'] === 'true'): ?>
                <div class="pqc-color-palette">
                    <?php foreach (get_option('pqc_canvas_colors', []) as $color): ?>
                        <button class="pqc-color-btn" data-color="<?php echo esc_attr($color); ?>"
                            style="background-color: <?php echo esc_attr($color); ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="pqc-canvas-scroll">
                <canvas id="pqc-canvas"
                    width="<?php echo esc_attr($atts['width']); ?>"
                    height="<?php echo esc_attr($atts['height']); ?>">
                    <?php _e('Ihr Browser unterstützt das Canvas-Element nicht.', 'pixel-quest-canvas'); ?>
                </canvas>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    public function pixel_toggle_shortcode($atts)
    {
        $atts = shortcode_atts([
            'text_show' => __('Pixel anzeigen', 'pixel-quest-canvas'),
            'text_hide' => __('Pixel ausblenden', 'pixel-quest-canvas')
        ], $atts);

        return '<button id="pqc-toggle-pixels" class="pqc-toggle-btn" 
                        data-text-show="' . esc_attr($atts['text_show']) . '" 
                        data-text-hide="' . esc_attr($atts['text_hide']) . '">
                    ' . esc_html($atts['text_show']) . '
                </button>';
    }
}

?>