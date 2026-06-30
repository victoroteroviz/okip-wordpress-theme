<?php

/**
 * Footer del sitio (ref `referencias/image.png`).
 *
 * Datos desde config/blocks/footer.php (okip_block_defaults('footer')).
 * Estructura: logo + columnas de enlaces + redes sociales + línea legal.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Devuelve el SVG inline de una red social del footer.
 *
 * @param string $network facebook|instagram|linkedin|youtube
 * @return string Markup SVG (cadena vacía si la red no existe).
 */
if (! function_exists('okip_footer_social_icon')) {
    function okip_footer_social_icon($network)
    {
        $icons = array(
            'facebook'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M14 8.5h2.2V5.6c-.4-.05-1.2-.15-2.1-.15-2.1 0-3.5 1.3-3.5 3.6V11H8v3h2.6v7.5h3.2V14h2.5l.4-3h-2.9V9.3c0-.5.2-.8 1.2-.8z"/></svg>',
            'instagram' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="3.8"/><circle cx="16.8" cy="7.2" r="1.1" fill="currentColor" stroke="none"/></svg>',
            'linkedin'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M6.94 5a2 2 0 11-4 0 2 2 0 014 0zM3.25 8.5h3.4V21h-3.4V8.5zm5.5 0h3.26v1.7h.05c.45-.86 1.56-1.76 3.21-1.76 3.43 0 4.06 2.26 4.06 5.2V21h-3.4v-5.55c0-1.32-.02-3.02-1.84-3.02-1.84 0-2.12 1.44-2.12 2.92V21h-3.4V8.5z"/></svg>',
            'youtube'   => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M21.6 8.2a2.5 2.5 0 00-1.75-1.77C18.3 6 12 6 12 6s-6.3 0-7.85.43A2.5 2.5 0 002.4 8.2 26 26 0 002 12a26 26 0 00.4 3.8 2.5 2.5 0 001.75 1.77C5.7 18 12 18 12 18s6.3 0 7.85-.43a2.5 2.5 0 001.75-1.77A26 26 0 0022 12a26 26 0 00-.4-3.8zM10 14.6V9.4l4.4 2.6-4.4 2.6z"/></svg>',
        );

        return isset($icons[$network]) ? $icons[$network] : '';
    }
}

$okip_footer  = okip_layout_config('footer');
$okip_logo    = isset($okip_footer['logo']) && is_array($okip_footer['logo']) ? $okip_footer['logo'] : array();
$okip_logo_img = ! empty($okip_logo['image']) ? okip_media_url($okip_logo['image']) : '';
$okip_logo_alt = ! empty($okip_logo['alt']) ? $okip_logo['alt'] : get_bloginfo('name');

$okip_columns = isset($okip_footer['columns']) && is_array($okip_footer['columns']) ? $okip_footer['columns'] : array();
$okip_social  = isset($okip_footer['social']) && is_array($okip_footer['social']) ? $okip_footer['social'] : array();
$okip_legal   = isset($okip_footer['legal']) && is_array($okip_footer['legal']) ? $okip_footer['legal'] : array();
?>
<footer class="okip-footer" role="contentinfo">
    <div class="okip-container okip-footer__inner">
        <div class="okip-footer__brand">
            <a class="okip-footer__logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr($okip_logo_alt); ?>">
                <?php if ($okip_logo_img) : ?>
                    <img src="<?php echo esc_url($okip_logo_img); ?>" alt="<?php echo esc_attr($okip_logo_alt); ?>" width="64" height="64" loading="lazy" decoding="async">
                <?php else : ?>
                    <?php echo esc_html($okip_logo_alt); ?>
                <?php endif; ?>
            </a>
        </div>

        <?php foreach ($okip_columns as $okip_col) :
            $okip_col_links = isset($okip_col['links']) && is_array($okip_col['links']) ? $okip_col['links'] : array();
            if (empty($okip_col_links)) {
                continue;
            }
            ?>
            <nav class="okip-footer__col" aria-label="<?php echo esc_attr(isset($okip_col['title']) ? $okip_col['title'] : ''); ?>">
                <?php if (! empty($okip_col['title'])) : ?>
                    <h2 class="okip-footer__heading"><?php echo esc_html($okip_col['title']); ?></h2>
                <?php endif; ?>
                <ul class="okip-footer__links">
                    <?php foreach ($okip_col_links as $okip_link) : ?>
                        <li>
                            <a href="<?php echo esc_url(isset($okip_link['url']) ? $okip_link['url'] : '#'); ?>">
                                <?php echo esc_html(isset($okip_link['label']) ? $okip_link['label'] : ''); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endforeach; ?>

        <?php
        $okip_social_links = isset($okip_social['links']) && is_array($okip_social['links']) ? $okip_social['links'] : array();
        if (! empty($okip_social_links)) :
            ?>
            <div class="okip-footer__col okip-footer__social">
                <?php if (! empty($okip_social['title'])) : ?>
                    <h2 class="okip-footer__heading"><?php echo esc_html($okip_social['title']); ?></h2>
                <?php endif; ?>
                <ul class="okip-footer__social-list">
                    <?php foreach ($okip_social_links as $okip_soc) :
                        $okip_net  = isset($okip_soc['network']) ? $okip_soc['network'] : '';
                        $okip_icon = okip_footer_social_icon($okip_net);
                        if (! $okip_icon) {
                            continue;
                        }
                        $okip_soc_label = isset($okip_soc['label']) ? $okip_soc['label'] : ucfirst($okip_net);
                        ?>
                        <li>
                            <a class="okip-footer__social-link" href="<?php echo esc_url(isset($okip_soc['url']) ? $okip_soc['url'] : '#'); ?>" aria-label="<?php echo esc_attr($okip_soc_label); ?>">
                                <?php echo $okip_icon; // SVG estático y seguro definido en este archivo. ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <div class="okip-container okip-footer__legal">
        <?php if (! empty($okip_legal['cookies_label'])) : ?>
            <a class="okip-footer__legal-link" href="<?php echo esc_url(isset($okip_legal['cookies_url']) ? $okip_legal['cookies_url'] : '#'); ?>">
                <?php echo esc_html($okip_legal['cookies_label']); ?>
            </a>
        <?php endif; ?>
        <?php if (! empty($okip_legal['copyright_format'])) : ?>
            <p class="okip-footer__copy">
                <?php echo esc_html(sprintf($okip_legal['copyright_format'], date_i18n('Y'))); ?>
            </p>
        <?php endif; ?>
        <?php if (! empty($okip_legal['terms_label'])) : ?>
            <a class="okip-footer__legal-link" href="<?php echo esc_url(isset($okip_legal['terms_url']) ? $okip_legal['terms_url'] : '#'); ?>">
                <?php echo esc_html($okip_legal['terms_label']); ?>
            </a>
        <?php endif; ?>
    </div>
</footer>
