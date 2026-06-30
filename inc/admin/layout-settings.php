<?php

/**
 * Panel admin para configuración global de navbar/footer.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Guarda overrides globales de layout.
 *
 * @param array $overrides
 * @return string saved|unchanged|error
 */
function okip_save_layout_settings_overrides(array $overrides)
{
    $key = okip_layout_settings_option_key();
    $previous = get_option($key, array());
    $previous = is_array($previous) ? $previous : array();

    if ($previous == $overrides) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
        return 'unchanged';
    }

    if (update_option($key, $overrides, false)) {
        return 'saved';
    }

    $now = get_option($key, array());
    $now = is_array($now) ? $now : array();
    return ($now == $overrides) ? 'saved' : 'error'; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
}

/**
 * Sanea una lista simple de enlaces.
 *
 * @param mixed $raw
 * @param int   $limit
 * @return array
 */
function okip_admin_sanitize_link_list($raw, $limit = 8)
{
    $raw = is_array($raw) ? $raw : array();
    $links = array();

    foreach ($raw as $item) {
        if (! is_array($item)) {
            continue;
        }
        $label = isset($item['label']) ? sanitize_text_field((string) $item['label']) : '';
        $url = isset($item['url']) ? esc_url_raw((string) $item['url']) : '';
        if ($label === '' && $url === '') {
            continue;
        }
        $links[] = array(
            'label' => $label,
            'url'   => $url !== '' ? $url : '#',
        );
        if (count($links) >= $limit) {
            break;
        }
    }

    return $links;
}

/**
 * Sanea configuración enviada del layout.
 *
 * @param mixed $raw
 * @return array
 */
function okip_admin_sanitize_layout_settings($raw)
{
    $raw = is_array($raw) ? $raw : array();
    $navbar_base = okip_block_defaults('navbar');
    $footer_base = okip_block_defaults('footer');

    $nav = isset($raw['navbar']) && is_array($raw['navbar']) ? $raw['navbar'] : array();
    $nav_logo = isset($nav['logo']) && is_array($nav['logo']) ? $nav['logo'] : array();
    $nav_app = isset($nav['appearance']) && is_array($nav['appearance']) ? $nav['appearance'] : array();
    $nav_reveal = isset($nav['reveal']) && is_array($nav['reveal']) ? $nav['reveal'] : array();

    $full = array(
        'navbar' => array(
            'logo' => array(
                'text'  => isset($nav_logo['text']) ? sanitize_text_field((string) $nav_logo['text']) : '',
                'image' => okip_admin_sanitize_media_ref(isset($nav_logo['image']) ? $nav_logo['image'] : ''),
            ),
            'appearance' => array(
                'background_color'       => sanitize_hex_color(isset($nav_app['background_color']) ? $nav_app['background_color'] : '') ?: '#000000',
                'background_opacity'     => okip_clamp_float(isset($nav_app['background_opacity']) ? $nav_app['background_opacity'] : .86, 0, 1),
                'blur'                   => okip_clamp_int(isset($nav_app['blur']) ? $nav_app['blur'] : 14, 0, 60),
                'border_opacity'         => okip_clamp_float(isset($nav_app['border_opacity']) ? $nav_app['border_opacity'] : .12, 0, 1),
                'text_color'             => sanitize_hex_color(isset($nav_app['text_color']) ? $nav_app['text_color'] : '') ?: '#ffffff',
                'active_underline_color' => sanitize_hex_color(isset($nav_app['active_underline_color']) ? $nav_app['active_underline_color'] : '') ?: '#ffffff',
            ),
            'menu' => okip_admin_sanitize_link_list(isset($nav['menu']) ? $nav['menu'] : array(), 8),
            'reveal' => array(
                'reveal_mode'               => okip_one_of(isset($nav_reveal['reveal_mode']) ? $nav_reveal['reveal_mode'] : 'after_hero', array('after_hero', 'always', 'manual'), 'after_hero'),
                'reveal_offset'             => okip_clamp_int(isset($nav_reveal['reveal_offset']) ? $nav_reveal['reveal_offset'] : 0, -500, 500),
                'hide_on_hero'              => okip_bool(isset($nav_reveal['hide_on_hero']) ? $nav_reveal['hide_on_hero'] : false),
                'use_intersection_observer' => okip_bool(isset($nav_reveal['use_intersection_observer']) ? $nav_reveal['use_intersection_observer'] : false),
            ),
        ),
    );

    $footer = isset($raw['footer']) && is_array($raw['footer']) ? $raw['footer'] : array();
    $footer_logo = isset($footer['logo']) && is_array($footer['logo']) ? $footer['logo'] : array();
    $footer_legal = isset($footer['legal']) && is_array($footer['legal']) ? $footer['legal'] : array();

    $columns = array();
    $raw_columns = isset($footer['columns']) && is_array($footer['columns']) ? $footer['columns'] : array();
    foreach ($raw_columns as $column) {
        if (! is_array($column)) {
            continue;
        }
        $title = isset($column['title']) ? sanitize_text_field((string) $column['title']) : '';
        $links = okip_admin_sanitize_link_list(isset($column['links']) ? $column['links'] : array(), 8);
        if ($title === '' && empty($links)) {
            continue;
        }
        $columns[] = array('title' => $title, 'links' => $links);
        if (count($columns) >= 4) {
            break;
        }
    }

    $social = isset($footer['social']) && is_array($footer['social']) ? $footer['social'] : array();
    $social_links = array();
    $raw_social_links = isset($social['links']) && is_array($social['links']) ? $social['links'] : array();
    foreach ($raw_social_links as $item) {
        if (! is_array($item)) {
            continue;
        }
        $network = okip_one_of(isset($item['network']) ? $item['network'] : '', array('facebook', 'instagram', 'linkedin', 'youtube'), '');
        if ($network === '') {
            continue;
        }
        $social_links[] = array(
            'network' => $network,
            'url'     => isset($item['url']) && esc_url_raw((string) $item['url']) ? esc_url_raw((string) $item['url']) : '#',
            'label'   => isset($item['label']) ? sanitize_text_field((string) $item['label']) : ucfirst($network),
        );
        if (count($social_links) >= 6) {
            break;
        }
    }

    $full['footer'] = array(
        'logo' => array(
            'image' => okip_admin_sanitize_media_ref(isset($footer_logo['image']) ? $footer_logo['image'] : ''),
            'alt'   => isset($footer_logo['alt']) ? sanitize_text_field((string) $footer_logo['alt']) : '',
        ),
        'columns' => $columns,
        'social' => array(
            'title' => isset($social['title']) ? sanitize_text_field((string) $social['title']) : '',
            'links' => $social_links,
        ),
        'legal' => array(
            'cookies_label'    => isset($footer_legal['cookies_label']) ? sanitize_text_field((string) $footer_legal['cookies_label']) : '',
            'cookies_url'      => isset($footer_legal['cookies_url']) ? esc_url_raw((string) $footer_legal['cookies_url']) : '',
            'copyright_format' => isset($footer_legal['copyright_format']) ? sanitize_text_field((string) $footer_legal['copyright_format']) : '',
            'terms_label'      => isset($footer_legal['terms_label']) ? sanitize_text_field((string) $footer_legal['terms_label']) : '',
            'terms_url'        => isset($footer_legal['terms_url']) ? esc_url_raw((string) $footer_legal['terms_url']) : '',
        ),
    );

    $diff = array(
        'navbar' => okip_array_diff_recursive($full['navbar'], $navbar_base),
        'footer' => okip_array_diff_recursive($full['footer'], $footer_base),
    );

    return array_filter($diff);
}

/**
 * Procesa el POST del panel Layout.
 *
 * @return void
 */
function okip_admin_handle_layout_load()
{
    if (! isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    if (! current_user_can('manage_options')) {
        okip_admin_add_notice('error', __('No tienes permisos para guardar estos cambios.', 'okip'));
    } else {
        $nonce = isset($_POST['okip_layout_nonce']) ? wp_unslash($_POST['okip_layout_nonce']) : '';
        if (! wp_verify_nonce($nonce, 'okip_layout_admin')) {
            okip_admin_add_notice('error', __('La sesión del formulario expiró o no es válida. Recarga la página e inténtalo de nuevo.', 'okip'));
        } elseif (isset($_POST['okip_save_layout'])) {
            $raw = isset($_POST['okip_layout']) ? wp_unslash($_POST['okip_layout']) : array();
            $result = okip_save_layout_settings_overrides(okip_admin_sanitize_layout_settings($raw));
            if ($result === 'saved') {
                okip_admin_add_notice('success', __('Configuración de layout guardada.', 'okip'));
            } elseif ($result === 'unchanged') {
                okip_admin_add_notice('info', __('No había cambios que guardar.', 'okip'));
            } else {
                okip_admin_add_notice('error', __('No se pudo guardar la configuración de layout.', 'okip'));
            }
        }
    }

    okip_admin_persist_notices();
    wp_safe_redirect(add_query_arg(array('page' => 'okip-layout'), admin_url('admin.php')));
    exit;
}

/**
 * Renderiza una lista fija de enlaces.
 *
 * @param string $base_name
 * @param array  $links
 * @param int    $count
 * @return void
 */
function okip_admin_render_link_rows($base_name, array $links, $count)
{
    for ($i = 0; $i < $count; $i++) {
        $link = isset($links[$i]) && is_array($links[$i]) ? $links[$i] : array('label' => '', 'url' => '');
        echo '<div class="okip-admin-grid okip-admin-grid--two okip-admin-row">';
        okip_admin_text_field(__('Etiqueta', 'okip'), $base_name . '[' . $i . '][label]', isset($link['label']) ? $link['label'] : '');
        okip_admin_text_field(__('URL', 'okip'), $base_name . '[' . $i . '][url]', isset($link['url']) ? $link['url'] : '');
        echo '</div>';
    }
}

/**
 * Pantalla de Layout.
 *
 * @return void
 */
function okip_render_layout_admin_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    okip_admin_load_persisted_notices();
    $navbar = okip_layout_config('navbar');
    $footer = okip_layout_config('footer');
    ?>
    <div class="wrap okip-admin">
        <h1><?php esc_html_e('OKIP Layout', 'okip'); ?></h1>
        <?php okip_admin_render_notices(); ?>

        <form method="post" class="okip-admin-form">
            <?php wp_nonce_field('okip_layout_admin', 'okip_layout_nonce'); ?>

            <div class="okip-admin-actions">
                <button type="submit" name="okip_save_layout" class="button button-primary"><?php esc_html_e('Guardar layout', 'okip'); ?></button>
            </div>

            <div class="okip-admin-tabs okip-admin-page-tabs" data-okip-tabs>
                <div class="okip-admin-tabs__nav" role="tablist">
                    <button type="button" class="okip-admin-tab-btn is-active" data-okip-tab-target="navbar"><?php esc_html_e('Navbar', 'okip'); ?></button>
                    <button type="button" class="okip-admin-tab-btn" data-okip-tab-target="footer"><?php esc_html_e('Footer', 'okip'); ?></button>
                </div>

                <div class="okip-admin-tab-panel is-active" data-okip-tab="navbar">
                    <section class="okip-admin-block">
                        <header class="okip-admin-block__head"><span><?php esc_html_e('Navbar global', 'okip'); ?></span><code>navbar</code></header>
                        <div class="okip-admin-panel">
                            <?php
                            okip_admin_section_open(__('Logo', 'okip'), __('Se usa si no hay logo personalizado asignado en Apariencia.', 'okip'));
                            echo '<div class="okip-admin-grid okip-admin-grid--two">';
                            okip_admin_text_field(__('Texto', 'okip'), 'okip_layout[navbar][logo][text]', isset($navbar['logo']['text']) ? $navbar['logo']['text'] : '');
                            okip_admin_media_field(__('Imagen', 'okip'), 'okip_layout[navbar][logo][image]', isset($navbar['logo']['image']) ? $navbar['logo']['image'] : '');
                            echo '</div>';
                            okip_admin_section_close();

                            okip_admin_section_open(__('Apariencia', 'okip'), __('Colores y transparencia del fondo fijo.', 'okip'));
                            echo '<div class="okip-admin-grid okip-admin-grid--two">';
                            okip_admin_color_field(__('Fondo', 'okip'), 'okip_layout[navbar][appearance][background_color]', $navbar['appearance']['background_color']);
                            okip_admin_number_field(__('Opacidad fondo', 'okip'), 'okip_layout[navbar][appearance][background_opacity]', $navbar['appearance']['background_opacity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
                            okip_admin_number_field(__('Blur px', 'okip'), 'okip_layout[navbar][appearance][blur]', $navbar['appearance']['blur'], '', array('min' => 0, 'max' => 60, 'step' => 1));
                            okip_admin_number_field(__('Opacidad borde', 'okip'), 'okip_layout[navbar][appearance][border_opacity]', $navbar['appearance']['border_opacity'], '', array('min' => 0, 'max' => 1, 'step' => '.01'));
                            okip_admin_color_field(__('Texto', 'okip'), 'okip_layout[navbar][appearance][text_color]', $navbar['appearance']['text_color']);
                            okip_admin_color_field(__('Subrayado activo', 'okip'), 'okip_layout[navbar][appearance][active_underline_color]', $navbar['appearance']['active_underline_color']);
                            echo '</div>';
                            okip_admin_section_close();

                            okip_admin_section_open(__('Comportamiento', 'okip'), __('Controla cuándo aparece el navbar en Home.', 'okip'));
                            echo '<div class="okip-admin-grid okip-admin-grid--two">';
                            okip_admin_select_field(__('Modo reveal', 'okip'), 'okip_layout[navbar][reveal][reveal_mode]', $navbar['reveal']['reveal_mode'], array('after_hero' => __('Después del hero', 'okip'), 'always' => __('Siempre visible', 'okip'), 'manual' => __('Manual por JS/CSS', 'okip')));
                            okip_admin_number_field(__('Offset px', 'okip'), 'okip_layout[navbar][reveal][reveal_offset]', $navbar['reveal']['reveal_offset'], '', array('min' => -500, 'max' => 500, 'step' => 1));
                            okip_admin_checkbox_field(__('Ocultar sobre hero', 'okip'), 'okip_layout[navbar][reveal][hide_on_hero]', ! empty($navbar['reveal']['hide_on_hero']));
                            okip_admin_checkbox_field(__('Usar IntersectionObserver', 'okip'), 'okip_layout[navbar][reveal][use_intersection_observer]', ! empty($navbar['reveal']['use_intersection_observer']));
                            echo '</div>';
                            okip_admin_section_close();

                            okip_admin_section_open(__('Menú fallback', 'okip'), __('Solo se usa cuando WordPress no tiene un menú asignado a la ubicación primary.', 'okip'));
                            okip_admin_render_link_rows('okip_layout[navbar][menu]', isset($navbar['menu']) ? $navbar['menu'] : array(), 6);
                            okip_admin_section_close();
                            ?>
                        </div>
                    </section>
                </div>

                <div class="okip-admin-tab-panel" data-okip-tab="footer">
                    <section class="okip-admin-block">
                        <header class="okip-admin-block__head"><span><?php esc_html_e('Footer global', 'okip'); ?></span><code>footer</code></header>
                        <div class="okip-admin-panel">
                            <?php
                            okip_admin_section_open(__('Logo', 'okip'), '');
                            echo '<div class="okip-admin-grid okip-admin-grid--two">';
                            okip_admin_media_field(__('Imagen', 'okip'), 'okip_layout[footer][logo][image]', isset($footer['logo']['image']) ? $footer['logo']['image'] : '');
                            okip_admin_text_field(__('Alt', 'okip'), 'okip_layout[footer][logo][alt]', isset($footer['logo']['alt']) ? $footer['logo']['alt'] : '');
                            echo '</div>';
                            okip_admin_section_close();

                            $columns = isset($footer['columns']) && is_array($footer['columns']) ? $footer['columns'] : array();
                            for ($c = 0; $c < 3; $c++) {
                                $column = isset($columns[$c]) && is_array($columns[$c]) ? $columns[$c] : array('title' => '', 'links' => array());
                                okip_admin_section_open(sprintf(__('Columna %d', 'okip'), $c + 1), '');
                                okip_admin_text_field(__('Título', 'okip'), 'okip_layout[footer][columns][' . $c . '][title]', isset($column['title']) ? $column['title'] : '');
                                okip_admin_render_link_rows('okip_layout[footer][columns][' . $c . '][links]', isset($column['links']) ? $column['links'] : array(), 6);
                                okip_admin_section_close();
                            }

                            $social = isset($footer['social']) && is_array($footer['social']) ? $footer['social'] : array();
                            okip_admin_section_open(__('Redes sociales', 'okip'), '');
                            okip_admin_text_field(__('Título', 'okip'), 'okip_layout[footer][social][title]', isset($social['title']) ? $social['title'] : '');
                            $social_links = isset($social['links']) && is_array($social['links']) ? $social['links'] : array();
                            for ($i = 0; $i < 4; $i++) {
                                $item = isset($social_links[$i]) && is_array($social_links[$i]) ? $social_links[$i] : array('network' => 'facebook', 'url' => '', 'label' => '');
                                echo '<div class="okip-admin-grid okip-admin-grid--three okip-admin-row">';
                                okip_admin_select_field(__('Red', 'okip'), 'okip_layout[footer][social][links][' . $i . '][network]', isset($item['network']) ? $item['network'] : 'facebook', array('facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'));
                                okip_admin_text_field(__('URL', 'okip'), 'okip_layout[footer][social][links][' . $i . '][url]', isset($item['url']) ? $item['url'] : '');
                                okip_admin_text_field(__('Label', 'okip'), 'okip_layout[footer][social][links][' . $i . '][label]', isset($item['label']) ? $item['label'] : '');
                                echo '</div>';
                            }
                            okip_admin_section_close();

                            $legal = isset($footer['legal']) && is_array($footer['legal']) ? $footer['legal'] : array();
                            okip_admin_section_open(__('Legal', 'okip'), '');
                            echo '<div class="okip-admin-grid okip-admin-grid--two">';
                            okip_admin_text_field(__('Texto cookies', 'okip'), 'okip_layout[footer][legal][cookies_label]', isset($legal['cookies_label']) ? $legal['cookies_label'] : '');
                            okip_admin_text_field(__('URL cookies', 'okip'), 'okip_layout[footer][legal][cookies_url]', isset($legal['cookies_url']) ? $legal['cookies_url'] : '');
                            okip_admin_text_field(__('Copyright', 'okip'), 'okip_layout[footer][legal][copyright_format]', isset($legal['copyright_format']) ? $legal['copyright_format'] : '');
                            okip_admin_text_field(__('Texto términos', 'okip'), 'okip_layout[footer][legal][terms_label]', isset($legal['terms_label']) ? $legal['terms_label'] : '');
                            okip_admin_text_field(__('URL términos', 'okip'), 'okip_layout[footer][legal][terms_url]', isset($legal['terms_url']) ? $legal['terms_url'] : '');
                            echo '</div>';
                            okip_admin_section_close();
                            ?>
                        </div>
                    </section>
                </div>
            </div>

            <div class="okip-admin-actions okip-admin-actions--bottom">
                <button type="submit" name="okip_save_layout" class="button button-primary button-large"><?php esc_html_e('Guardar layout', 'okip'); ?></button>
            </div>
        </form>
    </div>
    <?php
}
