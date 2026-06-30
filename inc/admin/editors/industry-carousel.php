<?php

/**
 * Editor admin del bloque Industry Carousel (industry-carousel).
 *
 * Pestañas: Tarjetas (repeater de ítems = botón + media, hasta 20) y Animación
 * (toggles del pin/scroll-driven y el flag del bloque legacy show_intro). El render
 * de cada tarjeta vive en inc/admin/partials/ic-items.php (okip_admin_render_ic_item).
 *
 * El repeater reutiliza el motor de tarjetas (assets/js/admin-blocks.js) vía
 * data-okip-cards con la colección `items` y sin maqueta (no hay data-okip-stage).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Editor del bloque Industry Carousel.
 *
 * @param string $instance_id
 * @param array  $data
 * @return void
 */
function okip_render_admin_industry_carousel_editor($instance_id, array $data)
{
    $data = okip_normalize_block_data('industry-carousel', $data);
    $base = 'okip_blocks[' . $instance_id . '][data]';

    $items     = isset($data['items']) && is_array($data['items']) ? $data['items'] : array();
    $layout    = isset($data['layout']) && is_array($data['layout']) ? $data['layout'] : array();
    $animation = isset($data['animation']) && is_array($data['animation']) ? $data['animation'] : array();
    ?>

    <div class="okip-admin-tabs" data-okip-tabs>
        <div class="okip-admin-tabs__nav" role="tablist">
            <button type="button" class="okip-admin-tab-btn is-active" data-okip-tab-target="tarjetas"><?php esc_html_e('Tarjetas', 'okip'); ?></button>
            <button type="button" class="okip-admin-tab-btn" data-okip-tab-target="animacion"><?php esc_html_e('Animación', 'okip'); ?></button>
        </div>

        <!-- ===== TARJETAS ===== -->
        <div class="okip-admin-tab-panel is-active" data-okip-tab="tarjetas">
            <div
                class="okip-admin-cards"
                data-okip-cards
                data-okip-cards-key="items"
                data-okip-cards-idprefix="item"
                data-okip-max="20">
                <p class="description"><?php esc_html_e('Cada tarjeta es un botón (su título) y una media en el carrusel. Hasta 20.', 'okip'); ?></p>
                <div class="okip-admin-cards__list" data-okip-cards-list>
                    <?php foreach ($items as $i => $item) : ?>
                        <?php
                        $legend = ! empty($item['title']) ? $item['title'] : sprintf(__('Tarjeta %d', 'okip'), (int) $i + 1);
                        okip_admin_render_ic_item($base . '[items][' . (int) $i . ']', $item, $legend);
                        ?>
                    <?php endforeach; ?>
                </div>
                <p class="okip-admin-cards__actions">
                    <button type="button" class="button button-secondary" data-okip-card-add><?php esc_html_e('Añadir tarjeta', 'okip'); ?></button>
                    <span class="description" data-okip-card-count></span>
                </p>
                <template data-okip-card-template>
                    <?php okip_admin_render_ic_item($base . '[items][__INDEX__]', okip_ic_item_defaults(), __('Nueva tarjeta', 'okip')); ?>
                </template>
            </div>
        </div>

        <!-- ===== ANIMACIÓN ===== -->
        <div class="okip-admin-tab-panel" data-okip-tab="animacion">
            <?php okip_admin_section_open(__('Scroll-driven', 'okip'), __('En escritorio el bloque se pina y el scroll vertical mueve el carrusel. En móvil/tablet cae a scroll horizontal nativo.', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php
                okip_admin_checkbox_field(__('Animación activa', 'okip'), $base . '[animation][enabled]', ! empty($animation['enabled']));
                okip_admin_checkbox_field(__('Pin en escritorio', 'okip'), $base . '[animation][pin_enabled]', ! empty($animation['pin_enabled']));
                ?>
            </div>
            <?php okip_admin_section_close(); ?>

            <?php okip_admin_section_open(__('Bloque legacy', 'okip'), __('Muestra el encabezado, subtítulo, texto dinámico y CTA del diseño anterior. Apagado por defecto.', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php okip_admin_checkbox_field(__('Mostrar intro (legacy)', 'okip'), $base . '[layout][show_intro]', ! empty($layout['show_intro'])); ?>
            </div>
            <?php okip_admin_section_close(); ?>
        </div>
    </div>
    <?php
}
