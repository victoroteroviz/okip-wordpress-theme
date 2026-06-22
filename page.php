<?php

/**
 * Página estándar.
 *
 * Si existe config/pages/{slug}.php con bloques, se renderiza por el motor de
 * bloques. Si no, se cae al contenido nativo de la página (the_content()).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$okip_slug   = okip_current_page_slug();
$okip_blocks = $okip_slug !== '' ? okip_get_page_blocks($okip_slug) : array();

if (! empty($okip_blocks)) {
    okip_render_page($okip_blocks);
} else {
    // Fallback: contenido nativo de la página.
    while (have_posts()) :
        the_post();
        ?>
        <article <?php post_class('okip-page'); ?>>
            <div class="okip-container">
                <h1 class="okip-page__title"><?php the_title(); ?></h1>
                <div class="okip-page__content">
                    <?php the_content(); ?>
                </div>
            </div>
        </article>
        <?php
    endwhile;
}

get_footer();
