<?php

/**
 * Editor admin del bloque News.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Editor del bloque News.
 *
 * @param string $instance_id
 * @param array  $data
 * @return void
 */
function okip_render_admin_news_editor($instance_id, array $data)
{
    $data = okip_normalize_block_data('news', $data);
    $base = 'okip_blocks[' . $instance_id . '][data]';
    $animation = isset($data['animation']) && is_array($data['animation']) ? $data['animation'] : array();
    ?>

    <div class="okip-admin-tabs" data-okip-tabs>
        <div class="okip-admin-tabs__nav" role="tablist">
            <button type="button" class="okip-admin-tab-btn is-active" data-okip-tab-target="animacion"><?php esc_html_e('Animación', 'okip'); ?></button>
        </div>

        <div class="okip-admin-tab-panel is-active" data-okip-tab="animacion">
            <?php okip_admin_section_open(__('Entrada de tarjetas', 'okip'), __('Fade-in individual, de una sola reproducción, al entrar cada card al viewport.', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php
                okip_admin_checkbox_field(__('Animación activa', 'okip'), $base . '[animation][enabled]', ! empty($animation['enabled']));
                okip_admin_number_field(__('Duración (ms)', 'okip'), $base . '[animation][duration_ms]', isset($animation['duration_ms']) ? $animation['duration_ms'] : 620, '', array('min' => 0, 'max' => 5000, 'step' => '50'));
                okip_admin_number_field(__('Delay inicial (ms)', 'okip'), $base . '[animation][delay_ms]', isset($animation['delay_ms']) ? $animation['delay_ms'] : 80, '', array('min' => 0, 'max' => 10000, 'step' => '25'));
                okip_admin_number_field(__('Stagger por card (ms)', 'okip'), $base . '[animation][stagger_ms]', isset($animation['stagger_ms']) ? $animation['stagger_ms'] : 95, '', array('min' => 0, 'max' => 3000, 'step' => '25'));
                okip_admin_number_field(__('Desplazamiento Y (px)', 'okip'), $base . '[animation][translate_y]', isset($animation['translate_y']) ? $animation['translate_y'] : 22, '', array('min' => 0, 'max' => 160, 'step' => '1'));
                okip_admin_number_field(__('Umbral viewport', 'okip'), $base . '[animation][threshold]', isset($animation['threshold']) ? $animation['threshold'] : .16, __('0.16 dispara cuando una parte pequeña de la card ya es visible.', 'okip'), array('min' => .01, 'max' => 1, 'step' => '.01'));
                okip_admin_number_field(__('Desactivar bajo px', 'okip'), $base . '[animation][disable_below]', isset($animation['disable_below']) ? $animation['disable_below'] : 0, __('0 mantiene la animación también en mobile.', 'okip'), array('min' => 0, 'max' => 9999, 'step' => '1'));
                ?>
            </div>
            <?php okip_admin_section_close(); ?>
        </div>
    </div>
    <?php
}
