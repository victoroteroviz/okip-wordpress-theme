<?php

/**
 * Editor admin del bloque Video con Título (video-w-title).
 *
 * Pestañas: Texto (cuadros posicionables con maqueta drag/drop), Video (selección
 * del video principal) y Animación (toggle del reveal de entrada, el único modelo
 * de animación que consume el frontend del bloque). El render de cada cuadro vive en
 * inc/admin/partials/vwt-text-boxes.php (okip_admin_render_vwt_text_box).
 *
 * La maqueta reutiliza el motor de tarjetas del Hero (assets/js/admin-blocks.js) vía
 * data-okip-cards con la colección `text_boxes`.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Editor del bloque Video con Título.
 *
 * @param string $instance_id
 * @param array  $data
 * @return void
 */
function okip_render_admin_video_w_title_editor($instance_id, array $data)
{
    $data = okip_normalize_block_data('video-w-title', $data);
    $base = 'okip_blocks[' . $instance_id . '][data]';

    $video     = isset($data['video']) && is_array($data['video']) ? $data['video'] : array();
    $animation = isset($data['animation']) && is_array($data['animation']) ? $data['animation'] : array();
    $boxes     = isset($data['text_boxes']) && is_array($data['text_boxes']) ? $data['text_boxes'] : array();
    ?>

    <div class="okip-admin-tabs" data-okip-tabs>
        <div class="okip-admin-tabs__nav" role="tablist">
            <button type="button" class="okip-admin-tab-btn is-active" data-okip-tab-target="texto"><?php esc_html_e('Texto', 'okip'); ?></button>
            <button type="button" class="okip-admin-tab-btn" data-okip-tab-target="video"><?php esc_html_e('Video', 'okip'); ?></button>
            <button type="button" class="okip-admin-tab-btn" data-okip-tab-target="animacion"><?php esc_html_e('Animación', 'okip'); ?></button>
        </div>

        <!-- ===== TEXTO ===== -->
        <div class="okip-admin-tab-panel is-active" data-okip-tab="texto">
            <div
                class="okip-admin-cards okip-admin-cards--text"
                data-okip-cards
                data-okip-cards-key="text_boxes"
                data-okip-cards-idprefix="box"
                data-okip-cards-variant="text"
                data-okip-max="12">
                <div class="okip-admin-stage okip-admin-stage--text" data-okip-stage aria-hidden="true"></div>
                <p class="description"><?php esc_html_e('Arrastra los cuadros en la maqueta para posicionarlos; usa la esquina inferior derecha para ajustar el ancho. X/Y y ancho aplican en escritorio; en móvil los cuadros se apilan.', 'okip'); ?></p>
                <div class="okip-admin-cards__list" data-okip-cards-list>
                    <?php foreach ($boxes as $i => $box) : ?>
                        <?php okip_admin_render_vwt_text_box($base . '[text_boxes][' . (int) $i . ']', $box, isset($box['id']) ? $box['id'] : 'box-' . ($i + 1)); ?>
                    <?php endforeach; ?>
                </div>
                <p class="okip-admin-cards__actions">
                    <button type="button" class="button button-secondary" data-okip-card-add><?php esc_html_e('Añadir cuadro', 'okip'); ?></button>
                    <span class="description" data-okip-card-count></span>
                </p>
                <template data-okip-card-template>
                    <?php okip_admin_render_vwt_text_box($base . '[text_boxes][__INDEX__]', okip_vwt_text_box_defaults(), __('Nuevo cuadro', 'okip')); ?>
                </template>
            </div>
        </div>

        <!-- ===== VIDEO ===== -->
        <div class="okip-admin-tab-panel" data-okip-tab="video">
            <?php okip_admin_section_open(__('Video principal', 'okip'), __('Video de fondo a sangre completa. Si no existe el media, el bloque cae a un fondo sobrio (color sólido).', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php okip_admin_media_field(__('Video', 'okip'), $base . '[video][media]', isset($video['media']) ? $video['media'] : '', __('Se guarda en video.media.', 'okip')); ?>
            </div>
            <?php okip_admin_section_close(); ?>
        </div>

        <!-- ===== ANIMACIÓN ===== -->
        <div class="okip-admin-tab-panel" data-okip-tab="animacion">
            <?php okip_admin_section_open(__('Reveal de entrada', 'okip'), __('Fade/translate del texto al entrar la escena al viewport. El script del bloque lo arma y dispara; si falla, el texto queda visible.', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php okip_admin_checkbox_field(__('Animación activa', 'okip'), $base . '[animation][enabled]', ! empty($animation['enabled'])); ?>
            </div>
            <?php okip_admin_section_close(); ?>
        </div>
    </div>
    <?php
}
