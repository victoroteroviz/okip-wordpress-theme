<?php

/**
 * Partial admin: tarjeta del bloque Industry Carousel (industry-carousel).
 *
 * Cada tarjeta es un fieldset con cabecera (duplicar/eliminar) y campos de contenido
 * (título del botón, media y alt). Lo reutilizan las tarjetas existentes y la plantilla
 * `<template>` que el JS clona al añadir/duplicar (repeater compartido en
 * assets/js/admin-blocks.js, sin maqueta drag/drop).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza una tarjeta editable del bloque industry-carousel.
 *
 * @param string $item_base Base del name (p.ej. okip_blocks[inst][data][items][0]).
 * @param array  $item      Datos de la tarjeta (se completan con los defaults).
 * @param string $legend    Etiqueta visible de la tarjeta.
 * @return void
 */
function okip_admin_render_ic_item($item_base, array $item, $legend)
{
    if (function_exists('okip_ic_item_defaults')) {
        $item = array_merge(okip_ic_item_defaults(), $item);
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

        <?php okip_admin_section_open(__('Contenido', 'okip'), __('El título es el texto del botón. La media (imagen o video) es la tarjeta del carrusel.', 'okip')); ?>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_text_field(__('Título (texto del botón)', 'okip'), $item_base . '[title]', $item['title']);
            okip_admin_text_field(__('Texto alternativo (alt)', 'okip'), $item_base . '[alt]', $item['alt'], __('Descripción de la imagen para accesibilidad.', 'okip'));
            ?>
        </div>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_media_field(__('Imagen', 'okip'), $item_base . '[image]', $item['image'], __('Se guarda en items.image.', 'okip'));
            okip_admin_media_field(__('Video (opcional)', 'okip'), $item_base . '[video]', $item['video'], __('Si se define, tiene prioridad sobre la imagen.', 'okip'));
            ?>
        </div>
        <?php okip_admin_section_close(); ?>

        <?php okip_admin_section_open(__('Compatibilidad (legacy)', 'okip'), __('Campos del diseño anterior. El rediseño oscuro no los muestra; se conservan por compatibilidad.', 'okip')); ?>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_text_field(__('Texto naranja (legacy)', 'okip'), $item_base . '[orange_text]', $item['orange_text']);
            okip_admin_color_field(__('Color de título (legacy)', 'okip'), $item_base . '[title_color]', $item['title_color']);
            ?>
        </div>
        <?php okip_admin_section_close(); ?>
    </fieldset>
    <?php
}
