<?php
// ==========================================
// DATEI: admin/partials/canvas-management.php
// ==========================================

if (!defined('ABSPATH')) {
    exit;
}

settings_errors('pqc_messages');

$db = PQC_Database::get_instance();
$canvas_pixels = $db->get_canvas_pixels();
$canvas_width = get_option('pqc_canvas_width', 800);
$canvas_height = get_option('pqc_canvas_height', 600);
?>

<div class="wrap pqc-admin-wrapper">
    <div class="pqc-admin-header">
        <h1><?php _e('Canvas Verwaltung', 'pixel-quest-canvas'); ?></h1>
        <p><?php _e('Verwalte und überwache das kollaborative Canvas', 'pixel-quest-canvas'); ?></p>
    </div>

    <div class="pqc-settings-section">
        <h2><?php _e('Canvas Vorschau', 'pixel-quest-canvas'); ?></h2>
        <div class="pqc-canvas-management">
            <div class="pqc-canvas-preview">
                <canvas id="pqc-admin-canvas"
                    width="<?php echo min($canvas_width, 600); ?>"
                    height="<?php echo min($canvas_height, 400); ?>"
                    style="border: 1px solid #ddd; image-rendering: pixelated;">
                </canvas>
            </div>

            <div class="pqc-canvas-controls">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="clear_canvas" value="1">
                    <button type="submit" class="pqc-btn pqc-btn-danger"
                        onclick="return confirm('<?php _e('Canvas wirklich komplett leeren?', 'pixel-quest-canvas'); ?>')">
                        <?php _e('Canvas leeren', 'pixel-quest-canvas'); ?>
                    </button>
                </form>

                <button id="pqc-export-canvas" class="pqc-btn pqc-btn-secondary">
                    <?php _e('Als Bild exportieren', 'pixel-quest-canvas'); ?>
                </button>

                <button id="pqc-refresh-canvas" class="pqc-btn pqc-btn-primary">
                    <?php _e('Aktualisieren', 'pixel-quest-canvas'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="pqc-settings-section">
        <h2><?php _e('Canvas Statistiken', 'pixel-quest-canvas'); ?></h2>
        <div class="pqc-stats-grid">
            <div class="pqc-stat-card">
                <span class="pqc-stat-number"><?php echo number_format(count($canvas_pixels)); ?></span>
                <span class="pqc-stat-label"><?php _e('Gesetzte Pixel', 'pixel-quest-canvas'); ?></span>
            </div>

            <div class="pqc-stat-card">
                <span class="pqc-stat-number"><?php echo number_format($canvas_width * $canvas_height); ?></span>
                <span class="pqc-stat-label"><?php _e('Gesamt Pixel', 'pixel-quest-canvas'); ?></span>
            </div>

            <div class="pqc-stat-card">
                <span class="pqc-stat-number"><?php echo $canvas_width; ?>x<?php echo $canvas_height; ?></span>
                <span class="pqc-stat-label"><?php _e('Canvas Größe', 'pixel-quest-canvas'); ?></span>
            </div>

            <div class="pqc-stat-card">
                <span class="pqc-stat-number">
                    <?php echo round((count($canvas_pixels) / ($canvas_width * $canvas_height)) * 100, 1); ?>%
                </span>
                <span class="pqc-stat-label"><?php _e('Auslastung', 'pixel-quest-canvas'); ?></span>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Canvas-Daten laden und zeichnen
        const canvas = document.getElementById('pqc-admin-canvas');
        const ctx = canvas.getContext('2d');
        const canvasData = <?php echo json_encode($canvas_pixels); ?>;
        const scaleX = canvas.width / <?php echo $canvas_width; ?>;
        const scaleY = canvas.height / <?php echo $canvas_height; ?>;

        function drawCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            canvasData.forEach(function(pixel) {
                ctx.fillStyle = pixel.color;
                ctx.fillRect(
                    Math.floor(pixel.x_position * scaleX),
                    Math.floor(pixel.y_position * scaleY),
                    Math.max(1, Math.ceil(scaleX)),
                    Math.max(1, Math.ceil(scaleY))
                );
            });
        }

        drawCanvas();

        // Export-Funktionalität
        $('#pqc-export-canvas').click(function() {
            const link = document.createElement('a');
            link.download = 'pixel-quest-canvas-' + new Date().getTime() + '.png';
            link.href = canvas.toDataURL();
            link.click();
        });

        // Aktualisieren
        $('#pqc-refresh-canvas').click(function() {
            location.reload();
        });
    });
</script>