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
 * Opción donde el panel guarda overrides de una página.
 *
 * @param string $slug
 * @return string
 */
function okip_page_overrides_option_key($slug)
{
    return 'okip_page_blocks_overrides_' . sanitize_key($slug);
}

/**
 * Overrides guardados por el panel para una página.
 *
 * Formato:
 * [
 *   instance_id => ['type' => 'hero', 'data' => [...]],
 * ]
 *
 * @param string $slug
 * @return array
 */
function okip_get_page_block_overrides($slug)
{
    $overrides = get_option(okip_page_overrides_option_key($slug), array());
    return is_array($overrides) ? $overrides : array();
}

/**
 * Mezcla overrides del panel sobre la configuración base del theme.
 *
 * @param array  $blocks
 * @param string $slug
 * @return array
 */
function okip_apply_page_block_overrides($blocks, $slug)
{
    if (! is_array($blocks)) {
        return array();
    }

    $overrides = okip_get_page_block_overrides($slug);
    if (empty($overrides)) {
        return $blocks;
    }

    foreach ($blocks as $i => $block) {
        if (empty($block['instance_id']) || ! isset($overrides[$block['instance_id']])) {
            continue;
        }

        $override = $overrides[$block['instance_id']];
        if (! is_array($override)) {
            continue;
        }

        if (isset($override['type']) && isset($block['type']) && $override['type'] !== $block['type']) {
            continue;
        }

        if (! empty($override['data']) && is_array($override['data'])) {
            $base_data = isset($block['data']) && is_array($block['data']) ? $block['data'] : array();
            $blocks[$i]['data'] = okip_merge_defaults($override['data'], $base_data);
        }
    }

    return $blocks;
}
add_filter('okip_page_blocks', 'okip_apply_page_block_overrides', 20, 2);

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
