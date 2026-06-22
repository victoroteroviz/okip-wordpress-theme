<?php

/**
 * Encolado de assets globales + GSAP local (condicional) + assets por bloque.
 *
 * GSAP/ScrollTrigger se cargan SOLO si los archivos reales existen en
 * assets/vendor/gsap/. Si no existen, el sitio funciona igual (los scripts
 * comprueban `typeof gsap` antes de animar). Nunca se usa CDN.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * ¿Está disponible GSAP localmente?
 *
 * @return bool
 */
function okip_has_gsap()
{
    return file_exists(OKIP_DIR . '/assets/vendor/gsap/gsap.min.js');
}

/**
 * ¿Está disponible ScrollTrigger localmente?
 *
 * @return bool
 */
function okip_has_scrolltrigger()
{
    return file_exists(OKIP_DIR . '/assets/vendor/gsap/ScrollTrigger.min.js');
}

/**
 * Añade la clase `okip-js` al <html> lo antes posible, para poder ocultar
 * estados iniciales de animación solo cuando hay JS (sin JS, todo es visible).
 *
 * @return void
 */
function okip_html_js_class()
{
    echo "<script>document.documentElement.classList.add('okip-js');</script>\n";
}
add_action('wp_head', 'okip_html_js_class', 1);

/**
 * Registra y encola todos los assets del front-end.
 *
 * @return void
 */
function okip_enqueue_assets()
{
    $css_dir = OKIP_DIR . '/assets/css';
    $css_url = OKIP_URI . '/assets/css';
    $js_dir  = OKIP_DIR . '/assets/js';
    $js_url  = OKIP_URI . '/assets/js';

    /* ---- CSS global (siempre, en cascada de dependencias) ---- */
    wp_enqueue_style('okip-tokens', $css_url . '/tokens.css', array(), okip_asset_version($css_dir . '/tokens.css'));
    wp_enqueue_style('okip-base', $css_url . '/base.css', array('okip-tokens'), okip_asset_version($css_dir . '/base.css'));
    wp_enqueue_style('okip-layout', $css_url . '/layout.css', array('okip-base'), okip_asset_version($css_dir . '/layout.css'));
    wp_enqueue_style('okip-components', $css_url . '/components.css', array('okip-layout'), okip_asset_version($css_dir . '/components.css'));

    /* ---- GSAP local (condicional) ---- */
    $gsap_deps = array();
    if (okip_has_gsap()) {
        wp_register_script('gsap', OKIP_URI . '/assets/vendor/gsap/gsap.min.js', array(), null, true);
        wp_enqueue_script('gsap');
        $gsap_deps[] = 'gsap';

        if (okip_has_scrolltrigger()) {
            wp_register_script('gsap-scrolltrigger', OKIP_URI . '/assets/vendor/gsap/ScrollTrigger.min.js', array('gsap'), null, true);
            wp_enqueue_script('gsap-scrolltrigger');
            $gsap_deps[] = 'gsap-scrolltrigger';
        }
    }

    /* ---- JS global ---- */
    wp_enqueue_script('okip-app', $js_url . '/app.js', array(), okip_asset_version($js_dir . '/app.js'), true);

    // gsap-init centraliza el registro de plugins y expone el estado de GSAP.
    wp_enqueue_script('okip-gsap-init', $js_url . '/gsap-init.js', array_merge(array('okip-app'), $gsap_deps), okip_asset_version($js_dir . '/gsap-init.js'), true);

    // Bandera para el JS: ¿GSAP/ScrollTrigger disponibles? (sin globales sueltas)
    wp_localize_script('okip-gsap-init', 'OKIP_ENV', array(
        'hasGsap'          => okip_has_gsap(),
        'hasScrollTrigger' => okip_has_gsap() && okip_has_scrolltrigger(),
    ));

    wp_enqueue_script('okip-navbar', $js_url . '/navbar.js', array('okip-gsap-init'), okip_asset_version($js_dir . '/navbar.js'), true);

    /* ---- Assets por bloque (solo los usados en esta página) ---- */
    $slug   = okip_current_page_slug();
    $blocks = $slug !== '' ? okip_get_page_blocks($slug) : array();
    okip_enqueue_block_assets(okip_used_block_types($blocks));
}
add_action('wp_enqueue_scripts', 'okip_enqueue_assets');
