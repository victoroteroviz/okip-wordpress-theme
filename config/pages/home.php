<?php

/**
 * Configuración de la página HOME.
 *
 * Lista ORDENADA de instancias de bloque. El orden de este array es el orden de
 * render. El futuro panel admin solo reordenará/editará este array (vía wp_options),
 * sin tocar el motor.
 *
 * En el MVP, Home solo tiene el Hero. Los bloques 2–6 se añadirán aquí más
 * adelante (parallax-monitor, industries-carousel, product-story, statement, news).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

return array(
    array(
        'type'        => 'hero',
        'instance_id' => 'home-hero-main',
        'data'        => array(
            'content' => array(
                'title_line_1' => 'Inteligencia mexicana',
                'title_line_2' => 'al servicio de la humanidad',
                'description'  => '',
                'alignment'    => 'center',
            ),
            'background' => array(
                // Fondo CSS editable por default: glitch/grid tecnológico sutil.
                // El flujo video intro/loop sigue soportado por el bloque si se cambia el type.
                'type'                 => 'css_glitch',
                'css_variant'          => 'glitch_grid',
                'css_bg'               => '#020711',
                'css_accent'           => '#00a9ff',
                'css_accent_2'         => '#6ee7ff',
                'css_grid_opacity'     => 0.24,
                'css_scanline_opacity' => 0.16,
                'css_noise_opacity'    => 0.10,
                'css_glitch_enabled'   => true,
                'css_glitch_intensity' => 0.42,
                'css_glitch_speed'     => 1,
                'css_glitch_interval'  => 7,
                'css_chroma_offset'    => 8,
            ),
            'overlay' => array(
                'enabled' => true,
                'color'   => '#020711',
                'opacity' => 0.18,
            ),
            'typography' => array(
                'title' => array(
                    'font_family'    => 'Montserrat',
                    'google_family'  => 'Montserrat',
                    'font_weight'    => 300,
                    'min_px'         => 42,
                    'fluid_vw'       => 5.2,
                    'max_px'         => 78,
                    'line_height'    => 1.08,
                    'letter_spacing' => 0,
                    'color'          => '#ffffff',
                ),
                'description' => array(
                    'font_family'    => 'Inter',
                    'google_family'  => 'Inter',
                    'font_weight'    => 400,
                    'min_px'         => 16,
                    'fluid_vw'       => 1.8,
                    'max_px'         => 22,
                    'line_height'    => 1.5,
                    'letter_spacing' => 0,
                    'color'          => '#d9e8f7',
                ),
            ),
            'reveal' => array(
                // image_reveal_delay usa el default de 1000ms del esquema.
                // Sin media real, ese es el tiempo de espera antes de revelar tarjetas y texto.
            ),
            'animation' => array(
                // El Hero queda sticky en desktop; el Bloque 2 lo cubre por flujo/z-index.
                // Mantener scroll_3d apagado evita doble transform con GSAP.
                'enabled'   => true,
                'scroll_3d' => false,
            ),
            // Las tarjetas se muestran desde el MVP aunque aún no exista media real:
            // sin media → placeholder temporal; al añadir 'media' (ruta/URL/ID válido)
            // el placeholder se sustituye automáticamente por el archivo real.
            // Ejemplo con media real:
            //   array('id'=>'card-1','type'=>'image','media'=>'img/card-1.jpg',
            //         'alt'=>'…','x'=>82,'y'=>26,'glow'=>true,'scanline'=>true),
            'cards' => array(
                array(
                    'id'                => 'card-monitor',
                    'type'              => 'video',
                    'x'                 => 83,
                    'y'                 => 28,
                    'glow'              => true,
                    'scanline'          => true,
                    'placeholder_label' => 'Monitoreo en vivo',
                    'placeholder_enabled' => false,
                ),
                array(
                    'id'                => 'card-analysis',
                    'type'              => 'image',
                    'x'                 => 16,
                    'y'                 => 64,
                    'glow'              => true,
                    'placeholder_label' => 'Análisis',
                    'placeholder_enabled' => false,
                ),
                array(
                    'id'                => 'card-alert',
                    'type'              => 'image',
                    'x'                 => 86,
                    'y'                 => 74,
                    'glow'              => true,
                    'placeholder_label' => 'Alertas',
                    'placeholder_enabled' => false,
                ),
            ),
        ),
    ),

    array(
        'type'        => 'parallax-monitor',
        'instance_id' => 'home-parallax-monitor',
        'data'        => array(
            'content' => array(
                // Ref `referencias/bloque 2.png`: título con resaltado en negrita (no color)
                // + subtítulo kicker debajo. Sin eyebrow ni descripción ni CTA.
                'eyebrow'          => '',
                'title'            => 'Facilitando la toma de decisiones en tiempo real',
                'highlighted_text' => 'toma de decisiones',
                'subtitle'         => 'Monitoreo, gestión e inteligencia operativa',
                'description'      => '',
            ),
            'cta' => array(
                'enabled' => false,
            ),
            // background/monitor sin media real → fallback neutro/geométrico mínimo.
        ),
    ),

    array(
        'type'        => 'industry-carousel',
        'instance_id' => 'home-industry-carousel',
        'data'        => array(
            'content' => array(
                'eyebrow'      => 'Industrias',
                'heading_main' => 'Ecosistemas de seguridad',
                'heading_sub'  => 'físicos y virtuales a la medida',
            ),
            'cta' => array(
                'enabled' => true,
                'label'   => 'Saber más',
                'url'     => '/fabrica-de-tecnologias',
            ),
            'items' => array(
                array(
                    'title'       => 'Gasolineras',
                    'orange_text' => 'Gasolineras',
                    'image'       => '',
                    'alt'         => 'Seguridad en gasolineras',
                ),
                array(
                    'title'       => 'Seguridad pública',
                    'orange_text' => 'Seguridad pública',
                    'image'       => '',
                    'alt'         => 'Monitoreo de seguridad pública',
                ),
                array(
                    'title'       => 'Infraestructura',
                    'orange_text' => 'Infraestructura',
                    'image'       => '',
                    'alt'         => 'Vigilancia de infraestructura',
                ),
                array(
                    'title'       => 'Transporte',
                    'orange_text' => 'Transporte',
                    'image'       => '',
                    'alt'         => 'Control de transporte',
                ),
                array(
                    'title'       => 'Sector privado',
                    'orange_text' => 'Sector privado',
                    'image'       => '',
                    'alt'         => 'Seguridad sector privado',
                ),
            ),
            'animation' => array(
                'enabled'       => true,
                'pin_enabled'   => true,
                'disable_below' => 1024,
                'scrub'         => 1,
            ),
        ),
    ),

    array(
        'type'        => 'product-story',
        'instance_id' => 'home-product-story',
        'data'        => array(
            // Continúa SIN transición tras el Bloque 3 (mismo fondo claro).
            // Contenido de productos en los defaults del bloque
            // (config/blocks/product-story.php): RIA, COVIA, GIA.
        ),
    ),

    array(
        'type'        => 'mission-statement',
        'instance_id' => 'home-mission-statement',
        'data'        => array(
            // Bloque 5: texto institucional y gradiente animado desde la base.
            // Defaults editables en config/blocks/mission-statement.php.
        ),
    ),

    array(
        'type'        => 'news',
        'instance_id' => 'home-news',
        'data'        => array(
            // Bloque 6: carrusel de noticias/referencias.
            // Consulta posts de categoría `noticias`; sin posts usa placeholders.
            // Cambiar posts_per_page para ajustar cuántas noticias se muestran.
            'query' => array(
                'source'         => 'category',
                'category'       => 'noticias',
                'posts_per_page' => 6,
            ),
        ),
    ),
);
