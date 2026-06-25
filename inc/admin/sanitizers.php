<?php

/**
 * Saneadores del panel admin.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Limpia una referencia de medio (ID, URL o ruta relativa).
 *
 * @param mixed $value
 * @return string
 */
function okip_admin_sanitize_media_ref($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (ctype_digit($value)) {
        return $value;
    }
    if (preg_match('#^https?://#', $value)) {
        return esc_url_raw($value);
    }
    return sanitize_text_field($value);
}

/**
 * Tamaños CSS simples para campos de layout.
 *
 * @param mixed  $value
 * @param string $default
 * @return string
 */
function okip_admin_sanitize_css_size($value, $default)
{
    $value = trim((string) $value);
    if (preg_match('/^\d+(\.\d+)?(px|rem|em|vw|vh|svh|%)$/', $value)) {
        return $value;
    }
    return $default;
}

/**
 * Sanea los overrides de una página completa.
 *
 * @param string $slug
 * @param mixed  $raw_blocks
 * @return array
 */
function okip_admin_sanitize_page_overrides($slug, $raw_blocks)
{
    $slug = sanitize_file_name($slug);
    $raw_blocks = is_array($raw_blocks) ? $raw_blocks : array();
    $config_file = okip_page_config_file($slug);
    $base_blocks = is_readable($config_file) ? include $config_file : array();
    $overrides = okip_get_page_block_overrides($slug);

    if (! is_array($base_blocks)) {
        return is_array($overrides) ? $overrides : array();
    }

    foreach ($base_blocks as $block) {
        if (empty($block['instance_id']) || empty($block['type'])) {
            continue;
        }
        $instance_id = okip_sanitize_instance_id($block['instance_id'], $block['type']);
        if (! isset($raw_blocks[$instance_id]) || ! is_array($raw_blocks[$instance_id])) {
            continue;
        }

        $raw_data = isset($raw_blocks[$instance_id]['data']) && is_array($raw_blocks[$instance_id]['data'])
            ? $raw_blocks[$instance_id]['data']
            : array();

        if ($block['type'] === 'hero') {
            $base_data = isset($block['data']) ? $block['data'] : array();
            $full = okip_admin_sanitize_hero_data($raw_data, $base_data);
            // Solo persistir el diff mínimo contra el config base normalizado: así un
            // cambio futuro en config/ no queda enmascarado por un snapshot completo.
            $base_norm = okip_normalize_block_data('hero', $base_data);
            $diff = okip_array_diff_recursive($full, $base_norm);
            if (! empty($diff)) {
                $overrides[$instance_id] = array('type' => 'hero', 'data' => $diff);
            } else {
                unset($overrides[$instance_id]);
            }
        }
    }

    return is_array($overrides) ? $overrides : array();
}

/**
 * Sanea la data editable del Hero.
 *
 * @param array $raw
 * @param array $base
 * @return array
 */
function okip_admin_sanitize_hero_data(array $raw, array $base = array())
{
    $current = okip_normalize_block_data('hero', $base);
    $data = array();

    $content = isset($raw['content']) && is_array($raw['content']) ? $raw['content'] : array();
    $data['content'] = array(
        'title_line_1' => isset($content['title_line_1']) ? sanitize_text_field((string) $content['title_line_1']) : '',
        'title_line_2' => isset($content['title_line_2']) ? sanitize_text_field((string) $content['title_line_2']) : '',
        'description'  => isset($content['description']) ? wp_kses_post((string) $content['description']) : '',
        'alignment'    => okip_one_of(isset($content['alignment']) ? $content['alignment'] : 'center', array('left', 'center', 'right'), 'center'),
        'max_width'    => okip_admin_sanitize_css_size(isset($content['max_width']) ? $content['max_width'] : '1000px', '1000px'),
    );

    $background = isset($raw['background']) && is_array($raw['background']) ? $raw['background'] : array();
    // Defaults = valor normalizado actual del bloque (fuente única; sin literales
    // divergentes). Si un campo no llega en el POST, se conserva el valor vigente.
    $bg_def = $current['background'];
    $data['background'] = array(
        'type'                 => okip_one_of(isset($background['type']) ? $background['type'] : $bg_def['type'], array('css_motion', 'video', 'image', 'svg', 'gradient'), $bg_def['type']),
        'media'                => okip_admin_sanitize_media_ref(isset($background['media']) ? $background['media'] : $bg_def['media']),
        'intro_media'          => okip_admin_sanitize_media_ref(isset($background['intro_media']) ? $background['intro_media'] : $bg_def['intro_media']),
        'loop_media'           => okip_admin_sanitize_media_ref(isset($background['loop_media']) ? $background['loop_media'] : $bg_def['loop_media']),
        'poster'               => okip_admin_sanitize_media_ref(isset($background['poster']) ? $background['poster'] : $bg_def['poster']),
        'fallback_image'       => okip_admin_sanitize_media_ref(isset($background['fallback_image']) ? $background['fallback_image'] : $bg_def['fallback_image']),
        'object_position'      => isset($background['object_position']) ? sanitize_text_field((string) $background['object_position']) : $bg_def['object_position'],
        'css_variant'          => okip_one_of(isset($background['css_variant']) ? $background['css_variant'] : $bg_def['css_variant'], array('future_grid', 'liquid_aurora', 'signal_field'), 'liquid_aurora'),
        'css_bg'               => sanitize_hex_color(isset($background['css_bg']) ? $background['css_bg'] : $bg_def['css_bg']) ?: $bg_def['css_bg'],
        'css_accent'           => sanitize_hex_color(isset($background['css_accent']) ? $background['css_accent'] : $bg_def['css_accent']) ?: $bg_def['css_accent'],
        'css_accent_2'         => sanitize_hex_color(isset($background['css_accent_2']) ? $background['css_accent_2'] : $bg_def['css_accent_2']) ?: $bg_def['css_accent_2'],
        'css_grid_opacity'     => okip_clamp_float(isset($background['css_grid_opacity']) ? $background['css_grid_opacity'] : $bg_def['css_grid_opacity'], 0, 1),
        'css_scanline_opacity' => okip_clamp_float(isset($background['css_scanline_opacity']) ? $background['css_scanline_opacity'] : $bg_def['css_scanline_opacity'], 0, 1),
        'css_noise_opacity'    => okip_clamp_float(isset($background['css_noise_opacity']) ? $background['css_noise_opacity'] : $bg_def['css_noise_opacity'], 0, 1),
        'css_motion_enabled'   => okip_bool(isset($background['css_motion_enabled']) ? $background['css_motion_enabled'] : $bg_def['css_motion_enabled']),
        'css_motion_intensity' => okip_clamp_float(isset($background['css_motion_intensity']) ? $background['css_motion_intensity'] : $bg_def['css_motion_intensity'], 0, 1),
        'css_motion_speed'     => okip_clamp_float(isset($background['css_motion_speed']) ? $background['css_motion_speed'] : $bg_def['css_motion_speed'], .2, 3),
        'css_motion_interval'  => okip_clamp_float(isset($background['css_motion_interval']) ? $background['css_motion_interval'] : $bg_def['css_motion_interval'], 2, 20),
        'css_chroma_offset'    => okip_clamp_float(isset($background['css_chroma_offset']) ? $background['css_chroma_offset'] : $bg_def['css_chroma_offset'], 0, 32),
    );

    $overlay = isset($raw['overlay']) && is_array($raw['overlay']) ? $raw['overlay'] : array();
    $ov_def = $current['overlay'];
    $data['overlay'] = array(
        'enabled' => okip_bool(isset($overlay['enabled']) ? $overlay['enabled'] : $ov_def['enabled']),
        'color'   => sanitize_hex_color(isset($overlay['color']) ? $overlay['color'] : $ov_def['color']) ?: $ov_def['color'],
        'opacity' => okip_clamp_float(isset($overlay['opacity']) ? $overlay['opacity'] : $ov_def['opacity'], 0, 1),
    );

    $typography = isset($raw['typography']) && is_array($raw['typography']) ? $raw['typography'] : array();
    $data['typography'] = array(
        'title' => okip_normalize_typography(isset($typography['title']) ? $typography['title'] : array(), 'hero_title'),
        'description' => okip_normalize_typography(isset($typography['description']) ? $typography['description'] : array(), 'hero_description'),
    );

    $transition = isset($raw['transition']) && is_array($raw['transition']) ? $raw['transition'] : array();
    $tr_def = $current['transition'];
    $data['transition'] = array(
        'intro_to_loop_crossfade' => okip_bool(isset($transition['intro_to_loop_crossfade']) ? $transition['intro_to_loop_crossfade'] : $tr_def['intro_to_loop_crossfade']),
        'crossfade_duration'      => okip_clamp_int(isset($transition['crossfade_duration']) ? $transition['crossfade_duration'] : $tr_def['crossfade_duration'], 0, 5000),
        'content_entry_delay'     => okip_clamp_int(isset($transition['content_entry_delay']) ? $transition['content_entry_delay'] : $tr_def['content_entry_delay'], 0, 60000),
    );

    // Intro / Loop: toggles técnicos de reproducción (pestaña Avanzado). Solo se
    // editan los flags; las rutas de media viven en el grupo background.
    $intro = isset($raw['intro']) && is_array($raw['intro']) ? $raw['intro'] : array();
    $in_def = $current['intro'];
    $data['intro'] = array(
        'enabled'      => okip_bool(isset($intro['enabled']) ? $intro['enabled'] : $in_def['enabled']),
        'media'        => isset($in_def['media']) ? $in_def['media'] : '',
        'fail_timeout' => okip_clamp_int(isset($intro['fail_timeout']) ? $intro['fail_timeout'] : $in_def['fail_timeout'], 0, 20000),
        'play_once'    => okip_bool(isset($intro['play_once']) ? $intro['play_once'] : $in_def['play_once']),
    );

    $loop = isset($raw['loop']) && is_array($raw['loop']) ? $raw['loop'] : array();
    $lp_def = $current['loop'];
    $data['loop'] = array(
        'enabled'     => okip_bool(isset($loop['enabled']) ? $loop['enabled'] : $lp_def['enabled']),
        'media'       => isset($lp_def['media']) ? $lp_def['media'] : '',
        'muted'       => okip_bool(isset($loop['muted']) ? $loop['muted'] : $lp_def['muted']),
        'playsinline' => okip_bool(isset($loop['playsinline']) ? $loop['playsinline'] : $lp_def['playsinline']),
        'autoplay'    => okip_bool(isset($loop['autoplay']) ? $loop['autoplay'] : $lp_def['autoplay']),
        'loop'        => okip_bool(isset($loop['loop']) ? $loop['loop'] : $lp_def['loop']),
    );

    $data['motion'] = okip_normalize_motion(isset($raw['motion']) ? $raw['motion'] : array(), array('background', 'text', 'cards'));

    $raw_cards = isset($raw['cards']) && is_array($raw['cards']) ? $raw['cards'] : array();
    $data['cards'] = array();
    foreach ($raw_cards as $i => $card) {
        if (! is_array($card)) {
            continue;
        }
        if (count($data['cards']) >= 10) {
            break; // Tope de 10 tarjetas por instancia.
        }
        $data['cards'][] = array(
            'id'                  => okip_sanitize_instance_id(isset($card['id']) ? $card['id'] : 'card-' . ($i + 1), 'card-' . ($i + 1)),
            'active'              => okip_bool(isset($card['active']) ? $card['active'] : false),
            'type'                => okip_one_of(isset($card['type']) ? $card['type'] : 'image', array('video', 'image', 'svg', 'gif'), 'image'),
            'media'               => okip_admin_sanitize_media_ref(isset($card['media']) ? $card['media'] : ''),
            'poster'              => okip_admin_sanitize_media_ref(isset($card['poster']) ? $card['poster'] : ''),
            'alt'                 => isset($card['alt']) ? sanitize_text_field((string) $card['alt']) : '',
            'x'                   => okip_clamp_float(isset($card['x']) ? $card['x'] : 50, 0, 100),
            'y'                   => okip_clamp_float(isset($card['y']) ? $card['y'] : 50, 0, 100),
            'width_pct'           => okip_clamp_float(isset($card['width_pct']) ? $card['width_pct'] : 14, 6, 30),
            'glow'                => okip_bool(isset($card['glow']) ? $card['glow'] : false),
            'scanline'            => okip_bool(isset($card['scanline']) ? $card['scanline'] : false),
            'placeholder_label'   => isset($card['placeholder_label']) ? sanitize_text_field((string) $card['placeholder_label']) : '',
            'placeholder_enabled' => okip_bool(isset($card['placeholder_enabled']) ? $card['placeholder_enabled'] : false),
            'play_mode'           => okip_one_of(isset($card['play_mode']) ? $card['play_mode'] : 'hover', array('hover', 'tap', 'manual'), 'hover'),
            'reset_on_leave'      => okip_bool(isset($card['reset_on_leave']) ? $card['reset_on_leave'] : false),
        );
    }

    return okip_normalize_block_data('hero', okip_merge_defaults($data, $current));
}
