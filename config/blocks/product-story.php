<?php

/**
 * Esquema / defaults del bloque Product Story (Bloque 4).
 *
 * Sección OSCURA de ANCHO COMPLETO (ref `referencias/image.png`): fondo negro con
 * presencia azul en el borde derecho (degradado radial), título superior izquierdo
 * (`SOLUCIONES`, uppercase por CSS) y tarjetas apiladas verticalmente.
 *
 * Cada tarjeta tiene DOS capas:
 *   - Capa de fondo (`okip-ps__back`): tarjeta gris/clara con heading + descripción
 *     (y media opcional, desactivada por defecto). Define la altura de la tarjeta.
 *   - Capa cover (`okip-ps__cover`): vidrio/blur encima con un título grande
 *     monoespaciado (`cover_title`). Al hover (desktop) o tap (touch) se desplaza
 *     hacia arriba y descubre la capa de fondo.
 *
 * Animación scroll-driven por TARJETA (desktop, GSAP): cada tarjeta hace un reveal
 * limpio (fade/slide-up) al entrar — NO typewriter. Sin GSAP (desktop): IO añade
 * `is-revealed`. Móvil/tablet ≤disable_below, sin GSAP o reduce-motion: `is-static`,
 * todo legible. El cover se descubre por hover (CSS) o tap (JS) en cualquier modo.
 *
 * El esquema está pensado para que el FUTURO panel admin edite: título de la sección
 * y lista de tarjetas (hasta 10) con sus campos de cover/fondo/media. (Hoy NO hay
 * panel: datos en este config.)
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
     * Defaults de una tarjeta.
     *
     * @return array
     */
    function okip_ps_item_defaults()
    {
        return array(
            // Capa cover (vidrio/blur encima).
            'cover_title'        => '',         // título grande monoespaciado del cover
            'cover_blur'         => 14,         // desenfoque gaussiano del cover (px, 0..40)
            'cover_background'   => '#0b1222',  // color base del vidrio del cover (hex)
            'cover_opacity'      => 0.55,       // opacidad del vidrio del cover (0..1)
            'cover_border_color' => '#33476e',  // color del borde del cover (hex)
            // Capa de fondo (gris/clara, debajo del cover).
            'heading'            => '',         // título de la capa de fondo
            'description'        => '',         // descripción de la capa de fondo
            'background_color'   => '#e7e7e7',  // color de la capa de fondo (hex)
            // Media opcional (DESACTIVADA por defecto): si off o sin media válida,
            // el texto absorbe el espacio (sin hueco reservado).
            'media_enabled'      => false,
            'media_type'         => 'image',    // image|gif|video|svg|placeholder
            'media'              => '',         // ruta a assets/ o URL o ID de attachment
            'alt'                => '',
        );
    }
}

if (! function_exists('okip_ps_hex')) {
    /**
     * Sanea un color hex (#rgb o #rrggbb) con fallback si es inválido.
     *
     * @param mixed  $value
     * @param string $fallback Color por defecto (hex válido).
     * @return string
     */
    function okip_ps_hex($value, $fallback)
    {
        $clean = function_exists('sanitize_hex_color')
            ? sanitize_hex_color((string) $value)
            : '';
        return $clean !== '' && $clean !== null ? $clean : $fallback;
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
        // Content: título de la sección (uppercase visual via CSS).
        $title = isset($data['content']['title']) ? (string) $data['content']['title'] : '';
        $title = sanitize_text_field($title);
        // Retrocompat: si llega el antiguo `section_label`, úsalo como título.
        if ($title === '' && ! empty($data['content']['section_label'])) {
            $title = sanitize_text_field((string) $data['content']['section_label']);
        }
        $data['content']['title'] = $title !== '' ? $title : 'SOLUCIONES';

        // Layout.
        $data['layout']['z_index'] = okip_clamp_int($data['layout']['z_index'], 0, 50);

        // Fondo del bloque (media-driven, prioridad imagen). Sin media válida →
        // fallback al degradado oscuro/azul por CSS.
        $b = isset($data['background']) && is_array($data['background']) ? $data['background'] : array();
        $b['media_enabled'] = okip_bool(isset($b['media_enabled']) ? $b['media_enabled'] : false);
        $b['media_type']    = okip_one_of(isset($b['media_type']) ? $b['media_type'] : 'image', array('image', 'gif', 'svg', 'video'), 'image');
        $b['media']         = sanitize_text_field((string) (isset($b['media']) ? $b['media'] : ''));
        $b['alt']           = sanitize_text_field((string) (isset($b['alt']) ? $b['alt'] : ''));
        $data['background'] = $b;

        // Animación.
        $a = $data['animation'];
        $a['enabled']              = okip_bool($a['enabled']);
        $a['use_gsap']             = okip_bool($a['use_gsap']);
        $a['use_vanilla_fallback'] = okip_bool($a['use_vanilla_fallback']);
        $a['disable_below']        = okip_clamp_int($a['disable_below'], 0, 9999);
        $a['reveal']               = okip_one_of($a['reveal'], array('fade-up', 'wipe', 'none'), 'fade-up');
        $data['animation']         = $a;

        // Normalizar tarjetas (máximo 10).
        $item_defaults = okip_ps_item_defaults();
        if (! empty($data['items']) && is_array($data['items'])) {
            $items = array_slice(array_values($data['items']), 0, 10);
            $out   = array();
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $merged                       = array_merge($item_defaults, $item);
                $merged['cover_title']        = sanitize_text_field((string) $merged['cover_title']);
                $merged['heading']            = sanitize_text_field((string) $merged['heading']);
                $merged['description']        = sanitize_text_field((string) $merged['description']);
                $merged['alt']                = sanitize_text_field((string) $merged['alt']);
                $merged['media']              = sanitize_text_field((string) $merged['media']);
                $merged['cover_blur']         = okip_clamp_int($merged['cover_blur'], 0, 40);
                $merged['cover_opacity']      = okip_clamp_float($merged['cover_opacity'], 0, 1);
                $merged['cover_background']   = okip_ps_hex($merged['cover_background'], '#0b1222');
                $merged['cover_border_color'] = okip_ps_hex($merged['cover_border_color'], '#33476e');
                $merged['background_color']   = okip_ps_hex($merged['background_color'], '#e7e7e7');
                $merged['media_enabled']      = okip_bool($merged['media_enabled']);
                $merged['media_type']         = okip_one_of($merged['media_type'], array('image', 'gif', 'video', 'svg', 'placeholder'), 'image');
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
        'title' => 'SOLUCIONES',   // título superior izquierdo (uppercase via CSS)
    ),
    'layout' => array(
        'min_height'    => 'auto',   // el bloque fluye natural, sin altura forzada
        'content_width' => '1360px', // columna de contenido sobre el fondo full-bleed
        'z_index'       => 0,        // 0 = z-index automático por orden de render (override si >0)
    ),
    'background' => array(
        // Fondo del bloque editable desde el panel. PRIORIDAD imagen; admite
        // gif/svg/video. Off por defecto → fallback al degradado oscuro/azul (CSS).
        'media_enabled' => false,
        'media_type'    => 'image',  // image|gif|svg|video
        'media'         => '',
        'alt'           => '',
    ),
    'items' => array(
        array(
            'cover_title'      => 'Monitoreo inteligente',
            'heading'          => 'CENTRALIZA - VISUALIZA - COORDINA',
            'description'      => 'Información operativa en tiempo real que fortalece la seguridad y la toma de decisiones integrando IA y análisis de datos.',
            'media_enabled'    => false,
        ),
        array(
            'cover_title'      => 'Registro de accesos',
            'heading'          => 'CONTROL - AUTOMATIZACIÓN - DATOS',
            'description'      => 'Transforma cada ingreso en información trazable, consultable y útil para fortalecer la seguridad y la operación del espacio.',
            'media_enabled'    => false,
        ),
        array(
            'cover_title'      => 'Mensajería segura',
            'heading'          => 'CANAL - ENCRIPTACIÓN - RESGUARDO',
            'description'      => 'Protección y cifrado de datos para reforzar la confidencialidad de las comunicaciones y resguardar información sensible.',
            'media_enabled'    => false,
        ),
    ),
    'animation' => array(
        'enabled'              => true,
        'use_gsap'             => true,    // si GSAP+ScrollTrigger están, reveal por tarjeta
        'use_vanilla_fallback' => true,    // sin GSAP (pero desktop) → IO añade is-revealed
        'disable_below'        => 1024,    // ≤ este ancho px → is-static, todo legible
        'reveal'               => 'fade-up', // fade-up | wipe | none (NO typewriter)
    ),
);
