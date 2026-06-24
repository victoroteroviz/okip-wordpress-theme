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
    $content = $data['content'];
    $background = $data['background'];
    $overlay = $data['overlay'];
    $transition = $data['transition'];
    $motion = $data['motion'];
    $typography = $data['typography'];
    $cards = isset($data['cards']) && is_array($data['cards']) ? $data['cards'] : array();
    ?>

    <details class="okip-admin-panel" open>
        <summary><?php esc_html_e('Contenido', 'okip'); ?></summary>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_text_field(__('Título línea 1', 'okip'), $base . '[content][title_line_1]', $content['title_line_1']);
            okip_admin_text_field(__('Título línea 2', 'okip'), $base . '[content][title_line_2]', $content['title_line_2']);
            okip_admin_select_field(__('Alineación', 'okip'), $base . '[content][alignment]', $content['alignment'], array(
                'center' => __('Centro', 'okip'),
                'left'   => __('Izquierda', 'okip'),
                'right'  => __('Derecha', 'okip'),
            ));
            okip_admin_text_field(__('Ancho máximo', 'okip'), $base . '[content][max_width]', $content['max_width']);
            ?>
        </div>
        <?php okip_admin_textarea_field(__('Descripción', 'okip'), $base . '[content][description]', $content['description']); ?>
    </details>

    <details class="okip-admin-panel" open>
        <summary><?php esc_html_e('Tipografía', 'okip'); ?></summary>
        <?php
        okip_admin_typography_group(__('Título', 'okip'), $base . '[typography][title]', $typography['title'], $content['title_line_1'] . ' ' . $content['title_line_2']);
        okip_admin_typography_group(__('Descripción', 'okip'), $base . '[typography][description]', $typography['description'], $content['description'] ?: __('Texto descriptivo del Hero', 'okip'));
        ?>
    </details>

    <details class="okip-admin-panel" open>
        <summary><?php esc_html_e('Fondo CSS / media', 'okip'); ?></summary>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_select_field(__('Tipo de fondo', 'okip'), $base . '[background][type]', $background['type'], array(
                'css_motion' => __('Fondo CSS editable', 'okip'),
                'video'      => __('Video intro/loop', 'okip'),
                'image'      => __('Imagen', 'okip'),
                'svg'        => __('SVG externo', 'okip'),
                'gradient'   => __('Fallback neutro', 'okip'),
            ));
            okip_admin_select_field(__('Variante CSS', 'okip'), $base . '[background][css_variant]', $background['css_variant'], array(
                'liquid_aurora' => __('Aurora líquida', 'okip'),
                'future_grid'   => __('Grid futurista', 'okip'),
                'signal_field'  => __('Campo de señal', 'okip'),
            ));
            okip_admin_color_field(__('Fondo CSS', 'okip'), $base . '[background][css_bg]', $background['css_bg']);
            okip_admin_color_field(__('Acento azul', 'okip'), $base . '[background][css_accent]', $background['css_accent']);
            okip_admin_color_field(__('Acento claro', 'okip'), $base . '[background][css_accent_2]', $background['css_accent_2']);
            okip_admin_number_field(__('Opacidad grid', 'okip'), $base . '[background][css_grid_opacity]', $background['css_grid_opacity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
            okip_admin_number_field(__('Opacidad scanlines', 'okip'), $base . '[background][css_scanline_opacity]', $background['css_scanline_opacity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
            okip_admin_number_field(__('Opacidad noise', 'okip'), $base . '[background][css_noise_opacity]', $background['css_noise_opacity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
            okip_admin_checkbox_field(__('Movimiento CSS activo', 'okip'), $base . '[background][css_motion_enabled]', $background['css_motion_enabled']);
            okip_admin_number_field(__('Intensidad fondo', 'okip'), $base . '[background][css_motion_intensity]', $background['css_motion_intensity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
            okip_admin_number_field(__('Velocidad fondo', 'okip'), $base . '[background][css_motion_speed]', $background['css_motion_speed'], __('1 = normal, mayor = mas rapido.', 'okip'), array('min' => .2, 'max' => 3, 'step' => '.05'));
            okip_admin_number_field(__('Intervalo pulso', 'okip'), $base . '[background][css_motion_interval]', $background['css_motion_interval'], __('Segundos entre micro desplazamientos o pulsos.', 'okip'), array('min' => 2, 'max' => 20, 'step' => '.25'));
            okip_admin_number_field(__('Chroma offset px', 'okip'), $base . '[background][css_chroma_offset]', $background['css_chroma_offset'], '', array('min' => 0, 'max' => 32, 'step' => '.5'));
            okip_admin_text_field(__('Object position', 'okip'), $base . '[background][object_position]', $background['object_position']);
            okip_admin_media_field(__('Media único / imagen / SVG externo', 'okip'), $base . '[background][media]', $background['media']);
            okip_admin_media_field(__('Intro video', 'okip'), $base . '[background][intro_media]', $background['intro_media']);
            okip_admin_media_field(__('Loop video', 'okip'), $base . '[background][loop_media]', $background['loop_media']);
            okip_admin_media_field(__('Poster', 'okip'), $base . '[background][poster]', $background['poster']);
            okip_admin_media_field(__('Fallback image', 'okip'), $base . '[background][fallback_image]', $background['fallback_image']);
            ?>
        </div>
    </details>

    <details class="okip-admin-panel">
        <summary><?php esc_html_e('Overlay y transición', 'okip'); ?></summary>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_checkbox_field(__('Overlay activo', 'okip'), $base . '[overlay][enabled]', $overlay['enabled']);
            okip_admin_color_field(__('Color overlay', 'okip'), $base . '[overlay][color]', $overlay['color']);
            okip_admin_number_field(__('Opacidad overlay', 'okip'), $base . '[overlay][opacity]', $overlay['opacity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
            okip_admin_checkbox_field(__('Crossfade intro/loop', 'okip'), $base . '[transition][intro_to_loop_crossfade]', $transition['intro_to_loop_crossfade']);
            okip_admin_number_field(__('Duración crossfade', 'okip'), $base . '[transition][crossfade_duration]', $transition['crossfade_duration'], '', array('min' => 0, 'max' => 5000, 'step' => '50'));
            ?>
        </div>
    </details>

    <details class="okip-admin-panel">
        <summary><?php esc_html_e('Animaciones', 'okip'); ?></summary>
        <div class="okip-admin-grid okip-admin-grid--two">
            <?php
            okip_admin_checkbox_field(__('Sistema activo', 'okip'), $base . '[motion][enabled]', $motion['enabled']);
            okip_admin_select_field(__('Replay', 'okip'), $base . '[motion][replay_mode]', $motion['replay_mode'], okip_motion_replay_options());
            okip_admin_select_field(__('Salida', 'okip'), $base . '[motion][exit_trigger]', $motion['exit_trigger'], okip_motion_exit_trigger_options());
            ?>
        </div>
        <?php
        okip_admin_motion_target_group(__('Fondo', 'okip'), $base . '[motion][background]', $motion['background'], 'background', true);
        okip_admin_motion_target_group(__('Letras', 'okip'), $base . '[motion][text]', $motion['text'], 'text', false);
        okip_admin_motion_target_group(__('Tarjetas', 'okip'), $base . '[motion][cards]', $motion['cards'], 'cards', true);
        ?>
    </details>

    <details class="okip-admin-panel">
        <summary><?php esc_html_e('Tarjetas', 'okip'); ?></summary>
        <?php foreach ($cards as $i => $card) : ?>
            <?php $card_base = $base . '[cards][' . (int) $i . ']'; ?>
            <fieldset class="okip-admin-panel okip-admin-panel--nested">
                <legend><?php echo esc_html(isset($card['id']) ? $card['id'] : 'card-' . ($i + 1)); ?></legend>
                <div class="okip-admin-grid okip-admin-grid--two">
                    <?php
                    okip_admin_text_field(__('ID', 'okip'), $card_base . '[id]', $card['id']);
                    okip_admin_checkbox_field(__('Activa', 'okip'), $card_base . '[active]', $card['active']);
                    okip_admin_select_field(__('Tipo', 'okip'), $card_base . '[type]', $card['type'], array(
                        'video' => __('Video', 'okip'),
                        'image' => __('Imagen', 'okip'),
                        'svg'   => __('SVG', 'okip'),
                    ));
                    okip_admin_media_field(__('Media', 'okip'), $card_base . '[media]', $card['media']);
                    okip_admin_media_field(__('Poster', 'okip'), $card_base . '[poster]', $card['poster']);
                    okip_admin_text_field(__('Alt', 'okip'), $card_base . '[alt]', $card['alt']);
                    okip_admin_number_field(__('X %', 'okip'), $card_base . '[x]', $card['x'], '', array('min' => 0, 'max' => 100, 'step' => '.5'));
                    okip_admin_number_field(__('Y %', 'okip'), $card_base . '[y]', $card['y'], '', array('min' => 0, 'max' => 100, 'step' => '.5'));
                    okip_admin_checkbox_field(__('Glow', 'okip'), $card_base . '[glow]', $card['glow']);
                    okip_admin_checkbox_field(__('Scanline', 'okip'), $card_base . '[scanline]', $card['scanline']);
                    okip_admin_checkbox_field(__('Placeholder', 'okip'), $card_base . '[placeholder_enabled]', $card['placeholder_enabled']);
                    okip_admin_text_field(__('Label placeholder', 'okip'), $card_base . '[placeholder_label]', $card['placeholder_label']);
                    ?>
                </div>
            </fieldset>
        <?php endforeach; ?>
    </details>
    <?php
}
