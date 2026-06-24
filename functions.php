<?php

/**
 * OKIP Theme — bootstrap.
 *
 * Punto de entrada del tema: carga los módulos de inc/ que contienen la
 * lógica (theme supports, enqueue, motor de bloques, navegación, etc.).
 * Aquí NO hay marcado de página.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit; // Acceso directo no permitido.
}

/**
 * Versión del tema. Se usa como fallback de cache-busting cuando no se puede
 * leer filemtime() de un asset.
 */
if (! defined('OKIP_VERSION')) {
    define('OKIP_VERSION', '0.1.0');
}

/** Ruta absoluta a la raíz del tema (sin barra final). */
if (! defined('OKIP_DIR')) {
    define('OKIP_DIR', get_template_directory());
}

/** URL a la raíz del tema (sin barra final). */
if (! defined('OKIP_URI')) {
    define('OKIP_URI', get_template_directory_uri());
}

/**
 * Carga un módulo de inc/ si existe.
 *
 * @param string $relative Ruta relativa a la raíz del tema.
 * @return void
 */
function okip_require($relative)
{
    $path = OKIP_DIR . '/' . ltrim($relative, '/');
    if (is_readable($path)) {
        require_once $path;
    }
}

/* Núcleo (siempre). */
okip_require('inc/sanitize.php');
okip_require('inc/design-controls.php');
okip_require('inc/animation-controls.php');
okip_require('inc/media.php');
okip_require('inc/data.php');
okip_require('inc/blocks.php');
okip_require('inc/block-loader.php');
okip_require('inc/nav.php');
okip_require('inc/setup.php');
okip_require('inc/enqueue.php');

/* Admin (solo en el back-office). Por ahora son stubs sin funcionalidad. */
if (is_admin()) {
    okip_require('inc/admin/sanitizers.php');
    okip_require('inc/admin/fields.php');
    okip_require('inc/admin/media-fields.php');
    okip_require('inc/admin/admin-pages.php');
}
