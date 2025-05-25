<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Datenbankbereinigung bei Plugin-Deinstallation
global $wpdb;

// Tabellen löschen
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pqc_canvas_pixels");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pqc_statistics");

// Optionen löschen
$options = [
    'pqc_max_session_pixels',
    'pqc_min_pixels_per_page',
    'pqc_max_pixels_per_page',
    'pqc_canvas_width',
    'pqc_canvas_height',
    'pqc_canvas_colors',
    'pqc_session_timeout'
];

foreach ($options as $option) {
    delete_option($option);
}

// Post-Meta bereinigen
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_pqc_%'");

?>