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
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

return array(
    'logo' => array(
        'text'  => 'OKIP',
        'image' => '', // ruta a assets/, URL o ID de attachment (futuro).
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
