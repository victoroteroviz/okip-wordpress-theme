<?php

/**
 * Esquema / defaults del navbar.
 *
 * - logo: texto temporal (y/o imagen futura).
 * - menu: menú de RESPALDO que se usa solo si no hay un menú "primary"
 *   asignado en WordPress. Soporta anclas internas (/#instance-id) y páginas.
 * - reveal: comportamiento de aparición del navbar.
 *     reveal_mode               after_hero | always | manual
 *     reveal_offset             px de margen para el disparo (IntersectionObserver)
 *     hide_on_hero              true → oculto mientras se ve el Hero (solo Home)
 *     use_intersection_observer true → IO; false/sin soporte → fallback scrollY
 * - appearance: fondo negro personalizable + blur gaussiano. Estos valores se
 *   exponen como variables CSS inline seguras en template-parts/layout/navbar.php
 *   y quedan preparados para que el futuro panel admin los edite.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

return array(
    'logo' => array(
        'text'  => 'OKIP',
        'image' => 'img/okip-logo-navbar.png', // ruta a assets/, URL o ID de attachment (futuro).
    ),
    'appearance' => array(
        'background_color'       => '#000000', // fondo negro (ref navbar.png)
        'background_opacity'     => 0.86,      // 0..1 — casi sólido
        'blur'                   => 14,        // px del blur gaussiano (backdrop-filter)
        'border_opacity'         => 0.12,      // 0..1 — borde inferior
        'text_color'             => '#ffffff', // color de los enlaces
        'active_underline_color' => '#ffffff', // subrayado del enlace activo
    ),
    'menu' => array(
        array('label' => 'Inteligencia mexicana', 'url' => '/#home-hero-main'),
        array('label' => 'Fábrica de tecnologías', 'url' => '/fabrica-de-tecnologias'),
        array('label' => 'Sala de prensa',         'url' => '/sala-de-prensa'),
        array('label' => '¿Cómo soy parte?',       'url' => '/contacto'),
    ),
    'reveal' => array(
        'reveal_mode'               => 'after_hero', // after_hero | always | manual
        'reveal_offset'             => 0,            // px
        'hide_on_hero'              => true,
        'use_intersection_observer' => true,
    ),
);
