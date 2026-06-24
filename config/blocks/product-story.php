<?php

/**
 * Esquema / defaults del bloque Product Story (Bloque 4).
 *
 * Sección de fondo CLARO que continúa SIN transición tras el Bloque 3
 * (industry-carousel). Layout ref `bloque 4.png`: composición editorial con tres
 * filas de producto. Cada fila:
 *   - Recuadro visual a la izquierda (negro logo+título | media con caption).
 *   - Etiqueta gris (pill) debajo del recuadro (RIA / COVIA / GIA).
 *   - Tarjeta gris clara a la derecha: heading + descripción en monoespaciado.
 *
 * Animación scroll-driven por FILA (desktop, GSAP): cada fila tiene su propio
 * ScrollTrigger con scrub. Al final puede activar un handoff pin corto para que
 * Mission se superponga. Móvil/tablet ≤disable_below, sin GSAP o reduce-motion:
 * is-static, todo legible.
 *
 * El esquema está pensado para que el FUTURO panel admin edite: lista de
 * productos, variante visual del recuadro, texto, etiqueta, tipo de animación,
 * scrub y breakpoint de desactivación. (Hoy NO hay panel: datos en este config.)
 *
 * Las funciones se declaran antes del return (con function_exists) porque el
 * archivo se incluye para obtener su array de defaults.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('okip_ps_item_defaults')) {
    /**
     * Defaults de un producto (fila).
     *
     * @return array
     */
    function okip_ps_item_defaults()
    {
        return array(
            'label'          => '',           // etiqueta gris bajo el recuadro (RIA/COVIA/GIA)
            'title_left'     => '',           // título dentro del recuadro izquierdo
            'heading'        => '',           // título monoespaciado de la tarjeta derecha
            'description'    => '',           // descripción de la tarjeta derecha
            'media_type'     => 'placeholder', // image|video|svg|placeholder
            'media'          => '',           // ruta a assets/ o URL o ID de attachment
            'alt'            => '',
            'visual_variant' => 'logo-title', // logo-title | media-caption
        );
    }
}

if (! function_exists('okip_normalize_product_story_data')) {
    /**
     * Normalizador específico del bloque Product Story.
     *
     * @param array $data Data ya mezclada con los defaults.
     * @return array
     */
    function okip_normalize_product_story_data($data)
    {
        // Content.
        $data['content']['section_label'] = sanitize_text_field((string) $data['content']['section_label']);

        // Layout.
        $data['layout']['z_index'] = okip_clamp_int($data['layout']['z_index'], 0, 50);

        // Animación.
        $a = $data['animation'];
        $a['enabled']              = okip_bool($a['enabled']);
        $a['use_gsap']             = okip_bool($a['use_gsap']);
        $a['use_vanilla_fallback'] = okip_bool($a['use_vanilla_fallback']);
        $a['disable_below']        = okip_clamp_int($a['disable_below'], 0, 9999);
        $a['scrub']                = okip_clamp_float($a['scrub'], 0, 5);
        $a['left_enter']    = okip_one_of($a['left_enter'], array('mask-slide', 'fade-up', 'scale-soft', 'none'), 'mask-slide');
        $a['copy_bg_enter'] = okip_one_of($a['copy_bg_enter'], array('wipe-left', 'fade', 'none'), 'wipe-left');
        $a['text_reveal']   = okip_one_of($a['text_reveal'], array('scroll-typewriter', 'fade-lines', 'none'), 'scroll-typewriter');
        $data['animation']  = $a;

        // Transición de entrega al bloque siguiente.
        $t = isset($data['transition']) && is_array($data['transition']) ? $data['transition'] : array();
        $t['handoff_pin']     = okip_bool(isset($t['handoff_pin']) ? $t['handoff_pin'] : true);
        $t['duration_vh']     = okip_clamp_int(isset($t['duration_vh']) ? $t['duration_vh'] : 55, 20, 200);
        $t['disable_below']   = okip_clamp_int(isset($t['disable_below']) ? $t['disable_below'] : 1024, 0, 9999);
        $data['transition']   = $t;

        // Normalizar ítems (productos).
        $item_defaults = okip_ps_item_defaults();
        if (! empty($data['items']) && is_array($data['items'])) {
            $out = array();
            foreach ($data['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $merged                   = array_merge($item_defaults, $item);
                $merged['label']          = sanitize_text_field((string) $merged['label']);
                $merged['title_left']     = sanitize_text_field((string) $merged['title_left']);
                $merged['heading']        = sanitize_text_field((string) $merged['heading']);
                $merged['description']    = sanitize_text_field((string) $merged['description']);
                $merged['alt']            = sanitize_text_field((string) $merged['alt']);
                $merged['media']          = sanitize_text_field((string) $merged['media']);
                $merged['media_type']     = okip_one_of($merged['media_type'], array('image', 'gif', 'video', 'svg', 'placeholder'), 'placeholder');
                $merged['visual_variant'] = okip_one_of($merged['visual_variant'], array('logo-title', 'media-caption'), 'logo-title');
                $out[] = $merged;
            }
            $data['items'] = $out;
        } else {
            $data['items'] = array();
        }

        return $data;
    }
}

return array(
    'content' => array(
        'section_label' => '',   // etiqueta opcional sobre la sección (no usada en ref)
    ),
    'layout' => array(
        'min_height'    => 'auto',   // el bloque fluye natural, sin altura forzada
        'content_width' => '1100px', // ancho máximo del contenedor editorial
        'z_index'       => 4,        // sobre el Bloque 3 en la cascada de la home
    ),
    'items' => array(
        array(
            'label'          => 'RIA',
            'title_left'     => 'Registro de accesos',
            'heading'        => 'Control - Automatización - Datos',
            'description'    => 'Transforma cada ingreso en información trazable, consultable y útil para fortalecer la seguridad y operación del espacio.',
            'media_type'     => 'placeholder',
            'media'          => '',
            'alt'            => '',
            'visual_variant' => 'logo-title',
        ),
        array(
            'label'          => 'COVIA',
            'title_left'     => 'Monitoreo Inteligente',
            'heading'        => 'Centraliza - Visualiza - Coordina',
            'description'    => 'Información operativa en tiempo real que fortalece la seguridad y toma de decisiones integrando IA + análisis de datos.',
            'media_type'     => 'placeholder',
            'media'          => '',
            'alt'            => '',
            'visual_variant' => 'media-caption',
        ),
        array(
            'label'          => 'GIA',
            'title_left'     => 'Mensajería segura',
            'heading'        => 'Canal - Encriptación - Resguardo',
            'description'    => 'Protección y cifrado de datos para fortalecer la confidencialidad de las comunicaciones y resguardar información sensible.',
            'media_type'     => 'placeholder',
            'media'          => '',
            'alt'            => '',
            'visual_variant' => 'logo-title',
        ),
    ),
    'animation' => array(
        'enabled'              => true,
        'use_gsap'             => true,    // si GSAP+ScrollTrigger están, anima por fila con scrub
        'use_vanilla_fallback' => true,    // sin GSAP (pero desktop) → IO añade is-revealed
        'disable_below'        => 1024,    // ≤ este ancho px → is-static, todo legible
        'scrub'                => 1,       // suavizado del scrub GSAP por fila
        'left_enter'           => 'mask-slide',        // mask-slide | fade-up | scale-soft | none
        'copy_bg_enter'        => 'wipe-left',         // wipe-left | fade | none
        'text_reveal'          => 'scroll-typewriter', // scroll-typewriter | fade-lines | none
    ),
    'transition' => array(
        'handoff_pin'   => true, // HOLD: fija la sección con el último producto centrado
        // Distancia del HOLD (pinSpacing:true): el último producto queda fijo y
        // limpio ~55vh antes de liberarse; luego Mission sube por scroll natural y
        // lo cubre (z-index mayor). No hay solape forzado → sin tapar el producto.
        'duration_vh'   => 55,
        'disable_below' => 1024,
    ),
);
