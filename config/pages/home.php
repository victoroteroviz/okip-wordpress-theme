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
            // Las tarjetas solo se renderizan con media REAL (ruta/URL/ID válido).
            // Ejemplo para cuando exista el asset:
            //   array('id'=>'card-1','type'=>'image','media'=>'img/card-1.jpg',
            //         'alt'=>'…','x'=>80,'y'=>24,'glow'=>true,'scanline'=>true),
            'cards' => array(),
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
