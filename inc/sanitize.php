<?php

/**
 * Helpers de saneo compartidos por el motor de bloques.
 *
 * Regla: sanear en la entrada / al normalizar, escapar en la salida.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Limita un valor a un conjunto cerrado (lista blanca).
 *
 * @param mixed    $value
 * @param string[] $allowed
 * @param string   $default
 * @return string
 */
function okip_one_of($value, array $allowed, $default = '')
{
    return in_array($value, $allowed, true) ? $value : $default;
}

/**
 * Clampa un entero a un rango.
 *
 * @param mixed $value
 * @param int   $min
 * @param int   $max
 * @return int
 */
function okip_clamp_int($value, $min, $max)
{
    $value = (int) $value;
    return max($min, min($max, $value));
}

/**
 * Clampa un float a un rango.
 *
 * @param mixed $value
 * @param float $min
 * @param float $max
 * @return float
 */
function okip_clamp_float($value, $min, $max)
{
    $value = (float) $value;
    return max($min, min($max, $value));
}

/**
 * Saneo de un instance_id: legible, estable y seguro como id/clase/ancla HTML.
 *
 * @param mixed  $value
 * @param string $default
 * @return string
 */
function okip_sanitize_instance_id($value, $default = 'okip-block')
{
    $value = sanitize_html_class((string) $value);
    return $value !== '' ? $value : $default;
}

/**
 * Convierte un color hex (#rgb o #rrggbb) en una cadena rgba() con la opacidad
 * dada. Útil para exponer colores configurables como variables CSS seguras.
 *
 * @param mixed $hex     Color hex (con o sin #). Si es inválido → $fallback_hex.
 * @param mixed $opacity Opacidad 0..1.
 * @param string $fallback_hex Color por defecto si $hex no es válido.
 * @return string p.ej. "rgba(0,0,0,0.86)"
 */
function okip_hex_to_rgba($hex, $opacity, $fallback_hex = '#000000')
{
    $valid = function_exists('sanitize_hex_color') ? sanitize_hex_color((string) $hex) : '';
    if (! $valid) {
        $valid = function_exists('sanitize_hex_color') ? sanitize_hex_color($fallback_hex) : $fallback_hex;
    }
    $valid = ltrim((string) $valid, '#');
    if (strlen($valid) === 3) {
        $valid = $valid[0] . $valid[0] . $valid[1] . $valid[1] . $valid[2] . $valid[2];
    }
    if (strlen($valid) !== 6) {
        $valid = '000000';
    }
    $r = hexdec(substr($valid, 0, 2));
    $g = hexdec(substr($valid, 2, 2));
    $b = hexdec(substr($valid, 4, 2));
    $a = okip_clamp_float($opacity, 0, 1);
    return sprintf('rgba(%d,%d,%d,%s)', $r, $g, $b, rtrim(rtrim(number_format($a, 3, '.', ''), '0'), '.'));
}

/**
 * Convierte distintas formas de "verdadero" en bool.
 *
 * @param mixed $value
 * @return bool
 */
function okip_bool($value)
{
    if (is_bool($value)) {
        return $value;
    }
    if (is_string($value)) {
        return in_array(strtolower($value), array('1', 'true', 'yes', 'on'), true);
    }
    return ! empty($value);
}

/**
 * ¿El array es una lista secuencial (0,1,2…)? Se usa para distinguir listas
 * (p.ej. tarjetas) de mapas asociativos (p.ej. content/background) al mezclar.
 *
 * @param mixed $arr
 * @return bool
 */
function okip_is_list($arr)
{
    if (! is_array($arr)) {
        return false;
    }
    if (function_exists('array_is_list')) {
        return array_is_list($arr);
    }
    $i = 0;
    foreach ($arr as $k => $_) {
        if ($k !== $i++) {
            return false;
        }
    }
    return true;
}

/**
 * Mezcla recursiva de datos sobre defaults.
 *
 * - Mapas asociativos: se fusionan clave a clave (recursivo).
 * - Listas (secuenciales): el valor de $data reemplaza al default (no se mezcla
 *   índice a índice, porque las listas representan colecciones, p.ej. tarjetas).
 *
 * @param mixed $data     Datos de la instancia (parciales).
 * @param mixed $defaults Valores por defecto del tipo de bloque.
 * @return mixed
 */
function okip_merge_defaults($data, $defaults)
{
    if (! is_array($defaults)) {
        return $data === null ? $defaults : $data;
    }
    if (! is_array($data)) {
        return $defaults;
    }
    // Si los defaults son una lista, dejamos que los datos la reemplacen tal cual.
    if (okip_is_list($defaults)) {
        return $data;
    }

    $out = $defaults;
    foreach ($data as $key => $value) {
        if (array_key_exists($key, $defaults)) {
            $out[$key] = okip_merge_defaults($value, $defaults[$key]);
        } else {
            $out[$key] = $value;
        }
    }
    return $out;
}

/**
 * Diff recursivo de $new contra $base: devuelve SOLO las claves de $new cuyo valor
 * difiere de $base. Es la operación inversa de okip_merge_defaults, así que el
 * resultado puede volver a mezclarse sobre $base para reconstruir $new.
 *
 * Las listas (secuenciales) se tratan de forma ATÓMICA: si la lista cambió en algo,
 * se devuelve entera (coherente con okip_merge_defaults, que reemplaza listas).
 *
 * @param mixed $new
 * @param mixed $base
 * @return array Diff mínimo (mapa asociativo).
 */
function okip_array_diff_recursive($new, $base)
{
    if (! is_array($new)) {
        return $new;
    }
    // Listas: comparación atómica (igualdad estructural).
    if (okip_is_list($new) || ! is_array($base)) {
        return $new;
    }

    $diff = array();
    foreach ($new as $key => $value) {
        if (! array_key_exists($key, $base)) {
            $diff[$key] = $value;
            continue;
        }
        if (is_array($value) && ! okip_is_list($value)) {
            $sub = okip_array_diff_recursive($value, $base[$key]);
            if (! empty($sub)) {
                $diff[$key] = $sub;
            }
            continue;
        }
        if (is_array($value)) {
            // Lista: incluir entera solo si difiere.
            if ($value !== $base[$key]) {
                $diff[$key] = $value;
            }
            continue;
        }
        if ($value !== $base[$key]) {
            $diff[$key] = $value;
        }
    }
    return $diff;
}
