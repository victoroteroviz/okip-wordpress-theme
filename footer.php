<?php

/**
 * Shell de pie: cierre de <main>, footer del sitio y wp_footer().
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
    </main><!-- #okip-content -->

    <?php get_template_part('template-parts/layout/footer-site'); ?>

    <?php wp_footer(); ?>
</body>

</html>
