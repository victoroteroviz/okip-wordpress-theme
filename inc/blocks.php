<?php

/**
 * Motor de bloques: whitelist, defaults, normalización y render.
 *
 * Un bloque es una instancia: { type, instance_id, data }.
 * El mismo `type` puede repetirse con distinto `instance_id` y `data`.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Lista blanca de tipos de bloque que se pueden renderizar.
 *
 * Ningún valor de config puede cargar un PHP arbitrario: solo los tipos aquí
 * declarados resuelven a template-parts/blocks/{type}/block.php.
 *
 * @return string[]
 */
function okip_allowed_blocks()
{
    /**
     * @param string[] $types Tipos permitidos. Añadir aquí al sumar bloques.
     */
    return apply_filters('okip_allowed_blocks', array(
        'hero',
        'parallax-monitor',
        'industry-carousel',
        // 'product-story', 'statement', 'news'  ← se habilitarán en fases posteriores.
    ));
}

/**
 * ¿El tipo de bloque está permitido?
 *
 * @param string $type
 * @return bool
 */
function okip_is_allowed_block($type)
{
    return in_array($type, okip_allowed_blocks(), true);
}

/**
 * Ruta absoluta a la carpeta de un tipo de bloque.
 *
 * @param string $type
 * @return string
 */
function okip_block_dir($type)
{
    return OKIP_DIR . '/template-parts/blocks/' . $type;
}

/**
 * Defaults / esquema de un tipo de bloque, desde config/blocks/{type}.php.
 *
 * @param string $type
 * @return array
 */
function okip_block_defaults($type)
{
    $file = OKIP_DIR . '/config/blocks/' . sanitize_file_name($type) . '.php';
    if (! is_readable($file)) {
        return array();
    }
    $defaults = include $file;
    return is_array($defaults) ? $defaults : array();
}

/**
 * Normaliza la data de una instancia mezclándola con los defaults del tipo.
 *
 * Si existe un normalizador específico del tipo (okip_normalize_{type}_data),
 * se invoca después de la mezcla genérica (p.ej. para validar listas/tarjetas).
 *
 * @param string $type
 * @param array  $data
 * @return array
 */
function okip_normalize_block_data($type, $data)
{
    $merged = okip_merge_defaults(is_array($data) ? $data : array(), okip_block_defaults($type));

    $normalizer = 'okip_normalize_' . str_replace('-', '_', $type) . '_data';
    if (function_exists($normalizer)) {
        $merged = call_user_func($normalizer, $merged);
    }

    return is_array($merged) ? $merged : array();
}

/**
 * Renderiza un bloque cargando su template, con data normalizada y scope por
 * instancia.
 *
 * @param string $type
 * @param string $instance_id
 * @param array  $data
 * @return void
 */
function okip_render_block($type, $instance_id, $data = array())
{
    $type = sanitize_key($type);

    if (! okip_is_allowed_block($type)) {
        return;
    }

    $template = okip_block_dir($type) . '/block.php';
    if (! is_readable($template)) {
        return;
    }

    $instance_id = okip_sanitize_instance_id($instance_id, $type);
    $data        = okip_normalize_block_data($type, $data);

    get_template_part(
        'template-parts/blocks/' . $type . '/block',
        null,
        array(
            'type'        => $type,
            'instance_id' => $instance_id,
            'data'        => $data,
        )
    );
}

/**
 * Renderiza una página completa: una lista ordenada de instancias de bloque.
 *
 * @param array $page_config Salida de okip_get_page_blocks().
 * @return void
 */
function okip_render_page($page_config)
{
    if (! is_array($page_config)) {
        return;
    }

    foreach ($page_config as $i => $block) {
        if (! is_array($block) || empty($block['type'])) {
            continue;
        }
        $type        = $block['type'];
        $instance_id = isset($block['instance_id']) ? $block['instance_id'] : ($type . '-' . $i);
        $data        = isset($block['data']) && is_array($block['data']) ? $block['data'] : array();

        okip_render_block($type, $instance_id, $data);
    }
}

/**
 * Tipos de bloque únicos usados por una lista de instancias (para enqueue).
 *
 * @param array $page_config
 * @return string[]
 */
function okip_used_block_types($page_config)
{
    $types = array();
    if (is_array($page_config)) {
        foreach ($page_config as $block) {
            if (is_array($block) && ! empty($block['type'])) {
                $type = sanitize_key($block['type']);
                if (okip_is_allowed_block($type)) {
                    $types[$type] = true;
                }
            }
        }
    }
    return array_keys($types);
}
