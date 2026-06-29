<?php

/**
 * Persistencia de overrides del panel admin.
 *
 * Única puerta de ESCRITURA de los overrides por página. La LECTURA sigue en
 * inc/data.php (okip_get_page_block_overrides / okip_page_overrides_option_key),
 * que es la fuente compartida con el front. El formato de la option no cambia:
 *   okip_page_blocks_overrides_{slug}  =>  [ instance_id => ['type'=>..,'data'=>..] ]
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Guarda los overrides de una página y reporta qué ocurrió realmente.
 *
 * Compara contra el valor previo para no mentir: solo escribe si hay cambios y
 * distingue "guardado" de "sin cambios" y de "error" (update_option falló por una
 * razón distinta a que el valor ya fuera igual).
 *
 * @param string $slug
 * @param array  $overrides Overrides ya saneados.
 * @return string 'saved' | 'unchanged' | 'error'
 */
function okip_save_page_block_overrides($slug, array $overrides)
{
    $key = okip_page_overrides_option_key($slug);

    $previous = get_option($key, array());
    $previous = is_array($previous) ? $previous : array();

    // Sin diferencias reales contra lo ya guardado: no se escribe nada.
    if ($previous == $overrides) { // phpcs:ignore -- comparación de contenido (no de orden).
        return 'unchanged';
    }

    if (update_option($key, $overrides, false)) {
        return 'saved';
    }

    // update_option devolvió false aun habiendo diferencia previa: confirmar leyendo.
    $now = get_option($key, array());
    $now = is_array($now) ? $now : array();
    return ($now == $overrides) ? 'saved' : 'error';
}
