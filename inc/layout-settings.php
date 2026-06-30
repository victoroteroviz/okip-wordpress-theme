<?php

/**
 * Configuración global editable del layout del sitio.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Option key para overrides globales de layout.
 *
 * @return string
 */
function okip_layout_settings_option_key()
{
    return 'okip_layout_settings_overrides';
}

/**
 * Overrides persistidos para navbar/footer.
 *
 * @return array
 */
function okip_get_layout_settings_overrides()
{
    $overrides = get_option(okip_layout_settings_option_key(), array());
    return is_array($overrides) ? $overrides : array();
}

/**
 * Config efectiva de una pieza global de layout.
 *
 * @param string $section navbar|footer.
 * @return array
 */
function okip_layout_config($section)
{
    $section = sanitize_key($section);
    $base = okip_block_defaults($section);
    $overrides = okip_get_layout_settings_overrides();
    $section_overrides = isset($overrides[$section]) && is_array($overrides[$section])
        ? $overrides[$section]
        : array();

    return okip_merge_defaults($section_overrides, is_array($base) ? $base : array());
}
