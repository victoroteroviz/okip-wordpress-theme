<?php

/**
 * Esquema / defaults del bloque Hero.
 *
 * La data de cada instancia (config/pages/*.php) se mezcla sobre estos defaults,
 * así una instancia parcial nunca rompe el render.
 *
 * Escena de entrada (dos videos):
 *   1. `intro`  → video introductorio que se reproduce UNA vez al cargar.
 *   2. `loop`   → video en bucle que toma el relevo (crossfade, sin parpadeo).
 * El intro y el loop coordinan solo el fondo; tarjetas y texto entran por un
 * temporizador configurable, sin depender de que el video termine.
 * El loop queda vivo en bucle. La escena NO se reinicia al volver al Hero
 * (replay_on_enter = false): el intro solo se repite recargando la página.
 *
 * Whitelists:
 *   background.type  : css_motion | video | image | svg | gradient
 *   card.type        : video | image | svg | gif
 *
 * Regla del fondo: video por defecto con asset del tema. El fondo CSS editable
 * y media image/svg siguen disponibles como alternativas limpias. El overlay es
 * una capa separada y opcional, no un reemplazo del fondo.
 *
 * Las funciones se declaran ANTES del return (con function_exists) porque el
 * archivo se incluye para obtener su array de defaults.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('okip_hero_gif_duration_ms')) {
    /**
     * Duración conocida de los GIF por default; permite reproducir un ciclo.
     *
     * @param string $media Ruta/URL del GIF.
     * @return int
     */
    function okip_hero_gif_duration_ms($media)
    {
        $media = strtolower((string) $media);
        if (strpos($media, 'carro.gif') !== false) {
            return 3320;
        }
        if (strpos($media, 'mapa%201.gif') !== false || strpos($media, 'mapa 1.gif') !== false) {
            return 3560;
        }
        if (strpos($media, 'reconocimiento.gif') !== false) {
            return 30080;
        }
        return 4000;
    }
}

if (! function_exists('okip_hero_card_defaults')) {
    /**
     * Defaults de una sola tarjeta del Hero.
     *
     * @return array
     */
    function okip_hero_card_defaults()
    {
        return array(
            'id'                  => '',
            'active'              => true,
            'type'                => 'image', // video | image | svg | gif
            'media'               => '',
            'poster'              => '',
            'alt'                 => '',
            'x'                   => 50.0,     // % (0..100) — solo desktop
            'y'                   => 50.0,     // % (0..100) — solo desktop
            'width_pct'           => 14.0,     // ancho en vw (desktop); clamp 6..30
            'glow'                => true,
            'scanline'            => false,
            'placeholder_label'   => '',      // texto del placeholder temporal
            'placeholder_enabled' => true,    // mostrar placeholder si no hay media real
            // Reproducción: NUNCA autoplay al cargar/entrar. Solo por interacción.
            'play_mode'        => 'hover', // hover | tap | manual | disabled
            'play_duration_ms' => 0,       // GIF: duración de un ciclo; 0 = inferir por asset.
            'reset_on_leave'   => false,   // videos: al salir del hover puede reiniciar/pausar.
        );
    }
}

if (! function_exists('okip_normalize_hero_data')) {
    /**
     * Normalizador específico del Hero: valida whitelists y normaliza tarjetas.
     *
     * @param array $data Data ya mezclada con los defaults del bloque.
     * @return array
     */
    function okip_normalize_hero_data($data)
    {
        $align_allowed = array('left', 'center', 'right');
        $bg_allowed    = array('css_motion', 'video', 'image', 'svg', 'gradient');
        $card_allowed  = array('video', 'image', 'svg', 'gif');
        $play_allowed  = array('hover', 'tap', 'manual', 'disabled');

        // Contenido.
        $data['content']['alignment'] = okip_one_of($data['content']['alignment'], $align_allowed, 'center');

        // Logo (anidado en content).
        if (! isset($data['content']['logo']) || ! is_array($data['content']['logo'])) {
            $data['content']['logo'] = array();
        }
        $data['content']['logo']['enabled'] = okip_bool(isset($data['content']['logo']['enabled']) ? $data['content']['logo']['enabled'] : false);
        $data['content']['logo']['width']   = is_string($data['content']['logo']['width'] ?? '') ? $data['content']['logo']['width'] : '120px';

        // Fondo (video por default, CSS editable como alternativa).
        $bg = isset($data['background']) && is_array($data['background']) ? $data['background'] : array();
        if (isset($bg['type']) && $bg['type'] === 'svg' && empty($bg['media'])) {
            $bg['type'] = 'video';
        }
        $data['background'] = array(
            'type'                 => okip_one_of(isset($bg['type']) ? $bg['type'] : 'video', $bg_allowed, 'video'),
            'media'                => isset($bg['media']) ? $bg['media'] : '',
            'intro_media'          => isset($bg['intro_media']) ? $bg['intro_media'] : 'assets/video/hero/intro-video.mp4',
            'loop_media'           => isset($bg['loop_media']) ? $bg['loop_media'] : 'assets/video/hero/loop-video.mp4',
            'poster'               => isset($bg['poster']) ? $bg['poster'] : '',
            'fallback_image'       => isset($bg['fallback_image']) ? $bg['fallback_image'] : '',
            'object_position'      => isset($bg['object_position']) ? $bg['object_position'] : 'center center',
            'css_variant'          => okip_one_of(isset($bg['css_variant']) ? $bg['css_variant'] : 'liquid_aurora', array('future_grid', 'liquid_aurora', 'signal_field'), 'liquid_aurora'),
            'css_bg'               => sanitize_hex_color((string) (isset($bg['css_bg']) ? $bg['css_bg'] : '#020711')) ?: '#020711',
            'css_accent'           => sanitize_hex_color((string) (isset($bg['css_accent']) ? $bg['css_accent'] : '#ff5a14')) ?: '#ff5a14',
            'css_accent_2'         => sanitize_hex_color((string) (isset($bg['css_accent_2']) ? $bg['css_accent_2'] : '#3c8cff')) ?: '#3c8cff',
            'css_grid_opacity'     => okip_clamp_float(isset($bg['css_grid_opacity']) ? $bg['css_grid_opacity'] : .18, 0, 1),
            'css_scanline_opacity' => okip_clamp_float(isset($bg['css_scanline_opacity']) ? $bg['css_scanline_opacity'] : .12, 0, 1),
            'css_noise_opacity'    => okip_clamp_float(isset($bg['css_noise_opacity']) ? $bg['css_noise_opacity'] : .07, 0, 1),
            'css_motion_enabled'   => okip_bool(isset($bg['css_motion_enabled']) ? $bg['css_motion_enabled'] : true),
            'css_motion_intensity' => okip_clamp_float(isset($bg['css_motion_intensity']) ? $bg['css_motion_intensity'] : .34, 0, 1),
            'css_motion_speed'     => okip_clamp_float(isset($bg['css_motion_speed']) ? $bg['css_motion_speed'] : .82, .2, 3),
            'css_motion_interval'  => okip_clamp_float(isset($bg['css_motion_interval']) ? $bg['css_motion_interval'] : 8, 2, 20),
            'css_chroma_offset'    => okip_clamp_float(isset($bg['css_chroma_offset']) ? $bg['css_chroma_offset'] : 5, 0, 32),
        );

        // Intro (video introductorio, una sola vez).
        $data['intro']['enabled']      = okip_bool($data['intro']['enabled']);
        $data['intro']['play_once']    = okip_bool($data['intro']['play_once']);
        $data['intro']['fail_timeout'] = okip_clamp_int($data['intro']['fail_timeout'], 0, 20000);

        // Loop (video de bucle).
        $data['loop']['enabled']     = okip_bool($data['loop']['enabled']);
        $data['loop']['muted']       = okip_bool($data['loop']['muted']);
        $data['loop']['playsinline'] = okip_bool($data['loop']['playsinline']);
        $data['loop']['autoplay']    = okip_bool($data['loop']['autoplay']);
        $data['loop']['loop']        = okip_bool($data['loop']['loop']);

        // Overlay (capa separada).
        $data['overlay']['enabled'] = okip_bool($data['overlay']['enabled']);
        $data['overlay']['opacity'] = okip_clamp_float($data['overlay']['opacity'], 0, 1);

        // Transición intro → loop.
        $data['transition']['intro_to_loop_crossfade'] = okip_bool($data['transition']['intro_to_loop_crossfade']);
        $data['transition']['crossfade_duration']      = okip_clamp_int($data['transition']['crossfade_duration'], 0, 5000);
        $data['transition']['content_entry_delay']     = okip_clamp_int(
            isset($data['transition']['content_entry_delay']) ? $data['transition']['content_entry_delay'] : 900,
            0,
            60000
        );

        // Animaciones reusables.
        $data['motion'] = okip_normalize_motion(isset($data['motion']) ? $data['motion'] : array(), array('background', 'text', 'cards'));
        unset($data['reveal'], $data['animation']);

        // Tipografía reusable.
        $data['typography']['title'] = okip_normalize_typography(
            isset($data['typography']['title']) ? $data['typography']['title'] : array(),
            'hero_title'
        );
        $data['typography']['description'] = okip_normalize_typography(
            isset($data['typography']['description']) ? $data['typography']['description'] : array(),
            'hero_description'
        );

        // Tarjetas.
        $cards  = isset($data['cards']) && is_array($data['cards']) ? $data['cards'] : array();
        $result = array();
        foreach ($cards as $i => $card) {
            if (! is_array($card)) {
                continue;
            }
            $card = okip_merge_defaults($card, okip_hero_card_defaults());

            $card['id']     = okip_sanitize_instance_id(! empty($card['id']) ? $card['id'] : 'card-' . ($i + 1), 'card-' . ($i + 1));
            $card['active'] = okip_bool($card['active']);
            $card['type']   = okip_one_of($card['type'], $card_allowed, 'image');
            $card['x']      = okip_clamp_float($card['x'], 0, 100);
            $card['y']      = okip_clamp_float($card['y'], 0, 100);
            $card['width_pct'] = okip_clamp_float($card['width_pct'], 6, 30);
            $card['glow']                = okip_bool($card['glow']);
            $card['scanline']            = okip_bool($card['scanline']);
            $card['placeholder_enabled'] = okip_bool($card['placeholder_enabled']);
            $card['placeholder_label']   = is_string($card['placeholder_label']) ? $card['placeholder_label'] : '';
            $card['play_mode']        = okip_one_of($card['play_mode'], $play_allowed, 'hover');
            $card['play_duration_ms'] = okip_clamp_int(isset($card['play_duration_ms']) ? $card['play_duration_ms'] : 0, 0, 120000);
            if ($card['type'] === 'gif' && $card['play_duration_ms'] <= 0) {
                $card['play_duration_ms'] = okip_hero_gif_duration_ms(isset($card['media']) ? $card['media'] : '');
            }
            $card['reset_on_leave'] = okip_bool($card['reset_on_leave']);

            $result[] = $card;
        }
        $data['cards'] = $result;

        return $data;
    }
}

return array(
    'content' => array(
        'title_line_1' => '',
        'title_line_2' => '',
        'description'  => '',
        'alignment'    => 'center', // left | center | right
        'max_width'    => '1000px',
        'logo'         => array(
            'enabled' => false,
            'media'   => '',      // ruta/URL/ID del logo
            'alt'     => '',
            'width'   => '150px', // ancho configurable
        ),
    ),
    'background' => array(
        'type'            => 'video', // css_motion | video | image | svg | gradient
        'media'           => '',      // compat: media único (si no hay intro/loop, se usa como loop)
        'intro_media'     => 'assets/video/hero/intro-video.mp4', // ruta/URL/ID del video introductorio
        'loop_media'      => 'assets/video/hero/loop-video.mp4',  // ruta/URL/ID del video de bucle
        'poster'          => '',      // imagen de respaldo para los videos
        'fallback_image'  => '',      // imagen estática si los videos no cargan
        'object_position' => 'center center',
        'css_variant'          => 'liquid_aurora',
        'css_bg'               => '#020711',
        'css_accent'           => '#ff5a14',
        'css_accent_2'         => '#3c8cff',
        'css_grid_opacity'     => 0.18,
        'css_scanline_opacity' => 0.12,
        'css_noise_opacity'    => 0.07,
        'css_motion_enabled'   => true,
        'css_motion_intensity' => 0.34,
        'css_motion_speed'     => 0.82,
        'css_motion_interval'  => 8,
        'css_chroma_offset'    => 5,
    ),
    'intro' => array(
        'enabled'      => true,
        'media'        => '',   // override del intro (si vacío → background.intro_media)
        'fail_timeout' => 2500, // ms: si el intro no arranca, saltar al loop/fallback
        'play_once'    => true,
    ),
    'loop' => array(
        'enabled'     => true,
        'media'       => '',    // override del loop (si vacío → background.loop_media)
        'muted'       => true,
        'playsinline' => true,
        'autoplay'    => true,
        'loop'        => true,
    ),
    'overlay' => array(
        'enabled' => true,
        'color'   => '#020711',
        'opacity' => 0.35,  // 0..1 — capa ligera, no reemplaza el fondo
    ),
    'transition' => array(
        'intro_to_loop_crossfade' => true, // crossfade suave intro → loop (sin parpadeo)
        'crossfade_duration'      => 700,  // ms del crossfade
        'content_entry_delay'     => 900,  // ms desde que inicia el Hero hasta texto/tarjetas
    ),
    'cards' => array(
        array(
            'id'                  => 'hero-carro',
            'type'                => 'gif',
            'media'               => 'assets/gif/hero/carro.gif',
            'alt'                 => 'Animación de reportes vehiculares',
            'x'                   => 19,
            'y'                   => 24,
            'width_pct'           => 23,
            'glow'                => false,
            'scanline'            => false,
            'placeholder_enabled' => false,
            'play_mode'           => 'hover',
            'play_duration_ms'    => 3320,
            'reset_on_leave'      => true,
        ),
        array(
            'id'                  => 'hero-reconocimiento',
            'type'                => 'gif',
            'media'               => 'assets/gif/hero/Reconocimiento.gif',
            'alt'                 => 'Animación de reconocimiento de persona',
            'x'                   => 22,
            'y'                   => 74,
            'width_pct'           => 24,
            'glow'                => false,
            'scanline'            => false,
            'placeholder_enabled' => false,
            'play_mode'           => 'hover',
            'play_duration_ms'    => 30080,
            'reset_on_leave'      => true,
        ),
        array(
            'id'                  => 'hero-mapa',
            'type'                => 'gif',
            'media'               => 'assets/gif/hero/Mapa 1.gif',
            'alt'                 => 'Animación de mapa operativo',
            'x'                   => 78,
            'y'                   => 72,
            'width_pct'           => 25,
            'glow'                => false,
            'scanline'            => false,
            'placeholder_enabled' => false,
            'play_mode'           => 'hover',
            'play_duration_ms'    => 3560,
            'reset_on_leave'      => true,
        ),
    ),
    'typography' => array(
        'title'       => okip_typography_defaults('hero_title'),
        'description' => okip_typography_defaults('hero_description'),
    ),
    'motion' => okip_motion_defaults(array('background', 'text', 'cards')),
);
