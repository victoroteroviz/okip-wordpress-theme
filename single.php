<?php

/**
 * Entrada individual.
 *
 * Base mínima; en fases futuras será la vista de cada noticia / sala de prensa.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<div class="okip-container okip-single">
    <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class('okip-single__article'); ?>>
            <h1 class="okip-single__title"><?php the_title(); ?></h1>
            <?php if (has_post_thumbnail()) : ?>
                <figure class="okip-single__thumb"><?php the_post_thumbnail('large'); ?></figure>
            <?php endif; ?>
            <div class="okip-single__content"><?php the_content(); ?></div>
        </article>
    <?php endwhile; ?>
</div>
<?php
get_footer();
