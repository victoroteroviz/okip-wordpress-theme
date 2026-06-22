<?php

/**
 * Navegación híbrida del navbar.
 *
 * Usa el menú "primary" asignado en WordPress. Si no hay ninguno asignado,
 * cae a un menú de respaldo definido en config/blocks/navbar.php (para que el
 * sitio nunca quede sin navegación).
 *
 * Los enlaces pueden ser anclas internas (/#home-hero-main) o páginas (/contacto).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Configuración del navbar (logo + menú de respaldo) desde config/blocks/navbar.php.
 *
 * @return array
 */
function okip_navbar_config()
{
    $defaults = okip_block_defaults('navbar');
    return is_array($defaults) ? $defaults : array();
}

/**
 * Añade la clase `okip-navbar__link` a los <a> que genera wp_nav_menu en la
 * location "primary", para que los estilos del navbar apliquen igual que con el
 * menú de respaldo. (El CSS también targetea `.okip-navbar__menu a` por robustez.)
 *
 * @param array    $atts Atributos del <a>.
 * @param WP_Post  $item Item del menú.
 * @param stdClass $args Args de wp_nav_menu.
 * @return array
 */
function okip_nav_menu_link_attributes($atts, $item, $args)
{
    if (isset($args->theme_location) && $args->theme_location === 'primary') {
        $existing = isset($atts['class']) ? $atts['class'] . ' ' : '';
        $atts['class'] = trim($existing . 'okip-navbar__link');
    }
    return $atts;
}
add_filter('nav_menu_link_attributes', 'okip_nav_menu_link_attributes', 10, 3);

/**
 * Imprime el menú primario, o el fallback si no hay menú asignado.
 *
 * @param string $menu_id   Id del <ul> (para aria-controls).
 * @param string $extra_class Clases extra para el <ul>.
 * @return void
 */
function okip_nav_menu($menu_id = 'okip-primary-menu', $extra_class = '')
{
    $list_class = trim('okip-navbar__menu ' . $extra_class);

    if (has_nav_menu('primary')) {
        wp_nav_menu(array(
            'theme_location' => 'primary',
            'container'      => false,
            'menu_id'        => $menu_id,
            'menu_class'     => $list_class,
            'depth'          => 2,
            'fallback_cb'    => false,
        ));
        return;
    }

    // Fallback desde config.
    $config = okip_navbar_config();
    $items  = isset($config['menu']) && is_array($config['menu']) ? $config['menu'] : array();

    if (empty($items)) {
        return;
    }

    echo '<ul id="' . esc_attr($menu_id) . '" class="' . esc_attr($list_class) . '">';
    foreach ($items as $item) {
        if (empty($item['label']) || empty($item['url'])) {
            continue;
        }
        printf(
            '<li class="okip-navbar__item"><a class="okip-navbar__link" href="%s">%s</a></li>',
            esc_url($item['url']),
            esc_html($item['label'])
        );
    }
    echo '</ul>';
}
