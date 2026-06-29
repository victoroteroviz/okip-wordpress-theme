<?php

/**
 * Notices del panel admin de OKIP Blocks.
 *
 * Store request-scoped: el guardado (POST) y el render ocurren en la misma
 * petición (callback del menú), así que basta un acumulador estático. No se usan
 * transients ni redirect/PRG para no cambiar el flujo actual.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Acumula (o devuelve) los notices de la petición actual.
 *
 * @param string|null $type    success|info|warning|error. null = solo leer.
 * @param string      $message Texto ya traducido.
 * @return array<int, array{type:string, message:string}>
 */
function okip_admin_notices_store($type = null, $message = '')
{
    static $notices = array();
    if ($type !== null) {
        $notices[] = array('type' => (string) $type, 'message' => (string) $message);
    }
    return $notices;
}

/**
 * Añade un notice a la cola de la petición.
 *
 * @param string $type    success|info|warning|error.
 * @param string $message Texto ya traducido.
 * @return void
 */
function okip_admin_add_notice($type, $message)
{
    $allowed = array('success', 'info', 'warning', 'error');
    if (! in_array($type, $allowed, true)) {
        $type = 'info';
    }
    okip_admin_notices_store($type, $message);
}

/**
 * Clave del transient donde se guardan los notices entre el POST y el GET (PRG).
 * Es por usuario para no cruzar mensajes entre sesiones admin.
 *
 * @return string
 */
function okip_admin_notices_transient_key()
{
    return 'okip_admin_notices_' . get_current_user_id();
}

/**
 * Persiste los notices acumulados antes de un redirect (PRG). Caduca solo; además
 * se borra al leerlos. No escribe nada si no hay notices.
 *
 * @return void
 */
function okip_admin_persist_notices()
{
    $notices = okip_admin_notices_store();
    if (! empty($notices)) {
        set_transient(okip_admin_notices_transient_key(), $notices, MINUTE_IN_SECONDS);
    }
}

/**
 * Recupera los notices guardados por un POST previo (tras el redirect) y los pasa
 * a la cola en memoria para que okip_admin_render_notices() los imprima. El
 * transient se borra al leerlo (one-shot).
 *
 * @return void
 */
function okip_admin_load_persisted_notices()
{
    $key = okip_admin_notices_transient_key();
    $stored = get_transient($key);
    if (! is_array($stored)) {
        return;
    }
    delete_transient($key);
    foreach ($stored as $notice) {
        if (isset($notice['type'], $notice['message'])) {
            okip_admin_add_notice($notice['type'], $notice['message']);
        }
    }
}

/**
 * Imprime los notices acumulados con el marcado estándar de WP.
 *
 * @return void
 */
function okip_admin_render_notices()
{
    foreach (okip_admin_notices_store() as $notice) {
        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr($notice['type']),
            esc_html($notice['message'])
        );
    }
}
