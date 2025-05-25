<?php
// ==========================================
// DATEI: includes/class-pqc-ajax.php
// ==========================================

if (!defined('ABSPATH')) {
    exit;
}

class PQC_Ajax
{

    public function __construct()
    {
        // AJAX-Aktionen werden in der Hauptdatei registriert
    }

    public function save_pixel()
    {
        // Nonce-Verifizierung
        if (!wp_verify_nonce($_POST['nonce'], 'pqc_canvas_nonce')) {
            wp_die(__('Sicherheitsfehler', 'pixel-quest-canvas'));
        }

        $x = isset($_POST['x']) ? absint($_POST['x']) : 0;
        $y = isset($_POST['y']) ? absint($_POST['y']) : 0;
        $color = isset($_POST['color']) ? sanitize_hex_color($_POST['color']) : '#000000';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : null;

        // Canvas-Grenzen prüfen
        $canvas_width = get_option('pqc_canvas_width', 800);
        $canvas_height = get_option('pqc_canvas_height', 600);

        if ($x >= $canvas_width || $y >= $canvas_height) {
            wp_send_json_error(__('Position außerhalb des Canvas', 'pixel-quest-canvas'));
        }

        // Gültige Farbe prüfen
        $valid_colors = get_option('pqc_canvas_colors', []);
        if (!in_array($color, $valid_colors)) {
            wp_send_json_error(__('Ungültige Farbe', 'pixel-quest-canvas'));
        }

        $db = PQC_Database::get_instance();
        $result = $db->save_pixel($x, $y, $color, $session_id);

        if ($result) {
            wp_send_json_success([
                'message' => __('Pixel gespeichert', 'pixel-quest-canvas'),
                'x' => $x,
                'y' => $y,
                'color' => $color
            ]);
        } else {
            wp_send_json_error(__('Fehler beim Speichern', 'pixel-quest-canvas'));
        }
    }

    public function get_canvas_data()
    {
        $db = PQC_Database::get_instance();
        $pixels = $db->get_canvas_pixels();

        $canvas_data = [];
        foreach ($pixels as $pixel) {
            $canvas_data[] = [
                'x' => (int) $pixel->x_position,
                'y' => (int) $pixel->y_position,
                'color' => $pixel->color
            ];
        }

        wp_send_json_success($canvas_data);
    }
}
