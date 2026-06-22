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
            'cards' => array(
                array(
                    'id'       => 'card-1',
                    'type'     => 'image',
                    'media'    => '', // sin medio → placeholder visual
                    'alt'      => 'Tarjeta de ejemplo 1',
                    'x'        => 80,
                    'y'        => 24,
                    'glow'     => true,
                    'scanline' => true,
                ),
                array(
                    'id'    => 'card-2',
                    'type'  => 'image',
                    'media' => '',
                    'alt'   => 'Tarjeta de ejemplo 2',
                    'x'     => 18,
                    'y'     => 70,
                    'glow'  => true,
                ),
            ),
        ),
    ),
);
