<script>
jQuery(document).ready(function($) {
    'use strict';
    
    // Color Picker initialisieren
    $('.pqc-color-picker').wpColorPicker();
    
    // Canvas-Verwaltung
    if ($('#pqc-admin-canvas').length) {
        initAdminCanvas();
    }
    
    // Statistiken automatisch aktualisieren
    setInterval(function() {
        if ($('.pqc-stats-grid').length) {
            updateStats();
        }
    }, 30000); // Alle 30 Sekunden
    
    function initAdminCanvas() {
        // Canvas-spezifische Admin-Funktionalität
        // Bereits im HTML-Template implementiert
    }
    
    function updateStats() {
        $.ajax({
            url: pqc_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'pqc_get_stats',
                nonce: pqc_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Statistiken aktualisieren
                    $('.pqc-stat-number').each(function(index) {
                        if (response.data[index]) {
                            $(this).text(response.data[index]);
                        }
                    });
                }
            }
        });
    }
    
    // Formular-Validierung
    $('form').on('submit', function(e) {
        const minPixels = parseInt($('input[name="min_pixels_per_page"]').val());
        const maxPixels = parseInt($('input[name="max_pixels_per_page"]').val());
        
        if (minPixels > maxPixels) {
            e.preventDefault();
            alert('Minimum Pixel darf nicht größer als Maximum Pixel sein!');
            return false;
        }
        
        const canvasWidth = parseInt($('input[name="canvas_width"]').val());
        const canvasHeight = parseInt($('input[name="canvas_height"]').val());
        
        if (canvasWidth * canvasHeight > 2000000) {
            e.preventDefault();
            alert('Canvas-Größe zu groß! Maximale Pixelanzahl: 2.000.000');
            return false;
        }
    });
    
    // Tooltips
    $('.pqc-tooltip').hover(function() {
        const tooltip = $(this).attr('data-tooltip');
        if (tooltip) {
            $(this).attr('title', tooltip);
        }
    });
    
    // Bestätigungsdialoge
    $('.pqc-btn-danger').click(function(e) {
        if (!confirm('Diese Aktion kann nicht rückgängig gemacht werden. Fortfahren?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Live-Vorschau für Canvas-Größe
    $('input[name="canvas_width"], input[name="canvas_height"]').on('input', function() {
        const width = parseInt($('input[name="canvas_width"]').val()) || 800;
        const height = parseInt($('input[name="canvas_height"]').val()) || 600;
        
        $('#canvas-size-preview').text(width + ' × ' + height + ' Pixel (' + 
            (width * height).toLocaleString() + ' Gesamt-Pixel)');
    });
});
</script>