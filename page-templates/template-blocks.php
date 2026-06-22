<?php

/**
 * Template Name: Página por bloques (OKIP)
 *
 * Plantilla asignable desde el editor de WordPress (atributos de página) para
 * forzar que una página se renderice con el motor de bloques aunque su slug no
 * coincida con un archivo de config/pages/. Si no hay config con su slug, cae a
 * the_content() (mismo comportamiento que page.php).
 *
 * En el MVP se comporta igual que page.php; existe para no atar el render por
 * bloques únicamente al slug.
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
    while (have_posts()) :
        the_post();
        ?>
        <article <?php post_class('okip-page'); ?>>
            <div class="okip-container">
                <h1 class="okip-page__title"><?php the_title(); ?></h1>
                <div class="okip-page__content"><?php the_content(); ?></div>
            </div>
        </article>
        <?php
    endwhile;
}

get_footer();
