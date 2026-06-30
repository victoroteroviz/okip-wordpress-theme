<?php

/**
 * Partial admin: cuadro de texto del bloque Video con Título (video-w-title).
 *
 * Espeja la estructura de inc/admin/partials/hero-cards.php: cada cuadro es un
 * fieldset con cabecera (duplicar/eliminar) y secciones de estado, posición y
 * tipografía. Lo reutilizan los cuadros existentes y la plantilla `<template>` que
 * el JS clona al añadir/duplicar (maqueta drag/drop compartida con el Hero).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza un cuadro de texto editable del bloque video-w-title.
 *
 * @param string $box_base Base del name (p.ej. okip_blocks[inst][data][text_boxes][0]).
 * @param array  $box      Datos del cuadro (se completan con los defaults).
 * @param string $legend   Etiqueta visible (el JS la sincroniza con el ID).
 * @return void
 */
function okip_admin_render_vwt_text_box($box_base, array $box, $legend)
{
    if (function_exists('okip_vwt_text_box_defaults')) {
        $box = okip_merge_defaults($box, okip_vwt_text_box_defaults());
    }
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
            okip_admin_text_field(__('ID', 'okip'), $box_base . '[id]', $box['id'], __('Identificador único del cuadro (scope y referencia).', 'okip'));
            okip_admin_checkbox_field(__('Activo', 'okip'), $box_base . '[active]', $box['active']);
            okip_admin_select_field(__('Rol', 'okip'), $box_base . '[role]', $box['role'], array(
                'title'    => __('Título', 'okip'),
                'subtitle' => __('Subtítulo', 'okip'),
                'text'     => __('Texto', 'okip'),
            ));
            ?>
        </div>
        <?php okip_admin_textarea_field(__('Contenido', 'okip'), $box_base . '[content]', $box['content']); ?>
        <?php okip_admin_section_close(); ?>

        <?php okip_admin_section_open(__('Posición y tamaño', 'okip'), __('Coordenadas en % sobre la escena (ancla en el centro). En móvil los cuadros se apilan.', 'okip')); ?>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_number_field(__('X %', 'okip'), $box_base . '[x]', $box['x'], '', array('min' => 0, 'max' => 100, 'step' => 'any'));
            okip_admin_number_field(__('Y %', 'okip'), $box_base . '[y]', $box['y'], '', array('min' => 0, 'max' => 100, 'step' => 'any'));
            okip_admin_number_field(__('Ancho %', 'okip'), $box_base . '[width_pct]', $box['width_pct'], '', array('min' => 5, 'max' => 100, 'step' => 'any'));
            okip_admin_number_field(__('Alto px', 'okip'), $box_base . '[height_px]', $box['height_px'], __('0 = alto automático por contenido.', 'okip'), array('min' => 0, 'max' => 1200, 'step' => '1'));
            okip_admin_select_field(__('Alineación', 'okip'), $box_base . '[align]', $box['align'], array(
                'left'   => __('Izquierda', 'okip'),
                'center' => __('Centro', 'okip'),
                'right'  => __('Derecha', 'okip'),
            ));
            ?>
        </div>
        <?php okip_admin_section_close(); ?>

        <?php okip_admin_section_open(__('Tipografía', 'okip')); ?>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_font_search_field(__('Fuente', 'okip'), $box_base . '[font_family]', $box['font_family'], $box['content'] !== '' ? $box['content'] : __('Texto de ejemplo', 'okip'));
            okip_admin_number_field(__('Tamaño px', 'okip'), $box_base . '[font_size_px]', $box['font_size_px'], '', array('min' => 8, 'max' => 200, 'step' => '.5'));
            okip_admin_number_field(__('Peso', 'okip'), $box_base . '[font_weight]', $box['font_weight'], '', array('min' => 100, 'max' => 900, 'step' => 100));
            okip_admin_color_field(__('Color', 'okip'), $box_base . '[color]', $box['color'] ?: '#ffffff');
            okip_admin_number_field(__('Line-height', 'okip'), $box_base . '[line_height]', $box['line_height'], '', array('min' => .8, 'max' => 3, 'step' => '.01'));
            okip_admin_number_field(__('Letter-spacing px', 'okip'), $box_base . '[letter_spacing]', $box['letter_spacing'], '', array('min' => -5, 'max' => 20, 'step' => '.1'));
            ?>
        </div>
        <?php okip_admin_section_close(); ?>
    </fieldset>
    <?php
}
