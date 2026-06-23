<?php

/**
 * Esquema / defaults del bloque Industry Carousel (Bloque 3).
 *
 * Sección de fondo CLARO (blanco/gris suave). Layout ref `bloque 3.png`:
 *   - Texto centrado arriba: heading_main (uppercase bold) + heading_sub + naranja + CTA
 *   - Cinta de imágenes full-width abajo: activa a color/escala mayor, inactivas en grises
 *
 * Carrusel scroll-driven (desktop, GSAP): un solo ScrollTrigger pin+scrub.
 * Móvil/tablet ≤disable_below: is-static, scroll horizontal nativo.
 *
 * Las funciones se declaran antes del return (con function_exists) porque el
 * archivo se incluye para obtener su array de defaults.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('okip_ic_item_defaults')) {
    function okip_ic_item_defaults()
    {
        return array(
            'title'       => '',  // nombre de la industria (label de ítem)
            'orange_text' => '',  // texto naranja del heading cuando este ítem está activo
            'image'       => '',  // ruta relativa a assets/img/ o URL
            'alt'         => '',
            'video'       => '',  // opcional: ruta a assets/video/
        );
    }
}

if (! function_exists('okip_normalize_industry_carousel_data')) {
    /**
     * Normalizador específico del bloque Industry Carousel.
     *
     * @param array $data Data ya mezclada con los defaults.
     * @return array
     */
    function okip_normalize_industry_carousel_data($data)
    {
        // Layout.
        $data['layout']['z_index'] = okip_clamp_int($data['layout']['z_index'], 0, 50);

        // CTA.
        $data['cta']['enabled'] = okip_bool($data['cta']['enabled']);

        // Animación.
        $a = $data['animation'];
        $a['enabled']       = okip_bool($a['enabled']);
        $a['pin_enabled']   = okip_bool($a['pin_enabled']);
        $a['disable_below'] = okip_clamp_int($a['disable_below'], 0, 9999);
        $a['scrub']         = okip_clamp_float($a['scrub'], 0, 5);
        $data['animation']  = $a;

        // Normalizar ítems.
        $item_defaults = okip_ic_item_defaults();
        if (! empty($data['items']) && is_array($data['items'])) {
            $out = array();
            foreach ($data['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $merged                = array_merge($item_defaults, $item);
                $merged['title']       = sanitize_text_field((string) $merged['title']);
                $merged['orange_text'] = sanitize_text_field((string) $merged['orange_text']);
                $merged['alt']         = sanitize_text_field((string) $merged['alt']);
                $merged['image']       = sanitize_text_field((string) $merged['image']);
                $merged['video']       = sanitize_text_field((string) $merged['video']);
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
        'eyebrow'      => '',         // pequeño label sobre el heading
        'heading_main' => '',         // título principal (uppercase bold)
        'heading_sub'  => '',         // subtítulo debajo en peso normal
        'cta_label'    => 'Saber más',
        'cta_url'      => '',
    ),
    'cta' => array(
        'enabled' => false,
        'label'   => 'Saber más',
        'url'     => '',
    ),
    'layout' => array(
        'min_height' => '100svh',
        'z_index'    => 3,
    ),
    'items' => array(
        array(
            'title'       => 'Seguridad pública',
            'orange_text' => 'Seguridad pública',
            'image'       => '',
            'alt'         => '',
            'video'       => '',
        ),
        array(
            'title'       => 'Infraestructura',
            'orange_text' => 'Infraestructura',
            'image'       => '',
            'alt'         => '',
            'video'       => '',
        ),
        array(
            'title'       => 'Transporte',
            'orange_text' => 'Transporte',
            'image'       => '',
            'alt'         => '',
            'video'       => '',
        ),
        array(
            'title'       => 'Sector privado',
            'orange_text' => 'Sector privado',
            'image'       => '',
            'alt'         => '',
            'video'       => '',
        ),
    ),
    'animation' => array(
        'enabled'       => true,
        'pin_enabled'   => true,   // pin GSAP en desktop; un solo ST maestro
        'disable_below' => 1024,   // ≤ este ancho px → is-static, scroll nativo
        'scrub'         => 1,      // suavizado del scrub GSAP
    ),
);
