<?php

/**
 * Helpers de medios.
 *
 * En esta fase los medios se referencian por:
 *   - ID de attachment (int)         → Media Library de WordPress.
 *   - URL absoluta (http/https)      → recurso externo o ya resuelto.
 *   - Ruta relativa a assets/        → recurso por defecto del tema.
 *   - '' (vacío)                     → sin medio (el bloque usa su fallback).
 *
 * Más adelante el panel admin guardará IDs de attachment; este helper ya los
 * soporta sin cambios en los templates.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Resuelve una referencia de medio a una URL utilizable.
 *
 * @param mixed $value ID de attachment, URL absoluta o ruta relativa a assets/.
 * @return string URL o '' si no se puede resolver.
 */
function okip_media_url($value)
{
    if (empty($value)) {
        return '';
    }

    // ID de attachment.
    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
        $url = wp_get_attachment_url((int) $value);
        return $url ? $url : '';
    }

    if (! is_string($value)) {
        return '';
    }

    // URL absoluta o protocolo relativo.
    if (preg_match('#^(https?:)?//#', $value)) {
        return $value;
    }

    // Ruta relativa: se interpreta dentro de assets/ del tema.
    $relative = ltrim($value, '/');
    if (strpos($relative, 'assets/') !== 0) {
        $relative = 'assets/' . $relative;
    }
    return OKIP_URI . '/' . $relative;
}

/**
 * ¿La referencia de medio resuelve a algo que realmente existe?
 *
 * - ID de attachment → debe tener URL.
 * - URL externa (http/protocol-relative) → se asume válida (no se comprueba red).
 * - Ruta relativa a assets/ → el archivo debe existir en disco.
 *
 * Permite que el front sea media-driven: si no existe, el bloque usa su
 * fallback neutro en vez de pintar un media roto.
 *
 * @param mixed $value
 * @return bool
 */
function okip_media_exists($value)
{
    if (empty($value)) {
        return false;
    }
    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
        return (bool) wp_get_attachment_url((int) $value);
    }
    if (! is_string($value)) {
        return false;
    }
    if (preg_match('#^(https?:)?//#', $value)) {
        return true; // externo: se asume disponible.
    }
    $relative = ltrim($value, '/');
    if (strpos($relative, 'assets/') !== 0) {
        $relative = 'assets/' . $relative;
    }
    return file_exists(OKIP_DIR . '/' . $relative);
}

/**
 * Texto alternativo de un attachment (si el medio es un ID), o cadena vacía.
 *
 * @param mixed $value
 * @return string
 */
function okip_media_alt($value)
{
    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
        $alt = get_post_meta((int) $value, '_wp_attachment_image_alt', true);
        return is_string($alt) ? $alt : '';
    }
    return '';
}
