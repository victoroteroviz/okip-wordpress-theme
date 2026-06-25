<?php

/**
 * Esquema / defaults del bloque Parallax Monitor (Bloque 2).
 *
 * Escena oscura full-screen (>=100svh). En desktop el Hero queda sticky por CSS
 * y este bloque lo cubre por flujo/z-index al salir del primer viewport.
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
        $data['layout']['z_index'] = okip_clamp_int($data['layout']['z_index'], 0, 50);

        // Fondo.
        $data['background']['type']     = okip_one_of($data['background']['type'], $bg_allowed, 'image');
        $data['background']['gradient'] = okip_bool($data['background']['gradient']);

        // Computadora (capa media del monitor).
        $data['computer']['type']                = okip_one_of($data['computer']['type'], $cmp_allowed, 'placeholder');
        $data['computer']['render_mode']         = okip_one_of(isset($data['computer']['render_mode']) ? $data['computer']['render_mode'] : 'screen', array('screen', 'scene'), 'screen');
        $data['computer']['black_key_enabled']   = okip_bool(isset($data['computer']['black_key_enabled']) ? $data['computer']['black_key_enabled'] : false);
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
        $a['background_pin']             = okip_bool(isset($a['background_pin']) ? $a['background_pin'] : true);
        $a['background_pin_vh']          = okip_clamp_int(isset($a['background_pin_vh']) ? $a['background_pin_vh'] : 100, 0, 300);
        $a['entry_scroll_vh']            = okip_clamp_int(isset($a['entry_scroll_vh']) ? $a['entry_scroll_vh'] : 155, 100, 300);
        $a['cover_delay_vh']             = okip_clamp_int(isset($a['cover_delay_vh']) ? $a['cover_delay_vh'] : 50, 0, 200);
        $a['cover_start_vh']             = okip_clamp_int(isset($a['cover_start_vh']) ? $a['cover_start_vh'] : 8, 1, 50);
        $a['cover_ramp']                 = okip_clamp_float(isset($a['cover_ramp']) ? $a['cover_ramp'] : 0.45, 0.05, 1);
        $a['overlap_breakpoint']         = okip_clamp_int(isset($a['overlap_breakpoint']) ? $a['overlap_breakpoint'] : 1024, 0, 4096);
        $a['background_speed']           = okip_clamp_float($a['background_speed'], 0, 2);
        $a['computer_speed']             = okip_clamp_float($a['computer_speed'], 0, 2);
        $a['text_speed']                 = okip_clamp_float($a['text_speed'], 0, 2);
        $a['background_enter_range']     = okip_pm_normalize_range($a['background_enter_range'], 0.00, 0.08);
        $a['computer_enter_range']       = okip_pm_normalize_range($a['computer_enter_range'], 0.28, 0.64);
        $a['text_enter_range']           = okip_pm_normalize_range($a['text_enter_range'], 0.70, 1.00);
        $a['parallax_drift_px']          = okip_clamp_int(isset($a['parallax_drift_px']) ? $a['parallax_drift_px'] : 180, 0, 500);
        $data['animation'] = $a;

        return $data;
    }
}

return array(
    'content' => array(
        'eyebrow'          => '',
        'title'            => '',
        'highlighted_text' => '', // subcadena del title a resaltar (negrita, no color)
        'subtitle'         => '', // línea inferior tipo kicker (uppercase, letterspaced)
        'description'      => '',
    ),
    'layout' => array(
        'min_height'       => '100svh', // escena full-screen
        'content_width'    => '1320px',
        'z_index'          => 2,        // por encima del Hero durante la transición
    ),
    'background' => array(
        'type'     => 'image', // image | video | svg
        'media'    => '',
        'poster'   => '',
        'alt'      => '',
        'color'    => '#050a16', // color base cuando no hay media
        'gradient' => true,      // gradient atmosférico cuando no hay media
    ),
    'computer' => array(
        'type'                => 'video', // video | image | svg | placeholder
        'render_mode'         => 'scene', // scene = video de escena libre; screen = media dentro del marco
        'media'               => 'assets/video/parallax-monitor/default-monitor.mp4',
        'poster'              => '',
        'alt'                 => 'Panel de monitoreo operativo en tiempo real',
        'black_key_enabled'   => false, // key visual opcional; OFF por defecto: el video ya es escena opaca sobre fondo oscuro
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
        'overlap_breakpoint'         => 1024,  // ≤ este ancho (px): sin pin/cover/overlap → flujo estático
        // Hold pin: con GSAP el Bloque 2 queda fijo mientras B3 lo cubre.
        'background_pin'             => true,
        'background_pin_vh'          => 100,   // fallback del hold pin; JS prefiere altura real del bloque
        'entry_scroll_vh'            => 155,   // duracion total del depth-entry: 100vh + 55vh extra
        'cover_delay_vh'             => 50,    // hold estático (≈medio viewport) tras terminar B2, antes de que B3 cubra
        'cover_start_vh'             => 8,     // vh antes del top del viewport donde el cover empieza a aparecer
        'cover_ramp'                 => 0.45,  // fracción de la ventana del cover hasta opacidad total (determinista)
        // Magnitud base y velocidades de entrada por capa (px = speed × parallax_drift_px).
        // Las capas empiezan desplazadas y terminan en y:0 antes del handoff visual a B3.
        'parallax_drift_px'          => 180,   // px base; escala clara para la profundidad
        'background_speed'           => 0.45,  // fondo: movimiento leve, pero completa su entrada casi de inmediato
        'computer_speed'             => 0.78,  // monitor: profundidad media visible
        'text_speed'                 => 0.95,  // texto: capa frontal, movimiento mayor
        // Rangos de entrada coreografiada (en progreso 0..1 de la transición).
        'background_enter_range'     => array(0.00, 0.08),
        'computer_enter_range'       => array(0.28, 0.64),
        'text_enter_range'           => array(0.70, 1.00),
    ),
);
