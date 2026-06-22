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
                // Sin medio real todavía → fallback de gradiente oscuro.
                // Para usar video: 'type' => 'video', 'media' => 'video/hero.mp4'
                'type' => 'gradient',
            ),
            'overlay' => array(
                'enabled' => true,
                'color'   => '#020711',
                'opacity' => 0.35,
            ),
            'reveal' => array(
                // gradient/imagen → revela tras image_reveal_delay (1.5s).
                'strategy'        => 'video_end',
                'replay_on_enter' => true,
            ),
            'animation' => array(
                // El hundimiento del Hero durante la transición lo controla el
                // Bloque 2 (parallax-monitor) para evitar doble transform con GSAP.
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
                'eyebrow'          => 'Tecnología OKIP',
                'title'            => 'Inteligencia visual para proteger lo que importa',
                'highlighted_text' => 'proteger',
                'description'      => 'Integramos monitoreo, análisis y visualización para convertir señales complejas en decisiones claras.',
            ),
            'cta' => array(
                'enabled' => true,
                'label'   => 'Conocer tecnología',
                'url'     => '/fabrica-de-tecnologias',
            ),
            // background/monitor sin media real → fallback neutro/geométrico mínimo.
        ),
    ),
);
