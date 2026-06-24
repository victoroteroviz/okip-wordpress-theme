<?php

/**
 * Campos de medios del panel admin.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Selector de medio reutilizable.
 *
 * @param string $label
 * @param string $name
 * @param mixed  $value
 * @param string $description
 * @return void
 */
function okip_admin_media_field($label, $name, $value, $description = '')
{
    okip_admin_field_open($label, $description);
    echo '<span class="okip-admin-media-field">';
    echo '<input type="text" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" data-okip-media-value>';
    echo '<button type="button" class="button" data-okip-media-select>' . esc_html__('Elegir medio', 'okip') . '</button>';
    echo '<button type="button" class="button-link" data-okip-media-clear>' . esc_html__('Limpiar', 'okip') . '</button>';
    echo '</span>';
    okip_admin_field_close();
}
