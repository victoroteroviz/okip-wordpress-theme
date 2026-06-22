<?php

/**
 * Esquema / defaults del bloque Parallax Monitor (Bloque 2).
 *
 * Escena oscura full-screen (>=100svh) que ENTRA SOBRE el bloque anterior (Hero)
 * conforme el usuario hace scroll, no desde el primer pintado. La transición se
 * calcula por PROGRESO de scroll (0..1) en JS vanilla (sin GSAP, sin pin):
 *   start = heroTop + heroHeight * overlap_start   (≈85% del Hero)
 *   end   = heroTop + heroHeight
 *
 * Tres capas reales con z-index y ritmos de entrada distintos (coreografía):
 *   1) background (z1) — entra primero y rápido
 *   2) computer   (z2) — entra después, con retardo mayor; su video/SVG puede
 *      reproducirse al entrar (autoplay_on_enter), porque es parte de la escena
 *   3) text       (z3) — entra al final y queda por encima
 *
 * Media-driven: background y computer solo muestran media REAL; sin media →
 * fallback neutro / placeholder geométrico (no mockup ni glow falso de fondo).
 *
 * Whitelists:
 *   background.type : image | video | svg
 *   computer.type   : video | image | svg | placeholder
 *
 * Las funciones se declaran antes del return (con function_exists) porque el
 * archivo se incluye para obtener su array de defaults.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('okip_pm_normalize_range')) {
    /**
     * Normaliza un rango [start, end] de progreso (0..1, start <= end).
     *
     * @param mixed $range
     * @param float $def_start
     * @param float $def_end
     * @return array{0:float,1:float}
     */
    function okip_pm_normalize_range($range, $def_start, $def_end)
    {
        $start = $def_start;
        $end   = $def_end;
        if (is_array($range)) {
            if (isset($range[0])) {
                $start = okip_clamp_float($range[0], 0, 1);
            }
            if (isset($range[1])) {
                $end = okip_clamp_float($range[1], 0, 1);
            }
        }
        if ($end < $start) {
            $end = $start;
        }
        return array($start, $end);
    }
}

if (! function_exists('okip_normalize_parallax_monitor_data')) {
    /**
     * Normalizador específico del bloque Parallax Monitor.
     *
     * @param array $data Data ya mezclada con los defaults.
     * @return array
     */
    function okip_normalize_parallax_monitor_data($data)
    {
        $bg_allowed  = array('image', 'video', 'svg');
        $cmp_allowed = array('video', 'image', 'svg', 'placeholder');

        // Layout / escena.
        $data['layout']['overlap_previous'] = okip_bool($data['layout']['overlap_previous']);
        $data['layout']['overlap_start']    = okip_clamp_float($data['layout']['overlap_start'], 0, 1);
        $data['layout']['z_index']          = okip_clamp_int($data['layout']['z_index'], 0, 50);

        // Fondo.
        $data['background']['type'] = okip_one_of($data['background']['type'], $bg_allowed, 'image');

        // Computadora (capa media del monitor).
        $data['computer']['type']                = okip_one_of($data['computer']['type'], $cmp_allowed, 'placeholder');
        $data['computer']['autoplay_on_enter']   = okip_bool($data['computer']['autoplay_on_enter']);
        $data['computer']['placeholder_enabled'] = okip_bool($data['computer']['placeholder_enabled']);
        $data['computer']['frame_enabled']       = okip_bool($data['computer']['frame_enabled']);

        // CTA.
        $data['cta']['enabled'] = okip_bool($data['cta']['enabled']);

        // Overlay.
        $data['overlay']['enabled'] = okip_bool($data['overlay']['enabled']);
        $data['overlay']['opacity'] = okip_clamp_float($data['overlay']['opacity'], 0, 1);

        // Glow.
        $data['glow']['enabled']   = okip_bool($data['glow']['enabled']);
        $data['glow']['intensity'] = okip_clamp_float($data['glow']['intensity'], 0, 1);

        // Animación / transición / parallax.
        $a = $data['animation'];
        $a['enabled']                    = okip_bool($a['enabled']);
        $a['use_gsap']                   = okip_bool($a['use_gsap']);
        $a['use_vanilla_fallback']       = okip_bool($a['use_vanilla_fallback']);
        $a['parallax_enabled']           = okip_bool($a['parallax_enabled']);
        $a['overlap_transition_enabled'] = okip_bool($a['overlap_transition_enabled']);
        $a['text_reveal']                = okip_bool($a['text_reveal']);
        $a['pin_enabled']                = okip_bool($a['pin_enabled']);
        $a['start_progress']             = okip_clamp_float($a['start_progress'], 0, 1);
        $a['background_speed']           = okip_clamp_float($a['background_speed'], 0, 2);
        $a['computer_speed']             = okip_clamp_float($a['computer_speed'], 0, 2);
        $a['text_speed']                 = okip_clamp_float($a['text_speed'], 0, 2);
        $a['background_enter_range']     = okip_pm_normalize_range($a['background_enter_range'], 0.00, 0.35);
        $a['computer_enter_range']       = okip_pm_normalize_range($a['computer_enter_range'], 0.25, 0.70);
        $a['text_enter_range']           = okip_pm_normalize_range($a['text_enter_range'], 0.55, 1.00);
        $data['animation'] = $a;

        return $data;
    }
}

return array(
    'content' => array(
        'eyebrow'          => '',
        'title'            => '',
        'highlighted_text' => '', // subcadena del title a resaltar
        'description'      => '',
    ),
    'layout' => array(
        'min_height'       => '100svh', // escena full-screen
        'content_width'    => '1200px',
        'overlap_previous' => true,     // entrar sobre el bloque anterior (Hero)
        'overlap_start'    => 0.85,     // % del Hero donde empieza la transición
        'overlap_amount'   => '18vh',   // cuánto sube sobre el bloque anterior
        'z_index'          => 2,        // por encima del Hero durante la transición
    ),
    'background' => array(
        'type'   => 'image', // image | video | svg
        'media'  => '',
        'poster' => '',
        'alt'    => '',
    ),
    'computer' => array(
        'type'                => 'placeholder', // video | image | svg | placeholder
        'media'               => '',
        'poster'              => '',
        'alt'                 => '',
        'autoplay_on_enter'   => true,  // el video/SVG de la escena SÍ puede arrancar al entrar
        'frame_enabled'       => true,  // marco geométrico mínimo del monitor
        'placeholder_enabled' => true,  // placeholder geométrico si no hay media real
    ),
    'cta' => array(
        'enabled' => false,
        'label'   => '',
        'url'     => '',
    ),
    'overlay' => array(
        'enabled' => true,
        'opacity' => 0.25,
    ),
    'glow' => array(
        'enabled'   => true,
        'intensity' => 0.6, // 0..1 — opacidad del glow azul tras el monitor
    ),
    'animation' => array(
        'enabled'                    => true,
        'use_gsap'                   => true,  // usar GSAP+ScrollTrigger si existen
        'use_vanilla_fallback'       => true,  // si no hay GSAP, parallax con rAF
        'parallax_enabled'           => true,
        'overlap_transition_enabled' => true,
        'text_reveal'                => true,
        'pin_enabled'                => false, // sin pin por ahora (solo con GSAP futuro)
        'start_progress'             => 0.85,  // alias de layout.overlap_start (transición)
        // Velocidades de drift de parallax (distintas por capa).
        'background_speed'           => 0.22,  // fondo: más rápido
        'computer_speed'             => 0.40,  // computadora: intermedio
        'text_speed'                 => 0.12,  // texto: más sutil
        // Rangos de entrada coreografiada (en progreso 0..1 de la transición).
        'background_enter_range'     => array(0.00, 0.35),
        'computer_enter_range'       => array(0.25, 0.70),
        'text_enter_range'           => array(0.55, 1.00),
    ),
);
