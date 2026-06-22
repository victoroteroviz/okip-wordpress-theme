<?php

/**
 * Capa de datos: única puerta de entrada al contenido de cada página.
 *
 * Flujo actual:
 *   config/pages/{slug}.php  →  okip_get_page_blocks($slug)
 *
 * Flujo futuro (panel admin), SIN tocar los templates ni el motor:
 *   defaults del tema (config/)  ←  overrides del cliente (wp_options)
 *   Por eso los datos editables NUNCA se escribirán en archivos del tema.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Ruta absoluta al archivo de configuración de una página.
 *
 * @param string $slug
 * @return string
 */
function okip_page_config_file($slug)
{
    $slug = sanitize_file_name($slug);
    return OKIP_DIR . '/config/pages/' . $slug . '.php';
}

/**
 * Indica si existe configuración por bloques para una página.
 *
 * @param string $slug
 * @return bool
 */
function okip_page_has_config($slug)
{
    return $slug !== '' && is_readable(okip_page_config_file($slug));
}

/**
 * Devuelve la lista ordenada de bloques de una página.
 *
 * Cada elemento es: ['type' => string, 'instance_id' => string, 'data' => array].
 * El orden del array ES el orden de render (el futuro admin solo reordenará
 * este array, sin cambiar el motor).
 *
 * @param string $slug
 * @return array<int, array{type:string, instance_id:string, data:array}>
 */
function okip_get_page_blocks($slug)
{
    $slug = sanitize_file_name($slug);

    if (! okip_page_has_config($slug)) {
        return array();
    }

    $blocks = include okip_page_config_file($slug);

    /**
     * Punto de extensión para el futuro panel admin: aquí se mezclarán los
     * overrides guardados en wp_options (orden + data por instancia).
     *
     * @param array  $blocks Bloques definidos en config/.
     * @param string $slug   Slug de la página.
     */
    $blocks = apply_filters('okip_page_blocks', is_array($blocks) ? $blocks : array(), $slug);

    return is_array($blocks) ? $blocks : array();
}

/**
 * Slug de la página actualmente en pantalla (para enqueue condicional y render).
 *
 * @return string '' si la vista no es una página por bloques.
 */
function okip_current_page_slug()
{
    if (is_front_page()) {
        return 'home';
    }
    if (is_page()) {
        $slug = get_post_field('post_name', get_queried_object_id());
        return is_string($slug) ? $slug : '';
    }
    return '';
}
