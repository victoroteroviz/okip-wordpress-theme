<?php

/**
 * Carga condicional de CSS/JS por bloque.
 *
 * Para cada tipo de bloque usado en la página actual, encola (si existen):
 *   template-parts/blocks/{type}/style.css
 *   template-parts/blocks/{type}/script.js
 *
 * - Versionado con filemtime() (cache-busting en desarrollo).
 * - Sin duplicados: un handle por tipo, aunque el bloque se repita N veces.
 * - El JS de bloque depende del runtime compartido de animaciones.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Versión de un asset basada en su fecha de modificación (o la del tema).
 *
 * @param string $abs_path Ruta absoluta al archivo.
 * @return string|int
 */
function okip_asset_version($abs_path)
{
    return file_exists($abs_path) ? filemtime($abs_path) : OKIP_VERSION;
}

/**
 * Encola los assets de los tipos de bloque indicados.
 *
 * @param string[] $types Tipos de bloque usados en la página.
 * @return void
 */
function okip_enqueue_block_assets($types)
{
    if (! is_array($types)) {
        return;
    }

    foreach ($types as $type) {
        $type = sanitize_key($type);
        if (! okip_is_allowed_block($type)) {
            continue;
        }

        $dir = okip_block_dir($type);
        $url = OKIP_URI . '/template-parts/blocks/' . $type;

        $css = $dir . '/style.css';
        if (file_exists($css)) {
            wp_enqueue_style(
                'okip-block-' . $type,
                $url . '/style.css',
                array('okip-animations'),
                okip_asset_version($css)
            );
        }

        $js = $dir . '/script.js';
        if (file_exists($js)) {
            wp_enqueue_script(
                'okip-block-' . $type,
                $url . '/script.js',
                array('okip-animations'),
                okip_asset_version($js),
                true
            );
        }
    }
}
