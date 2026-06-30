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

if (! function_exists('okip_vwt_text_box_defaults')) {
    /**
     * Defaults de un cuadro de texto editable del bloque Video con Título.
     *
     * Cada cuadro es una caja posicionable (drag & drop en el admin) con su propia
     * tipografía. `role` agrupa el estilo base (título/subtítulo/texto). Coordenadas
     * en % sobre la escena (ancla en el centro de la caja); `width_pct` en % del ancho
     * de la escena; `height_px` 0 = alto automático por contenido.
     *
     * @return array
     */
    function okip_vwt_text_box_defaults()
    {
        return array(
            'id'             => '',
            'active'         => false,
            'role'           => 'text',     // title | subtitle | text
            'content'        => '',
            'x'              => 50,          // % (centro de la caja)
            'y'              => 50,          // %
            'width_pct'      => 60,          // % del ancho de la escena
            'height_px'      => 0,           // 0 = alto automático
            'align'          => 'center',    // left | center | right
            'font_family'    => '',          // '' = hereda la base del tema
            'font_size_px'   => 32,
            'font_weight'    => 400,
            'color'          => '#ffffff',
            'line_height'    => 1.2,
            'letter_spacing' => 0,           // px
        );
    }
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

        // Cuadros de texto (lista). Cada caja se sanea contra sus defaults; el tope
        // evita instancias infladas desde un POST manipulado.
        $boxes = isset($data['text_boxes']) && is_array($data['text_boxes']) ? $data['text_boxes'] : array();
        $norm  = array();
        foreach ($boxes as $i => $box) {
            if (! is_array($box)) {
                continue;
            }
            if (count($norm) >= 12) {
                break;
            }
            $box = okip_merge_defaults($box, okip_vwt_text_box_defaults());
            $norm[] = array(
                'id'             => okip_sanitize_instance_id(isset($box['id']) ? $box['id'] : '', 'box-' . ($i + 1)),
                'active'         => okip_bool($box['active']),
                'role'           => okip_one_of($box['role'], array('title', 'subtitle', 'text'), 'text'),
                'content'        => sanitize_text_field((string) $box['content']),
                'x'              => okip_clamp_float($box['x'], 0, 100),
                'y'              => okip_clamp_float($box['y'], 0, 100),
                'width_pct'      => okip_clamp_float($box['width_pct'], 5, 100),
                'height_px'      => okip_clamp_int($box['height_px'], 0, 1200),
                'align'          => okip_one_of($box['align'], array('left', 'center', 'right'), 'center'),
                'font_family'    => okip_sanitize_google_font_family((string) $box['font_family']),
                'font_size_px'   => okip_clamp_float($box['font_size_px'], 8, 200),
                'font_weight'    => okip_clamp_int($box['font_weight'], 100, 900),
                'color'          => sanitize_hex_color((string) $box['color']) ?: '#ffffff',
                'line_height'    => okip_clamp_float($box['line_height'], 0.8, 3),
                'letter_spacing' => okip_clamp_float($box['letter_spacing'], -5, 20),
            );
        }
        $data['text_boxes'] = $norm;

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
    // Cuadros de texto posicionables (nuevo modelo del editor admin). Si ningún
    // cuadro activo tiene contenido, el render cae al bloque `content` legacy.
    'text_boxes' => array(
        array(
            'id'             => 'title-main',
            'active'         => true,
            'role'           => 'title',
            'content'        => '',
            'x'              => 50,
            'y'              => 46,
            'width_pct'      => 72,
            'height_px'      => 0,
            'align'          => 'center',
            'font_family'    => '',
            'font_size_px'   => 56,
            'font_weight'    => 300,
            'color'          => '#ffffff',
            'line_height'    => 1.08,
            'letter_spacing' => 0,
        ),
        array(
            'id'             => 'subtitle-main',
            'active'         => false,
            'role'           => 'subtitle',
            'content'        => '',
            'x'              => 50,
            'y'              => 64,
            'width_pct'      => 60,
            'height_px'      => 0,
            'align'          => 'center',
            'font_family'    => '',
            'font_size_px'   => 16,
            'font_weight'    => 600,
            'color'          => '#b9c4d4',
            'line_height'    => 1.3,
            'letter_spacing' => 2,
        ),
    ),
    'video' => array(
        // Video de fondo por defecto. Si no existe → fallback sobrio (color sólido).
        'media'       => 'assets/video/video-w-title/loop-video.mp4',
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
