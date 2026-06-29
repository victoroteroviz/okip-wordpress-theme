<?php

/**
 * Panel admin de bloques OKIP.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Slugs de páginas configurables.
 *
 * @return string[]
 */
function okip_admin_page_slugs()
{
    $files = glob(OKIP_DIR . '/config/pages/*.php');
    $slugs = array();
    if (is_array($files)) {
        foreach ($files as $file) {
            $slugs[] = basename($file, '.php');
        }
    }
    sort($slugs);
    return $slugs;
}

/**
 * Registra la página admin.
 *
 * @return void
 */
function okip_register_admin_pages()
{
    add_menu_page(
        __('OKIP Blocks', 'okip'),
        __('OKIP Blocks', 'okip'),
        'manage_options',
        'okip-blocks',
        'okip_render_blocks_admin_page',
        'dashicons-layout',
        58
    );
}
add_action('admin_menu', 'okip_register_admin_pages');

/**
 * Assets del panel.
 *
 * @param string $hook
 * @return void
 */
function okip_admin_enqueue_assets($hook)
{
    if ($hook !== 'toplevel_page_okip-blocks') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style(
        'okip-admin-blocks',
        OKIP_URI . '/assets/css/admin-blocks.css',
        array(),
        okip_asset_version(OKIP_DIR . '/assets/css/admin-blocks.css')
    );
    wp_enqueue_script(
        'okip-admin-blocks',
        OKIP_URI . '/assets/js/admin-blocks.js',
        array(),
        okip_asset_version(OKIP_DIR . '/assets/js/admin-blocks.js'),
        true
    );
    wp_localize_script('okip-admin-blocks', 'OKIP_ADMIN', array(
        'fonts' => okip_google_fonts_catalog(),
    ));
}
add_action('admin_enqueue_scripts', 'okip_admin_enqueue_assets');

/**
 * Render principal (solo pantalla).
 *
 * El manejo de POST (permisos, nonce, saneo y persistencia) vive en
 * inc/admin/save-handlers.php; el resultado llega vía notices. Tras guardar, la
 * pantalla se vuelve a dibujar con okip_get_page_blocks($slug) → muestra los
 * datos ya aplicados (overrides incluidos).
 *
 * @return void
 */
function okip_render_blocks_admin_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $available_slugs = okip_admin_page_slugs();
    $slug = okip_admin_resolve_slug($available_slugs);

    // Procesa el POST (si lo hay) y deja notices; nunca hace wp_die por nonce.
    okip_admin_handle_post($slug);

    $blocks = $slug !== '' ? okip_get_page_blocks($slug) : array();
    ?>
    <div class="wrap okip-admin">
        <h1><?php esc_html_e('OKIP Blocks', 'okip'); ?></h1>
        <?php okip_admin_render_notices(); ?>

        <form method="get" class="okip-admin-toolbar">
            <input type="hidden" name="page" value="okip-blocks">
            <label>
                <span><?php esc_html_e('Página', 'okip'); ?></span>
                <select name="okip_page_slug" onchange="this.form.submit()">
                    <?php foreach ($available_slugs as $page_slug) : ?>
                        <option value="<?php echo esc_attr($page_slug); ?>" <?php selected($slug, $page_slug); ?>><?php echo esc_html($page_slug); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <form method="post" class="okip-admin-form">
            <?php wp_nonce_field('okip_blocks_admin', 'okip_blocks_nonce'); ?>
            <input type="hidden" name="okip_page_slug" value="<?php echo esc_attr($slug); ?>">

            <div class="okip-admin-actions">
                <button type="submit" name="okip_save_blocks" class="button button-primary"><?php esc_html_e('Guardar cambios', 'okip'); ?></button>
                <button type="submit" name="okip_refresh_fonts" class="button"><?php esc_html_e('Refrescar catálogo de fuentes', 'okip'); ?></button>
            </div>

            <?php foreach ($blocks as $block) : ?>
                <?php
                $type = isset($block['type']) ? $block['type'] : '';
                $instance_id = isset($block['instance_id']) ? $block['instance_id'] : $type;
                ?>
                <section class="okip-admin-block">
                    <header class="okip-admin-block__head">
                        <span><?php echo esc_html($type); ?></span>
                        <code><?php echo esc_html($instance_id); ?></code>
                    </header>
                    <?php if ($type === 'hero') : ?>
                        <?php okip_render_admin_hero_editor($instance_id, isset($block['data']) ? $block['data'] : array()); ?>
                    <?php else : ?>
                        <p class="description"><?php esc_html_e('Este bloque se muestra para contexto. Su editor se añadirá usando los mismos campos reutilizables.', 'okip'); ?></p>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>

            <div class="okip-admin-actions okip-admin-actions--bottom">
                <button type="submit" name="okip_save_blocks" class="button button-primary button-large"><?php esc_html_e('Guardar cambios', 'okip'); ?></button>
            </div>
        </form>
    </div>
    <?php
}

/*
 * El editor del bloque Hero (okip_render_admin_hero_editor) vive en
 * inc/admin/editors/hero.php y el render de cada tarjeta
 * (okip_admin_render_hero_card) en inc/admin/partials/hero-cards.php.
 * Ambos se cargan desde functions.php antes que este controlador.
 */
