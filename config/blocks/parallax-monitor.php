<?php

/**
 * Esquema / defaults del bloque Parallax Monitor (Bloque 2).
 *
 * Sección oscura con texto a la izquierda y un monitor/pantalla a la derecha que
 * se mueve a un ritmo distinto al fondo al hacer scroll (parallax).
 *
 * Media-driven: el fondo y el monitor solo muestran media REAL (que exista). Sin
 * media → fallback neutro / geométrico mínimo (no mockup falso, no glow falso).
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

        $data['background']['type'] = okip_one_of($data['background']['type'], $bg_allowed, 'image');

        $data['overlay']['enabled'] = okip_bool($data['overlay']['enabled']);
        $data['overlay']['opacity'] = okip_clamp_float($data['overlay']['opacity'], 0, 1);

        $data['monitor']['frame_enabled'] = okip_bool($data['monitor']['frame_enabled']);

        $data['cta']['enabled'] = okip_bool($data['cta']['enabled']);

        $data['animation']['enabled']    = okip_bool($data['animation']['enabled']);
        $data['animation']['text_reveal'] = okip_bool($data['animation']['text_reveal']);
        $data['animation']['parallax_strength'] = okip_clamp_float($data['animation']['parallax_strength'], 0, 3);
        $data['animation']['monitor_speed']     = okip_clamp_float($data['animation']['monitor_speed'], -2, 2);
        $data['animation']['background_speed']   = okip_clamp_float($data['animation']['background_speed'], -2, 2);

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
    'background' => array(
        'type'   => 'image', // image | video | svg
        'media'  => '',
        'poster' => '',
        'alt'    => '',
    ),
    'monitor' => array(
        'image'        => '', // imagen del dispositivo/marco
        'screen_image' => '', // contenido estático de la pantalla
        'screen_video' => '', // contenido en video de la pantalla
        'alt'          => '',
        'frame_enabled' => true, // marco geométrico mínimo si no hay imagen real
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
    'animation' => array(
        'enabled'          => true,
        'parallax_strength' => 1.0,  // multiplicador global
        'monitor_speed'    => 0.18,  // desplazamiento relativo del monitor
        'background_speed' => 0.08,  // desplazamiento relativo del fondo
        'text_reveal'      => true,
    ),
);
