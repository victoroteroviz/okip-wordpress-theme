<?php

/**
 * Editor admin del bloque Hero.
 *
 * Extraído de inc/admin/admin-pages.php (reorganización estructural, sin cambios
 * de lógica). El render de cada tarjeta vive en inc/admin/partials/hero-cards.php
 * (okip_admin_render_hero_card).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Editor del bloque Hero.
 *
 * @param string $instance_id
 * @param array  $data
 * @return void
 */
function okip_render_admin_hero_editor($instance_id, array $data)
{
    $data = okip_normalize_block_data('hero', $data);
    $base = 'okip_blocks[' . $instance_id . '][data]';
    $content    = $data['content'];
    $background  = $data['background'];
    $overlay     = $data['overlay'];
    $transition  = $data['transition'];
    $intro       = $data['intro'];
    $loop        = $data['loop'];
    $motion      = $data['motion'];
    $typography  = $data['typography'];
    $cards = isset($data['cards']) && is_array($data['cards']) ? $data['cards'] : array();

    $bg_type   = $background['type'];
    $is_video  = ($bg_type === 'video');
    $is_css    = ($bg_type === 'css_motion');
    $is_img    = in_array($bg_type, array('image', 'svg'), true);
    $is_media  = in_array($bg_type, array('video', 'image', 'svg'), true);
    ?>

    <div class="okip-admin-tabs" data-okip-tabs>
        <div class="okip-admin-tabs__nav" role="tablist">
            <button type="button" class="okip-admin-tab-btn is-active" data-okip-tab-target="contenido"><?php esc_html_e('Contenido', 'okip'); ?></button>
            <button type="button" class="okip-admin-tab-btn" data-okip-tab-target="fondo"><?php esc_html_e('Fondo', 'okip'); ?></button>
            <button type="button" class="okip-admin-tab-btn" data-okip-tab-target="tarjetas"><?php esc_html_e('Tarjetas', 'okip'); ?></button>
            <button type="button" class="okip-admin-tab-btn" data-okip-tab-target="animacion"><?php esc_html_e('Animación', 'okip'); ?></button>
            <button type="button" class="okip-admin-tab-btn" data-okip-tab-target="avanzado"><?php esc_html_e('Avanzado', 'okip'); ?></button>
        </div>

        <!-- ===== CONTENIDO ===== -->
        <div class="okip-admin-tab-panel is-active" data-okip-tab="contenido">
            <?php okip_admin_section_open(__('Texto principal', 'okip'), __('Las dos líneas del título y la descripción del Hero.', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php
                okip_admin_text_field(__('Título línea 1', 'okip'), $base . '[content][title_line_1]', $content['title_line_1']);
                okip_admin_text_field(__('Título línea 2', 'okip'), $base . '[content][title_line_2]', $content['title_line_2']);
                ?>
            </div>
            <?php okip_admin_textarea_field(__('Descripción', 'okip'), $base . '[content][description]', $content['description']); ?>
            <?php okip_admin_section_close(); ?>

            <?php okip_admin_section_open(__('Ajustes básicos de letras', 'okip'), __('Alineación y ancho del bloque de texto.', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php
                okip_admin_select_field(__('Alineación', 'okip'), $base . '[content][alignment]', $content['alignment'], array(
                    'center' => __('Centro', 'okip'),
                    'left'   => __('Izquierda', 'okip'),
                    'right'  => __('Derecha', 'okip'),
                ));
                okip_admin_text_field(__('Ancho máximo', 'okip'), $base . '[content][max_width]', $content['max_width'], __('Ej. 1000px, 80vw.', 'okip'));
                ?>
            </div>
            <?php okip_admin_section_close(); ?>

            <?php okip_admin_section_open(__('Entrada del contenido', 'okip'), __('Cuándo aparecen texto y tarjetas; es independiente de que el video termine.', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php
                okip_admin_number_field(__('Espera de entrada (ms)', 'okip'), $base . '[transition][content_entry_delay]', $transition['content_entry_delay'], __('Milisegundos desde que inicia el Hero hasta disparar texto y tarjetas.', 'okip'), array('min' => 0, 'max' => 60000, 'step' => '50'));
                ?>
            </div>
            <?php okip_admin_section_close(); ?>

            <?php
            okip_admin_details_open(__('Tipografía avanzada', 'okip'));
            okip_admin_typography_group(__('Tipografía título', 'okip'), $base . '[typography][title]', $typography['title'], $content['title_line_1'] . ' ' . $content['title_line_2']);
            okip_admin_typography_group(__('Tipografía descripción', 'okip'), $base . '[typography][description]', $typography['description'], $content['description'] ?: __('Texto descriptivo del Hero', 'okip'));
            okip_admin_details_close();
            ?>
        </div>

        <!-- ===== FONDO ===== -->
        <div class="okip-admin-tab-panel" data-okip-tab="fondo">
            <?php okip_admin_section_open(__('Tipo de fondo', 'okip'), __('Determina qué controles aparecen abajo.', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php
                okip_admin_select_field(__('Tipo de fondo', 'okip'), $base . '[background][type]', $background['type'], array(
                    'video'      => __('Video intro/loop', 'okip'),
                    'css_motion' => __('Fondo CSS editable', 'okip'),
                    'image'      => __('Imagen', 'okip'),
                    'svg'        => __('SVG externo', 'okip'),
                    'gradient'   => __('Fallback neutro', 'okip'),
                ));
                ?>
            </div>
            <?php okip_admin_section_close(); ?>

            <!-- Video: loop, intro, poster, fallback y crossfade -->
            <div data-okip-when-bg="video"<?php echo $is_video ? '' : ' hidden'; ?>>
                <?php okip_admin_section_open(__('Video', 'okip'), __('El loop es el fondo permanente; el intro (opcional) se reproduce una vez y hace crossfade al loop.', 'okip')); ?>
                <div class="okip-admin-grid okip-admin-grid--two">
                    <?php
                    okip_admin_media_field(__('Loop video', 'okip'), $base . '[background][loop_media]', $background['loop_media'], __('Fondo principal en bucle.', 'okip'));
                    okip_admin_media_field(__('Intro video', 'okip'), $base . '[background][intro_media]', $background['intro_media'], __('Opcional; se reproduce una sola vez al cargar.', 'okip'));
                    okip_admin_media_field(__('Poster', 'okip'), $base . '[background][poster]', $background['poster'], __('Imagen mientras el video carga.', 'okip'));
                    okip_admin_media_field(__('Fallback image', 'okip'), $base . '[background][fallback_image]', $background['fallback_image'], __('Imagen estática si el video no puede reproducirse.', 'okip'));
                    okip_admin_checkbox_field(__('Crossfade intro → loop', 'okip'), $base . '[transition][intro_to_loop_crossfade]', $transition['intro_to_loop_crossfade']);
                    okip_admin_number_field(__('Duración crossfade (ms)', 'okip'), $base . '[transition][crossfade_duration]', $transition['crossfade_duration'], '', array('min' => 0, 'max' => 5000, 'step' => '50'));
                    ?>
                </div>
                <?php okip_admin_section_close(); ?>
            </div>

            <!-- Imagen / SVG: archivo único -->
            <div data-okip-when-bg="image svg"<?php echo $is_img ? '' : ' hidden'; ?>>
                <?php okip_admin_section_open(__('Imagen / SVG', 'okip'), __('Archivo único usado como fondo.', 'okip')); ?>
                <div class="okip-admin-grid okip-admin-grid--two">
                    <?php okip_admin_media_field(__('Imagen / SVG externo', 'okip'), $base . '[background][media]', $background['media']); ?>
                </div>
                <?php okip_admin_section_close(); ?>
            </div>

            <!-- Encuadre del media (video/image/svg) -->
            <div data-okip-when-bg="video image svg"<?php echo $is_media ? '' : ' hidden'; ?>>
                <?php okip_admin_section_open(__('Encuadre', 'okip'), __('Posición del media dentro del Hero.', 'okip')); ?>
                <div class="okip-admin-grid okip-admin-grid--two">
                    <?php okip_admin_text_field(__('Object position', 'okip'), $base . '[background][object_position]', $background['object_position'], __('Ej. center center, top, 50% 30%.', 'okip')); ?>
                </div>
                <?php okip_admin_section_close(); ?>
            </div>

            <!-- Fondo CSS editable: variante, colores, intensidad y velocidad -->
            <div data-okip-when-bg="css_motion"<?php echo $is_css ? '' : ' hidden'; ?>>
                <?php okip_admin_section_open(__('Fondo CSS', 'okip'), __('Variante, colores, intensidad y velocidad del fondo animado por CSS.', 'okip')); ?>
                <div class="okip-admin-grid okip-admin-grid--two">
                    <?php
                    okip_admin_select_field(__('Variante', 'okip'), $base . '[background][css_variant]', $background['css_variant'], array(
                        'liquid_aurora' => __('Aurora líquida', 'okip'),
                        'future_grid'   => __('Grid futurista', 'okip'),
                        'signal_field'  => __('Campo de señal', 'okip'),
                    ));
                    okip_admin_checkbox_field(__('Movimiento activo', 'okip'), $base . '[background][css_motion_enabled]', $background['css_motion_enabled']);
                    okip_admin_color_field(__('Fondo base', 'okip'), $base . '[background][css_bg]', $background['css_bg']);
                    okip_admin_color_field(__('Acento azul', 'okip'), $base . '[background][css_accent]', $background['css_accent']);
                    okip_admin_color_field(__('Acento claro', 'okip'), $base . '[background][css_accent_2]', $background['css_accent_2']);
                    okip_admin_number_field(__('Intensidad', 'okip'), $base . '[background][css_motion_intensity]', $background['css_motion_intensity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
                    okip_admin_number_field(__('Velocidad', 'okip'), $base . '[background][css_motion_speed]', $background['css_motion_speed'], __('1 = normal, mayor = más rápido.', 'okip'), array('min' => .2, 'max' => 3, 'step' => '.05'));
                    ?>
                </div>
                <?php
                okip_admin_details_open(__('Detalles CSS', 'okip'));
                echo '<div class="okip-admin-grid okip-admin-grid--two">';
                okip_admin_number_field(__('Opacidad grid', 'okip'), $base . '[background][css_grid_opacity]', $background['css_grid_opacity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
                okip_admin_number_field(__('Opacidad scanlines', 'okip'), $base . '[background][css_scanline_opacity]', $background['css_scanline_opacity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
                okip_admin_number_field(__('Opacidad noise', 'okip'), $base . '[background][css_noise_opacity]', $background['css_noise_opacity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
                okip_admin_number_field(__('Chroma offset (px)', 'okip'), $base . '[background][css_chroma_offset]', $background['css_chroma_offset'], '', array('min' => 0, 'max' => 32, 'step' => '.5'));
                okip_admin_number_field(__('Intervalo pulso (s)', 'okip'), $base . '[background][css_motion_interval]', $background['css_motion_interval'], __('Segundos entre micro desplazamientos o pulsos.', 'okip'), array('min' => 2, 'max' => 20, 'step' => '.25'));
                echo '</div>';
                okip_admin_details_close();
                okip_admin_section_close();
                ?>
            </div>

            <!-- Overlay: capa de color sobre cualquier fondo -->
            <?php okip_admin_section_open(__('Overlay', 'okip'), __('Capa de color opcional sobre el fondo; no lo reemplaza.', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php
                okip_admin_checkbox_field(__('Overlay activo', 'okip'), $base . '[overlay][enabled]', $overlay['enabled']);
                okip_admin_color_field(__('Color overlay', 'okip'), $base . '[overlay][color]', $overlay['color']);
                okip_admin_number_field(__('Opacidad overlay', 'okip'), $base . '[overlay][opacity]', $overlay['opacity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
                ?>
            </div>
            <?php okip_admin_section_close(); ?>
        </div>

        <!-- ===== TARJETAS ===== -->
        <div class="okip-admin-tab-panel" data-okip-tab="tarjetas">
            <div class="okip-admin-cards" data-okip-cards data-okip-max="10">
                <div class="okip-admin-stage" data-okip-stage aria-hidden="true"></div>
                <p class="description"><?php esc_html_e('Arrastra las tarjetas en la maqueta para posicionarlas; usa la esquina inferior derecha para redimensionar. X/Y y ancho aplican en escritorio; en móvil el Hero usa un layout apilado.', 'okip'); ?></p>
                <div class="okip-admin-cards__list" data-okip-cards-list>
                    <?php foreach ($cards as $i => $card) : ?>
                        <?php okip_admin_render_hero_card($base . '[cards][' . (int) $i . ']', $card, isset($card['id']) ? $card['id'] : 'card-' . ($i + 1)); ?>
                    <?php endforeach; ?>
                </div>
                <p class="okip-admin-cards__actions">
                    <button type="button" class="button button-secondary" data-okip-card-add><?php esc_html_e('Añadir tarjeta', 'okip'); ?></button>
                    <span class="description" data-okip-card-count></span>
                </p>
                <template data-okip-card-template>
                    <?php okip_admin_render_hero_card($base . '[cards][__INDEX__]', okip_hero_card_defaults(), __('Nueva tarjeta', 'okip')); ?>
                </template>
            </div>
        </div>

        <!-- ===== ANIMACIÓN ===== -->
        <div class="okip-admin-tab-panel" data-okip-tab="animacion">
            <?php okip_admin_section_open(__('Controles globales', 'okip'), __('Activan el sistema y definen repetición y salida para todos los targets.', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php
                okip_admin_checkbox_field(__('Sistema activo', 'okip'), $base . '[motion][enabled]', $motion['enabled']);
                okip_admin_select_field(__('Replay', 'okip'), $base . '[motion][replay_mode]', $motion['replay_mode'], okip_motion_replay_options());
                okip_admin_select_field(__('Salida', 'okip'), $base . '[motion][exit_trigger]', $motion['exit_trigger'], okip_motion_exit_trigger_options());
                ?>
            </div>
            <?php okip_admin_section_close(); ?>
            <?php
            okip_admin_motion_target_group(__('Fondo', 'okip'), $base . '[motion][background]', $motion['background'], 'background', true, true);
            okip_admin_motion_target_group(__('Letras', 'okip'), $base . '[motion][text]', $motion['text'], 'text', false);
            okip_admin_motion_target_group(__('Tarjetas', 'okip'), $base . '[motion][cards]', $motion['cards'], 'cards', true);
            ?>
        </div>

        <!-- ===== AVANZADO ===== -->
        <div class="okip-admin-tab-panel" data-okip-tab="avanzado">
            <p class="okip-admin-section__desc"><?php esc_html_e('Opciones técnicas de reproducción de los videos de fondo. Cambia solo si sabes lo que haces.', 'okip'); ?></p>

            <?php okip_admin_section_open(__('Intro (video de entrada)', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php
                okip_admin_checkbox_field(__('Intro activo', 'okip'), $base . '[intro][enabled]', $intro['enabled']);
                okip_admin_checkbox_field(__('Reproducir una sola vez', 'okip'), $base . '[intro][play_once]', $intro['play_once']);
                okip_admin_number_field(__('Timeout de fallo (ms)', 'okip'), $base . '[intro][fail_timeout]', $intro['fail_timeout'], __('Si el intro no arranca, saltar al loop tras este tiempo.', 'okip'), array('min' => 0, 'max' => 20000, 'step' => '50'));
                ?>
            </div>
            <?php okip_admin_section_close(); ?>

            <?php okip_admin_section_open(__('Loop (video en bucle)', 'okip')); ?>
            <div class="okip-admin-grid okip-admin-grid--two">
                <?php
                okip_admin_checkbox_field(__('Loop activo', 'okip'), $base . '[loop][enabled]', $loop['enabled']);
                okip_admin_checkbox_field(__('Autoplay', 'okip'), $base . '[loop][autoplay]', $loop['autoplay']);
                okip_admin_checkbox_field(__('Repetir en bucle', 'okip'), $base . '[loop][loop]', $loop['loop']);
                okip_admin_checkbox_field(__('Silenciado', 'okip'), $base . '[loop][muted]', $loop['muted']);
                okip_admin_checkbox_field(__('Playsinline', 'okip'), $base . '[loop][playsinline]', $loop['playsinline']);
                ?>
            </div>
            <?php okip_admin_section_close(); ?>
        </div>
    </div>
    <?php
}
