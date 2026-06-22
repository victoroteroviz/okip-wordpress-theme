<?php

/**
 * Footer del sitio (base mínima).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<footer class="okip-footer" role="contentinfo">
    <div class="okip-container okip-footer__inner">
        <p class="okip-footer__copy">
            &copy; <?php echo esc_html(date_i18n('Y')); ?> <?php echo esc_html(get_bloginfo('name')); ?>.
        </p>
    </div>
</footer>
