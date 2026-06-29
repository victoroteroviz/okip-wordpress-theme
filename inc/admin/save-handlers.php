<?php

/**
 * Manejo de POST del panel admin de OKIP Blocks.
 *
 * Separa el guardado del render: aquí se valida permisos y nonce, se sanea y se
 * persiste; el resultado se comunica vía notices (inc/admin/notices.php). El
 * render (inc/admin/admin-pages.php) solo dibuja la pantalla con los datos ya
 * aplicados por okip_get_page_blocks().
 *
 * Nonce action/name, capability, option key y nombres de botón se mantienen
 * idénticos al flujo anterior (compatibilidad).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Resuelve el slug de página de forma segura, usando POST o GET de forma EXPLÍCITA
 * (no $_REQUEST). En POST manda el hidden del formulario; en navegación, el GET.
 *
 * @param string[] $available_slugs Slugs válidos (con config en config/pages/).
 * @return string '' si no hay ninguno disponible.
 */
function okip_admin_resolve_slug(array $available_slugs)
{
    $raw = '';
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['okip_page_slug'])) {
        $raw = wp_unslash($_POST['okip_page_slug']);
    } elseif (isset($_GET['okip_page_slug'])) {
        $raw = wp_unslash($_GET['okip_page_slug']);
    }

    $slug = sanitize_file_name((string) $raw);
    if (! in_array($slug, $available_slugs, true)) {
        $slug = in_array('home', $available_slugs, true)
            ? 'home'
            : (isset($available_slugs[0]) ? $available_slugs[0] : '');
    }
    return $slug;
}

/**
 * Procesa el POST del panel (guardar bloques o refrescar fuentes) y deja el
 * resultado en notices. No hace wp_die: un nonce inválido produce un notice de
 * error claro en vez de la pantalla "El enlace que seguiste ha caducado".
 *
 * @param string $slug Slug ya resuelto y validado.
 * @return void
 */
function okip_admin_handle_post($slug)
{
    if (! isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    // Permisos.
    if (! current_user_can('manage_options')) {
        okip_admin_add_notice('error', __('No tienes permisos para guardar estos cambios.', 'okip'));
        return;
    }

    // Nonce (no fatal): mismo action/name que wp_nonce_field('okip_blocks_admin', 'okip_blocks_nonce').
    $nonce = isset($_POST['okip_blocks_nonce']) ? wp_unslash($_POST['okip_blocks_nonce']) : '';
    if (! wp_verify_nonce($nonce, 'okip_blocks_admin')) {
        okip_admin_add_notice('error', __('La sesión del formulario expiró o no es válida. Recarga la página e inténtalo de nuevo.', 'okip'));
        return;
    }

    // Refrescar catálogo de fuentes.
    if (isset($_POST['okip_refresh_fonts'])) {
        $count = okip_refresh_google_fonts_catalog();
        okip_admin_add_notice('success', sprintf(__('Catálogo de fuentes actualizado: %d fuentes disponibles.', 'okip'), (int) $count));
        return;
    }

    // Guardar bloques.
    if (isset($_POST['okip_save_blocks'])) {
        if ($slug === '') {
            okip_admin_add_notice('error', __('No se pudo determinar la página a guardar.', 'okip'));
            return;
        }

        $raw_blocks = isset($_POST['okip_blocks']) ? wp_unslash($_POST['okip_blocks']) : array();
        $overrides  = okip_admin_sanitize_page_overrides($slug, $raw_blocks);
        $result     = okip_save_page_block_overrides($slug, $overrides);

        switch ($result) {
            case 'saved':
                okip_admin_add_notice('success', __('Cambios guardados.', 'okip'));
                break;
            case 'unchanged':
                okip_admin_add_notice('info', __('No había cambios que guardar.', 'okip'));
                break;
            default:
                okip_admin_add_notice('error', __('No se pudieron guardar los cambios. Inténtalo de nuevo.', 'okip'));
                break;
        }
    }
}
