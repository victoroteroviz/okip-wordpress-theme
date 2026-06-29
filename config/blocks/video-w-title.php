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
 * Traspaso de salida: `transition.mode = sticky-cover` (CSS, ver assets/css/transitions.css
 * y el wrapper `.okip-cover-stage` en block.php). Sin ScrollTrigger → suave a cualquier
 * velocidad de scroll. `hold_vh` reserva scroll extra de visibilidad antes de que el
 * bloque siguiente lo cubra.
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
        // Layout. z_index default 0 = automático por orden de render; >0 = override.
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

        // Animación de entrada (reveal).
        $data['animation']['enabled'] = okip_bool($data['animation']['enabled']);

        // Traspaso de salida (sistema híbrido): sticky-cover por CSS.
        $data['transition'] = okip_normalize_transition(
            isset($data['transition']) ? $data['transition'] : array(),
            array('enabled' => true, 'mode' => 'sticky-cover', 'disable_below' => 1024, 'hold_vh' => 100)
        );

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
        'z_index'       => 0,        // 0 = z-index automático por orden de render (override si >0)
        'alignment'     => 'center', // left | center
    ),
    'animation' => array(
        'enabled' => true, // reveal de entrada (fade/translate); el JS lo "arma" y lo dispara
    ),
    'transition' => array(
        'enabled'       => true,
        'mode'          => 'sticky-cover', // CSS sticky: queda fijo y el siguiente bloque lo cubre
        'disable_below' => 1024,           // breakpoint informativo (el sticky CSS usa 1025px)
        'hold_vh'       => 100,            // scroll extra de visibilidad antes del traspaso
    ),
);
