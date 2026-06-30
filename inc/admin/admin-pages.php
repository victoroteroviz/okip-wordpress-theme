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
 * Panel para reordenar las instancias de bloque de la página seleccionada.
 *
 * @param array    $blocks     Bloques en el orden efectivo actual.
 * @param string[] $base_order Instance IDs en el orden base de config/.
 * @return void
 */
function okip_admin_render_block_order_panel($blocks, array $base_order)
{
    $blocks = is_array($blocks) ? $blocks : array();
    $base_order_json = wp_json_encode(array_values($base_order));
    if (! is_string($base_order_json)) {
        $base_order_json = '[]';
    }
    ?>
    <section class="okip-admin-order" data-okip-order data-base-order="<?php echo esc_attr($base_order_json); ?>">
        <header class="okip-admin-order__head">
            <div>
                <h2><?php esc_html_e('Orden de visualización', 'okip'); ?></h2>
                <p><?php esc_html_e('Define la secuencia en que los bloques se renderizan para esta página.', 'okip'); ?></p>
            </div>
        </header>

        <?php if (empty($blocks)) : ?>
            <p class="description okip-admin-order__empty">
                <?php esc_html_e('Esta página no tiene bloques configurados. Cuando se agreguen en config/pages, aparecerán aquí para ordenarlos.', 'okip'); ?>
            </p>
        <?php else : ?>
            <ol class="okip-admin-order__list" data-okip-order-list>
                <?php
                $position = 0;
                foreach ($blocks as $i => $block) :
                    if (! is_array($block) || empty($block['type'])) {
                        continue;
                    }
                    $type = sanitize_key($block['type']);
                    $instance_id = isset($block['instance_id']) ? $block['instance_id'] : ($type . '-' . $i);
                    $instance_id = okip_sanitize_instance_id($instance_id, $type);
                    $base_index = array_search($instance_id, $base_order, true);
                    $position++;
                    ?>
                    <li
                        class="okip-admin-order__item"
                        data-okip-order-item
                        data-instance-id="<?php echo esc_attr($instance_id); ?>"
                        draggable="true">
                        <input type="hidden" name="okip_block_order[]" value="<?php echo esc_attr($instance_id); ?>">
                        <span class="okip-admin-order__pos" data-okip-order-position><?php echo esc_html((string) $position); ?></span>
                        <span class="okip-admin-order__handle" data-okip-order-handle aria-hidden="true">
                            <span class="dashicons dashicons-menu"></span>
                        </span>
                        <span class="okip-admin-order__meta">
                            <strong><?php echo esc_html($type); ?></strong>
                            <code><?php echo esc_html($instance_id); ?></code>
                            <?php if ($base_index !== false) : ?>
                                <small><?php echo esc_html(sprintf(__('Base #%d', 'okip'), (int) $base_index + 1)); ?></small>
                            <?php endif; ?>
                        </span>
                        <span class="okip-admin-order__tools">
                            <button type="button" class="button button-small" data-okip-order-up><?php esc_html_e('Subir', 'okip'); ?></button>
                            <button type="button" class="button button-small" data-okip-order-down><?php esc_html_e('Bajar', 'okip'); ?></button>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
            <div class="okip-admin-order__actions">
                <button type="button" class="button" data-okip-order-reset><?php esc_html_e('Restaurar orden base', 'okip'); ?></button>
                <span class="description"><?php esc_html_e('Guarda los cambios para aplicar esta secuencia en el frontend.', 'okip'); ?></span>
            </div>
        <?php endif; ?>
    </section>
    <?php
}

/**
 * Registra la página admin.
 *
 * @return void
 */
function okip_register_admin_pages()
{
    $hook = add_menu_page(
        __('OKIP Blocks', 'okip'),
        __('OKIP Blocks', 'okip'),
        'manage_options',
        'okip-blocks',
        'okip_render_blocks_admin_page',
        'dashicons-layout',
        58
    );

    // El POST se procesa en load-{hook} (antes de imprimir HTML) para poder
    // redirigir (PRG) y evitar el reenvío del formulario al recargar.
    if ($hook) {
        add_action('load-' . $hook, 'okip_admin_handle_load');
    }
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
 * Render principal (solo pantalla, siempre GET).
 *
 * El POST se procesa antes, en load-{hook} (okip_admin_handle_load), que guarda y
 * redirige (PRG). Aquí solo se recuperan los notices persistidos por ese POST y se
 * dibuja la pantalla con okip_get_page_blocks($slug) → datos ya aplicados.
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

    // Notices dejados por el POST previo (sobreviven al redirect vía transient).
    okip_admin_load_persisted_notices();

    $blocks = $slug !== '' ? okip_get_page_blocks($slug) : array();
    $base_order = $slug !== '' ? okip_admin_base_block_order($slug) : array();
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

            <div class="okip-admin-tabs okip-admin-page-tabs" data-okip-tabs>
                <div class="okip-admin-tabs__nav" role="tablist">
                    <button type="button" class="okip-admin-tab-btn is-active" data-okip-tab-target="page-order"><?php esc_html_e('Orden de visualización', 'okip'); ?></button>
                    <button type="button" class="okip-admin-tab-btn" data-okip-tab-target="page-blocks"><?php esc_html_e('Configuración de bloques', 'okip'); ?></button>
                </div>

                <div class="okip-admin-tab-panel is-active" data-okip-tab="page-order">
                    <?php okip_admin_render_block_order_panel($blocks, $base_order); ?>
                </div>

                <div class="okip-admin-tab-panel" data-okip-tab="page-blocks">
                    <?php if (empty($blocks)) : ?>
                        <section class="okip-admin-block">
                            <p class="description"><?php esc_html_e('Esta página no tiene bloques configurados. Cuando se agreguen en config/pages, aquí aparecerán sus editores.', 'okip'); ?></p>
                        </section>
                    <?php else : ?>
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
                                <?php elseif ($type === 'news') : ?>
                                    <?php okip_render_admin_news_editor($instance_id, isset($block['data']) ? $block['data'] : array()); ?>
                                <?php elseif ($type === 'video-w-title') : ?>
                                    <?php okip_render_admin_video_w_title_editor($instance_id, isset($block['data']) ? $block['data'] : array()); ?>
                                <?php else : ?>
                                    <p class="description"><?php esc_html_e('Este bloque se muestra para contexto. Su editor se añadirá usando los mismos campos reutilizables.', 'okip'); ?></p>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

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
