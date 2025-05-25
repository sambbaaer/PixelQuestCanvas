<?php

/**
 * Pixel collection system for Pixel Quest Canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

class PQC_Pixel_Collector
{

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_footer', [$this, 'renderPixelIcons']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueCollectorScript']);
    }

    /**
     * Enqueue pixel collector specific scripts
     */
    public function enqueueCollectorScript()
    {
        if (!$this->shouldShowPixels()) {
            return;
        }

        wp_enqueue_script(
            'pqc-pixel-collector',
            PQC_PLUGIN_URL . 'assets/js/pixel-collector.js',
            ['jquery'],
            PQC_PLUGIN_VERSION,
            true
        );

        wp_localize_script('pqc-pixel-collector', 'pqc_collector', [
            'pixel_data' => $this->getPixelDataForPage(),
            'icon_url' => PQC_PLUGIN_URL . 'assets/images/pixel-icon.png',
            'collect_sound' => PQC_PLUGIN_URL . 'assets/sounds/collect.mp3'
        ]);
    }

    /**
     * Check if pixels should be shown on current page
     */
    private function shouldShowPixels()
    {
        global $post;

        // Don't show on admin pages
        if (is_admin()) {
            return false;
        }

        // Don't show if user disabled icons
        if (isset($_COOKIE['pqc_hide_icons']) && $_COOKIE['pqc_hide_icons'] === 'true') {
            return false;
        }

        // Check if pixels are enabled for this post/page
        if ($post) {
            $enabled = get_post_meta($post->ID, '_pqc_pixels_enabled', true);
            if ($enabled === 'no') {
                return false;
            }
        }

        return true;
    }

    /**
     * Get pixel data for current page
     */
    private function getPixelDataForPage()
    {
        global $post;

        if (!$post) {
            return [];
        }

        // Get settings for this page
        $min_pixels = get_post_meta($post->ID, '_pqc_min_pixels', true) ?: get_option('pqc_min_pixels_per_page', 2);
        $max_pixels = get_post_meta($post->ID, '_pqc_max_pixels', true) ?: get_option('pqc_max_pixels_per_page', 4);
        $multiplier = get_post_meta($post->ID, '_pqc_pixel_multiplier', true) ?: 1;

        // Calculate pixel count for this visit
        $pixel_count = rand($min_pixels, $max_pixels);
        $pixel_count = floor($pixel_count * $multiplier);

        // Generate pixel positions
        $pixels = [];
        for ($i = 0; $i < $pixel_count; $i++) {
            $pixels[] = [
                'id' => 'pixel_' . uniqid(),
                'delay' => rand(1000, 5000), // Show between 1-5 seconds after page load
                'position' => $this->generateRandomPosition()
            ];
        }

        // Update page statistics
        PQC_Database::getInstance()->updatePageStats($post->ID, $pixel_count);

        return $pixels;
    }

    /**
     * Generate random position for pixel icon
     */
    private function generateRandomPosition()
    {
        // Generate position as percentage of viewport
        return [
            'top' => rand(10, 80) . '%',
            'left' => rand(10, 90) . '%'
        ];
    }

    /**
     * Render pixel icons container
     */
    public function renderPixelIcons()
    {
        if (!$this->shouldShowPixels()) {
            return;
        }

        echo '<div id="pqc-pixel-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 9999;"></div>';
        echo '<div id="pqc-pixel-counter" style="position: fixed; top: 20px; right: 20px; background: rgba(0,0,0,0.8); color: white; padding: 10px 15px; border-radius: 20px; font-size: 14px; z-index: 10000; display: none;">
                <span id="pqc-counter-text">Pixel: <span id="pqc-counter-value">0</span></span>
              </div>';
    }

    /**
     * Calculate streak bonus
     */
    public static function calculateStreakBonus($visited_pages)
    {
        $bonus = 0;

        if ($visited_pages >= 3) $bonus += 1;
        if ($visited_pages >= 5) $bonus += 1;
        if ($visited_pages >= 7) $bonus += 1;

        return $bonus;
    }

    /**
     * Get current session pixel count
     */
    public static function getSessionPixelCount()
    {
        // This will be handled by JavaScript/LocalStorage
        // PHP function for reference/validation
        return 0;
    }

    /**
     * Check if session limit reached
     */
    public static function isSessionLimitReached($current_count)
    {
        $max_pixels = get_option('pqc_max_session_pixels', 40);
        return $current_count >= $max_pixels;
    }

    /**
     * Add meta box to posts/pages for pixel settings
     */
    public function addMetaBox()
    {
        add_meta_box(
            'pqc_pixel_settings',
            __('Pixel Quest Settings', 'pixel-quest-canvas'),
            [$this, 'renderMetaBox'],
            ['post', 'page'],
            'side',
            'default'
        );
    }

    /**
     * Render meta box content
     */
    public function renderMetaBox($post)
    {
        wp_nonce_field('pqc_meta_box', 'pqc_meta_box_nonce');

        $enabled = get_post_meta($post->ID, '_pqc_pixels_enabled', true) ?: 'yes';
        $min_pixels = get_post_meta($post->ID, '_pqc_min_pixels', true) ?: '';
        $max_pixels = get_post_meta($post->ID, '_pqc_max_pixels', true) ?: '';
        $multiplier = get_post_meta($post->ID, '_pqc_pixel_multiplier', true) ?: '1';

?>
        <table class="form-table">
            <tr>
                <th><label for="pqc_pixels_enabled"><?php _e('Enable Pixels', 'pixel-quest-canvas'); ?></label></th>
                <td>
                    <select name="pqc_pixels_enabled" id="pqc_pixels_enabled">
                        <option value="yes" <?php selected($enabled, 'yes'); ?>><?php _e('Yes', 'pixel-quest-canvas'); ?></option>
                        <option value="no" <?php selected($enabled, 'no'); ?>><?php _e('No', 'pixel-quest-canvas'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="pqc_min_pixels"><?php _e('Min Pixels', 'pixel-quest-canvas'); ?></label></th>
                <td>
                    <input type="number" name="pqc_min_pixels" id="pqc_min_pixels" value="<?php echo esc_attr($min_pixels); ?>" min="1" max="15" placeholder="<?php echo get_option('pqc_min_pixels_per_page', 2); ?>">
                    <p class="description"><?php _e('Leave empty to use global default', 'pixel-quest-canvas'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pqc_max_pixels"><?php _e('Max Pixels', 'pixel-quest-canvas'); ?></label></th>
                <td>
                    <input type="number" name="pqc_max_pixels" id="pqc_max_pixels" value="<?php echo esc_attr($max_pixels); ?>" min="1" max="15" placeholder="<?php echo get_option('pqc_max_pixels_per_page', 4); ?>">
                    <p class="description"><?php _e('Leave empty to use global default', 'pixel-quest-canvas'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pqc_pixel_multiplier"><?php _e('Pixel Multiplier', 'pixel-quest-canvas'); ?></label></th>
                <td>
                    <select name="pqc_pixel_multiplier" id="pqc_pixel_multiplier">
                        <option value="1" <?php selected($multiplier, '1'); ?>>1x</option>
                        <option value="1.5" <?php selected($multiplier, '1.5'); ?>>1.5x</option>
                        <option value="2" <?php selected($multiplier, '2'); ?>>2x</option>
                    </select>
                    <p class="description"><?php _e('Multiply pixel count for hot content', 'pixel-quest-canvas'); ?></p>
                </td>
            </tr>
        </table>
<?php
    }

    /**
     * Save meta box data
     */
    public function saveMetaBox($post_id)
    {
        if (!isset($_POST['pqc_meta_box_nonce']) || !wp_verify_nonce($_POST['pqc_meta_box_nonce'], 'pqc_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = ['pqc_pixels_enabled', 'pqc_min_pixels', 'pqc_max_pixels', 'pqc_pixel_multiplier'];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}

// Initialize meta box hooks
add_action('add_meta_boxes', [PQC_Pixel_Collector::getInstance(), 'addMetaBox']);
add_action('save_post', [PQC_Pixel_Collector::getInstance(), 'saveMetaBox']);
