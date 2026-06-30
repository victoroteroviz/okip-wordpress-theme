<?php

/**
 * Partial admin: tarjeta del bloque Hero.
 *
 * Extraído de inc/admin/admin-pages.php (reorganización estructural, sin cambios
 * de lógica). Lo usa el editor del Hero (inc/admin/editors/hero.php).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza una tarjeta del Hero (reutilizado por las tarjetas existentes y por la
 * plantilla `<template>` que el JS clona al añadir/duplicar).
 *
 * @param string $card_base Base del name (p.ej. okip_blocks[inst][data][cards][0]).
 * @param array  $card      Datos de la tarjeta (se completan con los defaults).
 * @param string $legend    Etiqueta visible (el JS la sincroniza con el ID).
 * @return void
 */
function okip_admin_render_hero_card($card_base, array $card, $legend)
{
    if (function_exists('okip_hero_card_defaults')) {
        $card = okip_merge_defaults($card, okip_hero_card_defaults());
    }
    $is_video = ($card['type'] === 'video');
    $is_gif   = ($card['type'] === 'gif');
    ?>
    <fieldset class="okip-admin-card okip-admin-panel okip-admin-panel--nested" data-okip-card>
        <div class="okip-admin-card__head">
            <legend class="okip-admin-card__title" data-okip-card-legend><?php echo esc_html($legend); ?></legend>
            <span class="okip-admin-card__tools">
                <button type="button" class="button-link" data-okip-card-dup><?php esc_html_e('Duplicar', 'okip'); ?></button>
                <button type="button" class="button-link okip-admin-card__remove" data-okip-card-remove><?php esc_html_e('Eliminar', 'okip'); ?></button>
            </span>
        </div>

        <?php okip_admin_section_open(__('Estado y contenido', 'okip')); ?>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_text_field(__('ID', 'okip'), $card_base . '[id]', $card['id'], __('Identificador único de la tarjeta (sirve de ancla y scope).', 'okip'));
            okip_admin_checkbox_field(__('Activa', 'okip'), $card_base . '[active]', $card['active']);
            okip_admin_text_field(__('Texto alternativo', 'okip'), $card_base . '[alt]', $card['alt'], __('Descripción accesible del contenido.', 'okip'));
            okip_admin_checkbox_field(__('Mostrar placeholder', 'okip'), $card_base . '[placeholder_enabled]', $card['placeholder_enabled'], __('Si no hay media real, dibuja un recuadro geométrico en su lugar.', 'okip'));
            okip_admin_text_field(__('Etiqueta del placeholder', 'okip'), $card_base . '[placeholder_label]', $card['placeholder_label'], __('Texto mostrado dentro del placeholder.', 'okip'));
            ?>
        </div>
        <?php okip_admin_section_close(); ?>

        <?php okip_admin_section_open(__('Media', 'okip')); ?>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_select_field(__('Tipo', 'okip'), $card_base . '[type]', $card['type'], array(
                'image' => __('Imagen', 'okip'),
                'gif'   => __('GIF', 'okip'),
                'video' => __('Video', 'okip'),
                'svg'   => __('SVG', 'okip'),
            ));
            okip_admin_media_field(__('Media', 'okip'), $card_base . '[media]', $card['media']);
            ?>
        </div>
        <div class="okip-admin-grid okip-admin-grid--two" data-okip-when-card-type="video"<?php echo $is_video ? '' : ' hidden'; ?>>
            <?php okip_admin_media_field(__('Poster', 'okip'), $card_base . '[poster]', $card['poster'], __('Imagen mientras el video carga (solo tarjetas de video).', 'okip')); ?>
        </div>
        <?php okip_admin_section_close(); ?>

        <?php okip_admin_section_open(__('Reproducción', 'okip'), __('Controla cómo se activan los medios animados de esta tarjeta.', 'okip')); ?>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_select_field(__('Modo', 'okip'), $card_base . '[play_mode]', $card['play_mode'], array(
                'hover'    => __('Hover', 'okip'),
                'disabled' => __('Desactivado', 'okip'),
            ), __('En GIF, hover reproduce un ciclo completo y bloquea nuevos disparos hasta terminar.', 'okip'));
            ?>
        </div>
        <div class="okip-admin-grid okip-admin-grid--two" data-okip-when-card-type="gif"<?php echo $is_gif ? '' : ' hidden'; ?>>
            <?php
            okip_admin_number_field(__('Duración GIF (ms)', 'okip'), $card_base . '[play_duration_ms]', $card['play_duration_ms'], __('Duración de un ciclo. 0 intenta usar la duración conocida del asset.', 'okip'), array('min' => 0, 'max' => 120000, 'step' => '10'));
            ?>
        </div>
        <div class="okip-admin-grid okip-admin-grid--two" data-okip-when-card-type="video"<?php echo $is_video ? '' : ' hidden'; ?>>
            <?php okip_admin_checkbox_field(__('Reiniciar video al salir', 'okip'), $card_base . '[reset_on_leave]', $card['reset_on_leave']); ?>
        </div>
        <?php okip_admin_section_close(); ?>

        <?php okip_admin_section_open(__('Posición', 'okip'), __('Coordenadas y ancho en escritorio (en móvil el Hero apila las tarjetas).', 'okip')); ?>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_number_field(__('X %', 'okip'), $card_base . '[x]', $card['x'], '', array('min' => 0, 'max' => 100, 'step' => 'any'));
            okip_admin_number_field(__('Y %', 'okip'), $card_base . '[y]', $card['y'], '', array('min' => 0, 'max' => 100, 'step' => 'any'));
            okip_admin_number_field(__('Ancho vw', 'okip'), $card_base . '[width_pct]', $card['width_pct'], '', array('min' => 6, 'max' => 30, 'step' => 'any'));
            ?>
        </div>
        <?php okip_admin_section_close(); ?>

        <?php okip_admin_section_open(__('Apariencia', 'okip')); ?>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_checkbox_field(__('Glow', 'okip'), $card_base . '[glow]', $card['glow']);
            okip_admin_checkbox_field(__('Scanline', 'okip'), $card_base . '[scanline]', $card['scanline']);
            ?>
        </div>
        <?php okip_admin_section_close(); ?>
    </fieldset>
    <?php
}
