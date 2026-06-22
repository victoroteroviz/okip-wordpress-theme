<?php

/**
 * Esquema / defaults del bloque Parallax Monitor (Bloque 2).
 *
 * Escena oscura de pantalla completa: texto grande a la izquierda y un
 * monitor/pantalla grande a la derecha, con glow azul sutil detrás del monitor
 * (iluminación de la escena, no fondo que tape media real).
 *
 * - Escena full-screen: min_height >= 100svh.
 * - Se SUPERPONE al bloque anterior (Hero) durante el scroll (overlap_previous +
 *   z_index), para sensación de transición por capas, no de secciones apiladas.
 * - Parallax REAL por capas (fondo / monitor / texto a velocidades distintas):
 *   GSAP + ScrollTrigger si existen; si no, fallback vanilla con
 *   IntersectionObserver + requestAnimationFrame.
 *
 * Media-driven: el fondo y la pantalla solo muestran media REAL (que exista). Sin
 * media → fallback neutro / marco geométrico mínimo (no mockup falso, no glow
 * falso como fondo). El glow es decorativo y vive DETRÁS del monitor.
 *
 * Whitelists:
 *   background.type : image | video | svg
 *
 * Las funciones se declaran antes del return (con function_exists) porque el
 * archivo se incluye para obtener su array de defaults.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
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
        $bg_allowed = array('image', 'video', 'svg');

        // Layout / escena.
        $data['layout']['overlap_previous'] = okip_bool($data['layout']['overlap_previous']);
        $data['layout']['z_index']          = okip_clamp_int($data['layout']['z_index'], 0, 50);

        // Fondo.
        $data['background']['type'] = okip_one_of($data['background']['type'], $bg_allowed, 'image');

        // Monitor.
        $data['monitor']['frame_enabled']       = okip_bool($data['monitor']['frame_enabled']);
        $data['monitor']['placeholder_enabled'] = okip_bool($data['monitor']['placeholder_enabled']);

        // CTA.
        $data['cta']['enabled'] = okip_bool($data['cta']['enabled']);

        // Overlay.
        $data['overlay']['enabled'] = okip_bool($data['overlay']['enabled']);
        $data['overlay']['opacity'] = okip_clamp_float($data['overlay']['opacity'], 0, 1);

        // Glow (iluminación de la escena, detrás del monitor).
        $data['glow']['enabled']   = okip_bool($data['glow']['enabled']);
        $data['glow']['intensity'] = okip_clamp_float($data['glow']['intensity'], 0, 1);

        // Animación / parallax.
        $data['animation']['enabled']                    = okip_bool($data['animation']['enabled']);
        $data['animation']['use_gsap']                   = okip_bool($data['animation']['use_gsap']);
        $data['animation']['use_vanilla_fallback']       = okip_bool($data['animation']['use_vanilla_fallback']);
        $data['animation']['parallax_enabled']           = okip_bool($data['animation']['parallax_enabled']);
        $data['animation']['overlap_transition_enabled'] = okip_bool($data['animation']['overlap_transition_enabled']);
        $data['animation']['text_reveal']                = okip_bool($data['animation']['text_reveal']);
        $data['animation']['pin_enabled']                = okip_bool($data['animation']['pin_enabled']);
        $data['animation']['background_speed']           = okip_clamp_float($data['animation']['background_speed'], 0, 2);
        $data['animation']['monitor_speed']              = okip_clamp_float($data['animation']['monitor_speed'], 0, 2);
        $data['animation']['text_speed']                 = okip_clamp_float($data['animation']['text_speed'], 0, 2);
        $data['animation']['scroll_duration']            = okip_clamp_float($data['animation']['scroll_duration'], 0.25, 4);

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
        'overlap_previous' => true,     // superponerse al bloque anterior (Hero)
        'overlap_amount'   => '18vh',   // cuánto sube sobre el bloque anterior
        'z_index'          => 2,        // por encima del Hero durante la transición
    ),
    'background' => array(
        'type'   => 'image', // image | video | svg
        'media'  => '',
        'poster' => '',
        'alt'    => '',
    ),
    'monitor' => array(
        'image'               => '', // imagen del dispositivo/marco
        'screen_image'        => '', // contenido estático de la pantalla
        'screen_video'        => '', // contenido en video de la pantalla
        'alt'                 => '',
        'frame_enabled'       => true, // marco geométrico mínimo si no hay imagen real
        'placeholder_enabled' => true, // placeholder geométrico de pantalla si no hay media
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
        'background_speed'           => 0.18,  // capa fondo (lenta)
        'monitor_speed'              => 0.45,  // capa monitor (rápida)
        'text_speed'                 => 0.12,  // capa texto (muy ligera)
        'scroll_duration'            => 1.0,   // factor de duración del recorrido
        'pin_enabled'                => false, // sin pin por ahora (solo con GSAP)
        'text_reveal'                => true,
    ),
);
