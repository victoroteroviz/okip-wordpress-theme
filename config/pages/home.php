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
                // Escena de entrada con dos videos. Sin media real todavía → fondo
                // neutro (color sólido) y reveal tras image_reveal_delay.
                // Para activarla, coloca los archivos en assets/video/ y descomenta:
                //   'intro_media'    => 'video/hero-intro.mp4',
                //   'loop_media'     => 'video/hero-loop.mp4',
                //   'fallback_image' => 'img/hero-fallback.jpg',
                'type' => 'video',
            ),
            'overlay' => array(
                'enabled' => true,
                'color'   => '#020711',
                'opacity' => 0.35,
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
                ),
                array(
                    'id'                => 'card-analysis',
                    'type'              => 'image',
                    'x'                 => 16,
                    'y'                 => 64,
                    'glow'              => true,
                    'placeholder_label' => 'Análisis',
                ),
                array(
                    'id'                => 'card-alert',
                    'type'              => 'image',
                    'x'                 => 86,
                    'y'                 => 74,
                    'glow'              => true,
                    'placeholder_label' => 'Alertas',
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
);
