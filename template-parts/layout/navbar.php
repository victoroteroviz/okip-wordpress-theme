<?php

/**
 * Navbar global.
 *
 * Fixed, fondo oscuro translúcido con blur (CSS). Links en desktop, hamburguesa
 * en tablet/móvil (accesible: aria-expanded, aria-controls, aria-label).
 * Menú desde WordPress (location "primary") o fallback de config (inc/nav.php).
 *
 * Visibilidad: la lógica vive en assets/js/navbar.js. Aquí solo se exponen los
 * ajustes de `reveal` como data-attributes y, en Home con hero, se aplica la
 * clase `okip-navbar--start-hidden` desde el servidor para evitar parpadeo
 * (el navbar nace oculto y JS decide cuándo mostrarlo). Sin JS, es visible.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

$okip_nav     = okip_navbar_config();
$okip_logo    = isset($okip_nav['logo']) ? $okip_nav['logo'] : array('text' => 'OKIP', 'image' => '');
$okip_logo_img = ! empty($okip_logo['image']) ? okip_media_url($okip_logo['image']) : '';
$okip_menu_id = 'okip-primary-menu';

$okip_reveal = isset($okip_nav['reveal']) && is_array($okip_nav['reveal']) ? $okip_nav['reveal'] : array();
$okip_reveal = wp_parse_args($okip_reveal, array(
    'reveal_mode'               => 'after_hero',
    'reveal_offset'             => 0,
    'hide_on_hero'              => true,
    'use_intersection_observer' => true,
));

// Apariencia: fondo negro configurable + blur gaussiano. Se expone como variables
// CSS inline seguras (preparado para edición desde el futuro panel admin).
$okip_appearance = isset($okip_nav['appearance']) && is_array($okip_nav['appearance']) ? $okip_nav['appearance'] : array();
$okip_appearance = wp_parse_args($okip_appearance, array(
    'background_color'       => '#000000',
    'background_opacity'     => 0.86,
    'blur'                   => 14,
    'border_opacity'         => 0.12,
    'text_color'             => '#ffffff',
    'active_underline_color' => '#ffffff',
));

$okip_nav_text   = sanitize_hex_color($okip_appearance['text_color']) ? sanitize_hex_color($okip_appearance['text_color']) : '#ffffff';
$okip_nav_active = sanitize_hex_color($okip_appearance['active_underline_color']) ? sanitize_hex_color($okip_appearance['active_underline_color']) : '#ffffff';
$okip_nav_blur   = okip_clamp_int($okip_appearance['blur'], 0, 60);

$okip_nav_style = sprintf(
    '--okip-navbar-bg:%s;--okip-navbar-blur:%dpx;--okip-navbar-border:%s;--okip-navbar-text:%s;--okip-navbar-active:%s;',
    okip_hex_to_rgba($okip_appearance['background_color'], $okip_appearance['background_opacity'], '#000000'),
    (int) $okip_nav_blur,
    okip_hex_to_rgba($okip_appearance['text_color'], $okip_appearance['border_opacity'], '#ffffff'),
    esc_attr($okip_nav_text),
    esc_attr($okip_nav_active)
);

// ¿Estamos en Home y la Home incluye un Hero? Solo entonces ocultamos de inicio.
$okip_home_has_hero = is_front_page()
    && in_array('hero', okip_used_block_types(okip_get_page_blocks('home')), true);

$okip_start_hidden = $okip_home_has_hero
    && $okip_reveal['reveal_mode'] === 'after_hero'
    && ! empty($okip_reveal['hide_on_hero']);

$okip_navbar_class = 'okip-navbar' . ($okip_start_hidden ? ' okip-navbar--start-hidden' : '');
?>
<header
    class="<?php echo esc_attr($okip_navbar_class); ?>"
    data-okip-navbar
    data-reveal-mode="<?php echo esc_attr($okip_reveal['reveal_mode']); ?>"
    data-reveal-offset="<?php echo esc_attr((string) (int) $okip_reveal['reveal_offset']); ?>"
    data-hide-on-hero="<?php echo ! empty($okip_reveal['hide_on_hero']) ? '1' : '0'; ?>"
    data-use-io="<?php echo ! empty($okip_reveal['use_intersection_observer']) ? '1' : '0'; ?>"
    style="<?php echo esc_attr($okip_nav_style); ?>"
    role="banner">
    <div class="okip-navbar__inner">

        <a class="okip-navbar__brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('OKIP — inicio', 'okip'); ?>">
            <?php if (has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php elseif ($okip_logo_img) : ?>
                <img class="okip-navbar__logo" src="<?php echo esc_url($okip_logo_img); ?>" alt="<?php echo esc_attr(! empty($okip_logo['text']) ? $okip_logo['text'] : 'OKIP'); ?>">
            <?php else : ?>
                <span class="okip-navbar__logo-text"><?php echo esc_html(! empty($okip_logo['text']) ? $okip_logo['text'] : 'OKIP'); ?></span>
            <?php endif; ?>
        </a>

        <button
            class="okip-navbar__toggle"
            type="button"
            data-okip-nav-toggle
            aria-label="<?php esc_attr_e('Abrir menú', 'okip'); ?>"
            aria-controls="<?php echo esc_attr($okip_menu_id); ?>"
            aria-expanded="false">
            <span class="okip-navbar__toggle-bar" aria-hidden="true"></span>
            <span class="okip-navbar__toggle-bar" aria-hidden="true"></span>
            <span class="okip-navbar__toggle-bar" aria-hidden="true"></span>
        </button>

        <nav class="okip-navbar__nav" data-okip-nav aria-label="<?php esc_attr_e('Navegación principal', 'okip'); ?>">
            <?php okip_nav_menu($okip_menu_id); ?>
        </nav>

    </div>
</header>
