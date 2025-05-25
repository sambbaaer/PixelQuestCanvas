<?php

if (!defined('ABSPATH')) {
    exit;
}

settings_errors('pqc_messages');
?>

<div class="wrap pqc-admin-wrapper">
    <div class="pqc-admin-header">
        <h1><?php _e('Pixel Quest Canvas Einstellungen', 'pixel-quest-canvas'); ?></h1>
        <p><?php _e('Konfiguriere das Plugin nach deinen Wünschen', 'pixel-quest-canvas'); ?></p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('pqc_save_settings', 'pqc_settings_nonce'); ?>
        
        <div class="pqc-settings-section">
            <h2><?php _e('Pixel Einstellungen', 'pixel-quest-canvas'); ?></h2>
            <table class="pqc-form-table">
                <tr>
                    <th><?php _e('Max Pixel pro Session', 'pixel-quest-canvas'); ?></th>
                    <td>
                        <input type="color" name="canvas_color_<?php echo $i + 1; ?>" 
                           value="<?php echo esc_attr($colors[$i] ?? '#000000'); ?>" 
                           class="pqc-color-picker">
                </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <?php submit_button(__('Einstellungen speichern', 'pixel-quest-canvas')); ?>
    </form>
</div>
