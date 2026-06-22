<?php

/**
 * Configuración del tema: theme supports, menús, tamaños de imagen.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Soportes del tema y registro de menús.
 *
 * @return void
 */
function okip_setup()
{
    // Título gestionado por WordPress (<title> vía wp_head()).
    add_theme_support('title-tag');

    // Imágenes destacadas (las usará el bloque de noticias en fases futuras).
    add_theme_support('post-thumbnails');

    // Marcado HTML5 moderno.
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));

    // Logo personalizado (el navbar usa fallback de texto si no hay logo).
    add_theme_support('custom-logo', array(
        'height'      => 48,
        'width'       => 160,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    // Navegación híbrida: un menú primario (anclas internas y/o páginas).
    register_nav_menus(array(
        'primary' => __('Menú principal', 'okip'),
    ));
}
add_action('after_setup_theme', 'okip_setup');
