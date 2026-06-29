<?php

/**
 * Esquema / defaults del bloque Video con Título (video-w-title).
 *
 * Sección secundaria casi full-screen: video de fondo a sangre completa con un
 * overlay opcional y un bloque de texto centrado (título con resaltado en negrita,
 * subtítulo tipo kicker y descripción). Sustituye al antiguo `parallax-monitor`
 * conservando su posición entre el Hero y el Industry Carousel.
 *
 * Media-driven: el video solo se pinta si el media existe (okip_media_exists);
 * si no, fallback sobrio = color sólido (sin gradiente/patrón/glow falso).
 *
 * Whitelists:
 *   layout.alignment : left | center
 *
 * Las funciones se declaran antes del return (con function_exists) porque el
 * archivo se incluye para obtener su array de defaults.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('okip_normalize_video_w_title_data')) {
    /**
     * Normalizador específico del bloque Video con Título.
     *
     * @param array $data Data ya mezclada con los defaults.
     * @return array
     */
    function okip_normalize_video_w_title_data($data)
    {
        // Layout.
        $data['layout']['z_index']   = okip_clamp_int($data['layout']['z_index'], 0, 50);
        $data['layout']['alignment'] = okip_one_of($data['layout']['alignment'], array('left', 'center'), 'center');

        // Video.
        $data['video']['autoplay']    = okip_bool($data['video']['autoplay']);
        $data['video']['loop']        = okip_bool($data['video']['loop']);
        $data['video']['muted']       = okip_bool($data['video']['muted']);
        $data['video']['playsinline'] = okip_bool($data['video']['playsinline']);

        // Overlay.
        $data['overlay']['enabled'] = okip_bool($data['overlay']['enabled']);
        $data['overlay']['opacity'] = okip_clamp_float($data['overlay']['opacity'], 0, 1);

        // Animación.
        $data['animation']['enabled']           = okip_bool($data['animation']['enabled']);
        $data['animation']['disable_below']     = okip_clamp_int($data['animation']['disable_below'], 0, 4096);
        $data['animation']['overlap_enabled']   = okip_bool($data['animation']['overlap_enabled']);
        $data['animation']['overlap_breakpoint'] = okip_clamp_int($data['animation']['overlap_breakpoint'], 0, 4096);

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
    'video' => array(
        // Ruta convencional del video de fondo. Si no existe → fallback sobrio (color sólido).
        'media'       => 'assets/video/video-w-title/background.mp4',
        'poster'      => '',
        'autoplay'    => true,
        'loop'        => true,
        'muted'       => true,
        'playsinline' => true,
    ),
    'overlay' => array(
        'enabled' => true,
        'color'   => '#05080f', // mismo tono base del fallback (coherente con el Hero)
        'opacity' => 0.45,
    ),
    'layout' => array(
        'min_height'    => '100svh', // escena casi full-screen
        'content_width' => '1100px',
        'z_index'       => 2,        // compatible con la transición Hero (z) → Industry
        'alignment'     => 'center', // left | center
    ),
    'animation' => array(
        'enabled'       => true, // entrada por fade/translate (CSS + JS defensivo)
        'disable_below' => 0,    // 0 = activa en todos los anchos; >0 desactiva ≤ ese ancho
        // Overlap de salida: el bloque se auto-pinea (fijo) mientras el bloque siguiente
        // (z-index mayor, ej. industry-carousel z3) sube desde la base y lo cubre. Igual
        // que el traspaso Hero→bloque. Solo desktop + GSAP+ScrollTrigger.
        'overlap_enabled'    => true,
        'overlap_breakpoint' => 1024, // ≤ este ancho (px): sin pin → flujo apilado normal
    ),
);
