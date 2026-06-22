<?php

/**
 * Fallback de la jerarquía de plantillas (archivos, búsqueda, blog, etc.).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<div class="okip-container okip-archive">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class('okip-archive__item'); ?>>
                <h2 class="okip-archive__title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>
                <div class="okip-archive__excerpt"><?php the_excerpt(); ?></div>
            </article>
        <?php endwhile; ?>
        <?php the_posts_pagination(); ?>
    <?php else : ?>
        <p><?php esc_html_e('No hay contenido disponible.', 'okip'); ?></p>
    <?php endif; ?>
</div>
<?php
get_footer();
