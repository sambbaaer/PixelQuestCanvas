<?php
// ==========================================
// DATEI: includes/class-pqc-database.php
// ==========================================

if (!defined('ABSPATH')) {
    exit;
}

class PQC_Database
{

    private static $instance = null;
    private $table_canvas;
    private $table_stats;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        global $wpdb;
        $this->table_canvas = $wpdb->prefix . 'pqc_canvas_pixels';
        $this->table_stats = $wpdb->prefix . 'pqc_statistics';
    }

    public static function create_tables()
    {
        global $wpdb;

        $instance = self::get_instance();
        $charset_collate = $wpdb->get_charset_collate();

        // Canvas-Pixel-Tabelle
        $sql_canvas = "CREATE TABLE IF NOT EXISTS {$instance->table_canvas} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            x_position int(11) NOT NULL,
            y_position int(11) NOT NULL,
            color varchar(7) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            session_id varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_position (x_position, y_position),
            KEY created_at (created_at),
            KEY session_id (session_id)
        ) $charset_collate;";

        // Statistik-Tabelle
        $sql_stats = "CREATE TABLE IF NOT EXISTS {$instance->table_stats} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            pixels_collected bigint(20) DEFAULT 0,
            page_views bigint(20) DEFAULT 0,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id),
            KEY pixels_collected (pixels_collected),
            KEY page_views (page_views)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_canvas);
        dbDelta($sql_stats);
    }

    public function save_pixel($x, $y, $color, $session_id = null)
    {
        global $wpdb;

        return $wpdb->replace(
            $this->table_canvas,
            [
                'x_position' => absint($x),
                'y_position' => absint($y),
                'color' => sanitize_hex_color($color),
                'session_id' => sanitize_text_field($session_id),
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
    }

    public function get_canvas_pixels()
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT x_position, y_position, color 
             FROM {$this->table_canvas} 
             ORDER BY created_at ASC"
        );
    }

    public function clear_canvas()
    {
        global $wpdb;
        return $wpdb->query("TRUNCATE TABLE {$this->table_canvas}");
    }

    public function update_page_stats($post_id, $pixels_collected = 0)
    {
        global $wpdb;

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_stats} WHERE post_id = %d",
            $post_id
        ));

        if ($existing) {
            return $wpdb->update(
                $this->table_stats,
                [
                    'pixels_collected' => $existing->pixels_collected + $pixels_collected,
                    'page_views' => $existing->page_views + 1
                ],
                ['post_id' => $post_id],
                ['%d', '%d'],
                ['%d']
            );
        } else {
            return $wpdb->insert(
                $this->table_stats,
                [
                    'post_id' => $post_id,
                    'pixels_collected' => $pixels_collected,
                    'page_views' => 1
                ],
                ['%d', '%d', '%d']
            );
        }
    }

    public function get_statistics()
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT s.*, p.post_title 
             FROM {$this->table_stats} s 
             LEFT JOIN {$wpdb->posts} p ON s.post_id = p.ID 
             ORDER BY s.pixels_collected DESC 
             LIMIT 20"
        );
    }
}
