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
 * Mientras corre el intro las tarjetas y el texto permanecen ocultos; al terminar
 * el intro se hace crossfade al loop, luego se revelan tarjetas y después texto.
 * El loop queda vivo en bucle. La escena NO se reinicia al volver al Hero
 * (replay_on_enter = false): el intro solo se repite recargando la página.
 *
 * Whitelists:
 *   background.type  : css_glitch | video | image | svg | gradient
 *   card.type        : video | image | svg
 *
 * Regla del fondo: `css_glitch` es el fondo editable por defecto. Media
 * (video/image/svg) sigue disponible como alternativa limpia. El overlay es una
 * capa separada y opcional, no un reemplazo del fondo.
 *
 * Las funciones se declaran ANTES del return (con function_exists) porque el
 * archivo se incluye para obtener su array de defaults.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
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
            'type'                => 'image', // video | image | svg
            'media'               => '',
            'poster'              => '',
            'alt'                 => '',
            'x'                   => 50.0,     // % (0..100) — solo desktop
            'y'                   => 50.0,     // % (0..100) — solo desktop
            'glow'                => true,
            'scanline'            => false,
            'placeholder_label'   => '',      // texto del placeholder temporal
            'placeholder_enabled' => true,    // mostrar placeholder si no hay media real
            // Reproducción: NUNCA autoplay al cargar/entrar. Solo por interacción.
            'play_mode'      => 'hover', // hover | tap | manual
            'reset_on_leave' => false,   // al salir del hover NO reinicia/pausa (continúa)
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
        $bg_allowed    = array('css_glitch', 'video', 'image', 'svg', 'gradient');
        $card_allowed  = array('video', 'image', 'svg');
        $play_allowed  = array('hover', 'tap', 'manual');

        // Contenido.
        $data['content']['alignment'] = okip_one_of($data['content']['alignment'], $align_allowed, 'center');

        // Fondo (CSS editable por default, media intro/loop como alternativa).
        if (isset($data['background']['type']) && $data['background']['type'] === 'svg' && empty($data['background']['media'])) {
            $data['background']['type'] = 'css_glitch';
        }
        $data['background']['type'] = okip_one_of($data['background']['type'], $bg_allowed, 'css_glitch');
        $data['background']['css_variant'] = okip_one_of($data['background']['css_variant'], array('glitch_grid'), 'glitch_grid');
        $data['background']['css_bg'] = sanitize_hex_color((string) $data['background']['css_bg']) ?: '#020711';
        $data['background']['css_accent'] = sanitize_hex_color((string) $data['background']['css_accent']) ?: '#00a9ff';
        $data['background']['css_accent_2'] = sanitize_hex_color((string) $data['background']['css_accent_2']) ?: '#6ee7ff';
        $data['background']['css_grid_opacity'] = okip_clamp_float($data['background']['css_grid_opacity'], 0, 1);
        $data['background']['css_scanline_opacity'] = okip_clamp_float($data['background']['css_scanline_opacity'], 0, 1);
        $data['background']['css_noise_opacity'] = okip_clamp_float($data['background']['css_noise_opacity'], 0, 1);
        $data['background']['css_glitch_enabled'] = okip_bool($data['background']['css_glitch_enabled']);
        $data['background']['css_glitch_intensity'] = okip_clamp_float($data['background']['css_glitch_intensity'], 0, 1);
        $data['background']['css_glitch_speed'] = okip_clamp_float($data['background']['css_glitch_speed'], 0.2, 3);
        $data['background']['css_glitch_interval'] = okip_clamp_float($data['background']['css_glitch_interval'], 2, 20);
        $data['background']['css_chroma_offset'] = okip_clamp_float($data['background']['css_chroma_offset'], 0, 32);

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

        // Reveal (tiempos medidos tras el intro).
        $data['reveal']['reveal_after_intro']      = okip_bool($data['reveal']['reveal_after_intro']);
        $data['reveal']['image_reveal_delay']      = okip_clamp_int($data['reveal']['image_reveal_delay'], 0, 20000);
        $data['reveal']['cards_delay_after_intro'] = okip_clamp_int($data['reveal']['cards_delay_after_intro'], 0, 10000);
        $data['reveal']['text_delay_after_intro']  = okip_clamp_int($data['reveal']['text_delay_after_intro'], 0, 10000);
        $data['reveal']['pause_or_blur_on_fail']   = okip_bool($data['reveal']['pause_or_blur_on_fail']);

        // Transición intro → loop.
        $data['transition']['intro_to_loop_crossfade'] = okip_bool($data['transition']['intro_to_loop_crossfade']);
        $data['transition']['crossfade_duration']      = okip_clamp_int($data['transition']['crossfade_duration'], 0, 5000);

        // Animación.
        $data['animation']['enabled']   = okip_bool($data['animation']['enabled']);
        $data['animation']['scroll_3d'] = okip_bool($data['animation']['scroll_3d']);

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
            $card['glow']                = okip_bool($card['glow']);
            $card['scanline']            = okip_bool($card['scanline']);
            $card['placeholder_enabled'] = okip_bool($card['placeholder_enabled']);
            $card['placeholder_label']   = is_string($card['placeholder_label']) ? $card['placeholder_label'] : '';
            $card['play_mode']      = okip_one_of($card['play_mode'], $play_allowed, 'hover');
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
    ),
    'background' => array(
        'type'            => 'css_glitch', // css_glitch | video | image | svg | gradient
        'media'           => '',      // compat: media único (si no hay intro/loop, se usa como loop)
        'intro_media'     => '',      // ruta/URL/ID del video introductorio
        'loop_media'      => '',      // ruta/URL/ID del video de bucle
        'poster'          => '',      // imagen de respaldo para los videos
        'fallback_image'  => '',      // imagen estática si los videos no cargan
        'object_position' => 'center center',
        'css_variant'          => 'glitch_grid',
        'css_bg'               => '#020711',
        'css_accent'           => '#00a9ff',
        'css_accent_2'         => '#6ee7ff',
        'css_grid_opacity'     => 0.24,
        'css_scanline_opacity' => 0.16,
        'css_noise_opacity'    => 0.10,
        'css_glitch_enabled'   => true,
        'css_glitch_intensity' => 0.42,
        'css_glitch_speed'     => 1,
        'css_glitch_interval'  => 7,
        'css_chroma_offset'    => 8,
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
    'reveal' => array(
        'reveal_after_intro'      => true, // revelar tarjetas/texto al terminar el intro
        'image_reveal_delay'      => 1000, // ms (image/svg/gradient o sin intro/loop)
        'cards_delay_after_intro' => 300,  // ms tras el fin del intro → tarjetas
        'text_delay_after_intro'  => 600,  // ms tras el fin del intro → texto
        'pause_or_blur_on_fail'   => true, // al fallar sin loop/fallback: degradar el fondo
    ),
    'transition' => array(
        'intro_to_loop_crossfade' => true, // crossfade suave intro → loop (sin parpadeo)
        'crossfade_duration'      => 700,  // ms del crossfade
    ),
    'cards' => array(
        // Lista de tarjetas multimedia flotantes (ver okip_hero_card_defaults()).
    ),
    'typography' => array(
        'title'       => okip_typography_defaults('hero_title'),
        'description' => okip_typography_defaults('hero_description'),
    ),
    'animation' => array(
        'enabled'   => true,
        'scroll_3d' => true, // efecto 3D al hacer scroll (ScrollTrigger)
    ),
);
