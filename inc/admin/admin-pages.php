<?php

/**
 * Panel admin de bloques OKIP.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Slugs de páginas configurables.
 *
 * @return string[]
 */
function okip_admin_page_slugs()
{
    $files = glob(OKIP_DIR . '/config/pages/*.php');
    $slugs = array();
    if (is_array($files)) {
        foreach ($files as $file) {
            $slugs[] = basename($file, '.php');
        }
    }
    sort($slugs);
    return $slugs;
}

/**
 * Registra la página admin.
 *
 * @return void
 */
function okip_register_admin_pages()
{
    add_menu_page(
        __('OKIP Blocks', 'okip'),
        __('OKIP Blocks', 'okip'),
        'manage_options',
        'okip-blocks',
        'okip_render_blocks_admin_page',
        'dashicons-layout',
        58
    );
}
add_action('admin_menu', 'okip_register_admin_pages');

/**
 * Assets del panel.
 *
 * @param string $hook
 * @return void
 */
function okip_admin_enqueue_assets($hook)
{
    if ($hook !== 'toplevel_page_okip-blocks') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style(
        'okip-admin-blocks',
        OKIP_URI . '/assets/css/admin-blocks.css',
        array(),
        okip_asset_version(OKIP_DIR . '/assets/css/admin-blocks.css')
    );
    wp_enqueue_script(
        'okip-admin-blocks',
        OKIP_URI . '/assets/js/admin-blocks.js',
        array(),
        okip_asset_version(OKIP_DIR . '/assets/js/admin-blocks.js'),
        true
    );
    wp_localize_script('okip-admin-blocks', 'OKIP_ADMIN', array(
        'fonts' => okip_google_fonts_catalog(),
    ));
}
add_action('admin_enqueue_scripts', 'okip_admin_enqueue_assets');

/**
 * Render principal.
 *
 * @return void
 */
function okip_render_blocks_admin_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $available_slugs = okip_admin_page_slugs();
    $slug = isset($_REQUEST['okip_page_slug']) ? sanitize_file_name(wp_unslash($_REQUEST['okip_page_slug'])) : 'home';
    if (! in_array($slug, $available_slugs, true)) {
        $slug = in_array('home', $available_slugs, true) ? 'home' : (isset($available_slugs[0]) ? $available_slugs[0] : '');
    }

    $notice = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('okip_blocks_admin', 'okip_blocks_nonce')) {
        if (isset($_POST['okip_refresh_fonts'])) {
            $count = okip_refresh_google_fonts_catalog();
            $notice = sprintf(__('Catálogo de fuentes actualizado: %d fuentes disponibles.', 'okip'), $count);
        } elseif (isset($_POST['okip_save_blocks'])) {
            $raw_blocks = isset($_POST['okip_blocks']) ? wp_unslash($_POST['okip_blocks']) : array();
            $overrides = okip_admin_sanitize_page_overrides($slug, $raw_blocks);
            update_option(okip_page_overrides_option_key($slug), $overrides, false);
            $notice = __('Cambios guardados.', 'okip');
        }
    }

    $blocks = $slug !== '' ? okip_get_page_blocks($slug) : array();
    ?>
    <div class="wrap okip-admin">
        <h1><?php esc_html_e('OKIP Blocks', 'okip'); ?></h1>
        <?php if ($notice !== '') : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
        <?php endif; ?>

        <form method="get" class="okip-admin-toolbar">
            <input type="hidden" name="page" value="okip-blocks">
            <label>
                <span><?php esc_html_e('Página', 'okip'); ?></span>
                <select name="okip_page_slug" onchange="this.form.submit()">
                    <?php foreach ($available_slugs as $page_slug) : ?>
                        <option value="<?php echo esc_attr($page_slug); ?>" <?php selected($slug, $page_slug); ?>><?php echo esc_html($page_slug); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <form method="post" class="okip-admin-form">
            <?php wp_nonce_field('okip_blocks_admin', 'okip_blocks_nonce'); ?>
            <input type="hidden" name="okip_page_slug" value="<?php echo esc_attr($slug); ?>">

            <div class="okip-admin-actions">
                <button type="submit" name="okip_save_blocks" class="button button-primary"><?php esc_html_e('Guardar cambios', 'okip'); ?></button>
                <button type="submit" name="okip_refresh_fonts" class="button"><?php esc_html_e('Refrescar catálogo de fuentes', 'okip'); ?></button>
            </div>

            <?php foreach ($blocks as $block) : ?>
                <?php
                $type = isset($block['type']) ? $block['type'] : '';
                $instance_id = isset($block['instance_id']) ? $block['instance_id'] : $type;
                ?>
                <section class="okip-admin-block">
                    <header class="okip-admin-block__head">
                        <span><?php echo esc_html($type); ?></span>
                        <code><?php echo esc_html($instance_id); ?></code>
                    </header>
                    <?php if ($type === 'hero') : ?>
                        <?php okip_render_admin_hero_editor($instance_id, isset($block['data']) ? $block['data'] : array()); ?>
                    <?php else : ?>
                        <p class="description"><?php esc_html_e('Este bloque se muestra para contexto. Su editor se añadirá usando los mismos campos reutilizables.', 'okip'); ?></p>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>

            <div class="okip-admin-actions okip-admin-actions--bottom">
                <button type="submit" name="okip_save_blocks" class="button button-primary button-large"><?php esc_html_e('Guardar cambios', 'okip'); ?></button>
            </div>
        </form>
    </div>
    <?php
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

        <?php okip_admin_section_open(__('Posición', 'okip'), __('Coordenadas y ancho en escritorio (en móvil el Hero apila las tarjetas).', 'okip')); ?>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_number_field(__('X %', 'okip'), $card_base . '[x]', $card['x'], '', array('min' => 0, 'max' => 100, 'step' => '.5'));
            okip_admin_number_field(__('Y %', 'okip'), $card_base . '[y]', $card['y'], '', array('min' => 0, 'max' => 100, 'step' => '.5'));
            okip_admin_number_field(__('Ancho vw', 'okip'), $card_base . '[width_pct]', $card['width_pct'], '', array('min' => 6, 'max' => 30, 'step' => '.5'));
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
