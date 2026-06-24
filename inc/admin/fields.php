<?php

/**
 * Helpers de campos del panel admin.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Atributos HTML desde mapa asociativo.
 *
 * @param array<string,mixed> $attrs
 * @return string
 */
function okip_admin_attrs(array $attrs)
{
    $out = '';
    foreach ($attrs as $key => $value) {
        if ($value === null || $value === false) {
            continue;
        }
        $key = sanitize_key($key);
        if ($value === true) {
            $out .= ' ' . esc_attr($key);
        } else {
            $out .= ' ' . esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }
    }
    return $out;
}

/**
 * Campo base.
 *
 * @param string $label
 * @param string $description
 * @return void
 */
function okip_admin_field_open($label, $description = '')
{
    echo '<label class="okip-admin-field">';
    echo '<span class="okip-admin-field__label">' . esc_html($label) . '</span>';
    if ($description !== '') {
        echo '<span class="okip-admin-field__desc">' . esc_html($description) . '</span>';
    }
}

/**
 * Cierra campo base.
 *
 * @return void
 */
function okip_admin_field_close()
{
    echo '</label>';
}

/**
 * Campo de texto.
 */
function okip_admin_text_field($label, $name, $value, $description = '', array $attrs = array())
{
    okip_admin_field_open($label, $description);
    echo '<input type="text" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '"' . okip_admin_attrs($attrs) . '>';
    okip_admin_field_close();
}

/**
 * Campo textarea.
 */
function okip_admin_textarea_field($label, $name, $value, $description = '', array $attrs = array())
{
    okip_admin_field_open($label, $description);
    $attrs = array_merge(array('rows' => 3), $attrs);
    echo '<textarea name="' . esc_attr($name) . '"' . okip_admin_attrs($attrs) . '>' . esc_textarea((string) $value) . '</textarea>';
    okip_admin_field_close();
}

/**
 * Campo numérico.
 */
function okip_admin_number_field($label, $name, $value, $description = '', array $attrs = array())
{
    okip_admin_field_open($label, $description);
    $attrs = array_merge(array('step' => '1'), $attrs);
    echo '<input type="number" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '"' . okip_admin_attrs($attrs) . '>';
    okip_admin_field_close();
}

/**
 * Campo color.
 */
function okip_admin_color_field($label, $name, $value, $description = '')
{
    okip_admin_field_open($label, $description);
    echo '<input type="color" name="' . esc_attr($name) . '" value="' . esc_attr((string) ($value ?: '#000000')) . '">';
    okip_admin_field_close();
}

/**
 * Campo checkbox.
 */
function okip_admin_checkbox_field($label, $name, $checked, $description = '')
{
    echo '<label class="okip-admin-check">';
    // Hidden fallback: garantiza que la clave llegue en POST cuando el checkbox
    // está desmarcado (si no, el saneador caería en su default y no se podría apagar).
    echo '<input type="hidden" name="' . esc_attr($name) . '" value="0">';
    echo '<input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked((bool) $checked, true, false) . '>';
    echo '<span>' . esc_html($label) . '</span>';
    if ($description !== '') {
        echo '<small>' . esc_html($description) . '</small>';
    }
    echo '</label>';
}

/**
 * Select.
 *
 * @param string              $label
 * @param string              $name
 * @param string              $value
 * @param array<string,string> $options
 * @param string              $description
 * @return void
 */
function okip_admin_select_field($label, $name, $value, array $options, $description = '')
{
    okip_admin_field_open($label, $description);
    echo '<select name="' . esc_attr($name) . '">';
    foreach ($options as $option_value => $option_label) {
        echo '<option value="' . esc_attr($option_value) . '" ' . selected((string) $value, (string) $option_value, false) . '>' . esc_html($option_label) . '</option>';
    }
    echo '</select>';
    okip_admin_field_close();
}

/**
 * Campo de búsqueda libre de Google Fonts con preview.
 */
function okip_admin_font_search_field($label, $name, $value, $preview_text)
{
    $preview_id = 'okip-font-preview-' . md5($name);
    okip_admin_field_open($label, __('Busca una fuente de Google Fonts o escribe el nombre exacto.', 'okip'));
    echo '<input class="okip-admin-font-search" type="search" autocomplete="off" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" data-preview="' . esc_attr($preview_id) . '">';
    echo '<div class="okip-admin-font-results" data-font-results></div>';
    echo '<span id="' . esc_attr($preview_id) . '" class="okip-admin-font-preview">' . esc_html($preview_text) . '</span>';
    okip_admin_field_close();
}

/**
 * Grupo tipográfico reusable.
 *
 * @param string $legend
 * @param string $base_name
 * @param array  $typography
 * @param string $preview_text
 * @return void
 */
function okip_admin_typography_group($legend, $base_name, array $typography, $preview_text)
{
    $typography = okip_normalize_typography($typography, 'body');
    echo '<fieldset class="okip-admin-panel okip-admin-panel--nested">';
    echo '<legend>' . esc_html($legend) . '</legend>';
    echo '<div class="okip-admin-grid okip-admin-grid--two">';
    okip_admin_font_search_field(__('Fuente', 'okip'), $base_name . '[font_family]', $typography['font_family'], $preview_text);
    okip_admin_number_field(__('Peso', 'okip'), $base_name . '[font_weight]', $typography['font_weight'], '', array('min' => 100, 'max' => 900, 'step' => 100));
    okip_admin_number_field(__('Tamaño mínimo px', 'okip'), $base_name . '[min_px]', $typography['min_px'], '', array('min' => 10, 'max' => 140, 'step' => '.5'));
    okip_admin_number_field(__('Escala vw', 'okip'), $base_name . '[fluid_vw]', $typography['fluid_vw'], '', array('min' => .1, 'max' => 12, 'step' => '.1'));
    okip_admin_number_field(__('Tamaño máximo px', 'okip'), $base_name . '[max_px]', $typography['max_px'], '', array('min' => 10, 'max' => 180, 'step' => '.5'));
    okip_admin_number_field(__('Line-height', 'okip'), $base_name . '[line_height]', $typography['line_height'], '', array('min' => .8, 'max' => 2.4, 'step' => '.01'));
    okip_admin_number_field(__('Letter spacing px', 'okip'), $base_name . '[letter_spacing]', $typography['letter_spacing'], '', array('min' => 0, 'max' => 12, 'step' => '.1'));
    okip_admin_color_field(__('Color', 'okip'), $base_name . '[color]', $typography['color'] ?: '#ffffff');
    echo '</div>';
    echo '</fieldset>';
}
