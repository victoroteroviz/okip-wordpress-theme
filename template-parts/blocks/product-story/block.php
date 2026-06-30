<?php

/**
 * Bloque Product Story (Bloque 4).
 *
 * Ref visual: `referencias/image.png`. Sección OSCURA de ANCHO COMPLETO: fondo negro
 * con presencia azul en el borde derecho (degradado, por CSS), título superior
 * izquierdo (`SOLUCIONES`, uppercase por CSS) y tarjetas apiladas verticalmente.
 *
 * Cada tarjeta tiene DOS capas:
 *   - `okip-ps__back`  → capa de fondo gris/clara: heading + descripción (+ media
 *     opcional, off por defecto). Va en el flujo y DEFINE la altura de la tarjeta.
 *   - `okip-ps__cover` → vidrio/blur encima con un título grande monoespaciado
 *     (`cover_title`). Al hover (desktop, CSS) o tap/foco (touch/teclado, JS) se
 *     desplaza hacia arriba y descubre la capa de fondo.
 *
 * Animación (desktop, GSAP): reveal limpio POR TARJETA al entrar (fade/slide-up, NO
 * typewriter); el root NUNCA recibe transform (solo las tarjetas internas). Sin GSAP
 * (desktop): IO añade `is-revealed`. Móvil/reduce-motion/anim off: `is-static`, todo
 * legible. Los estados iniciales ocultos viven SOLO bajo
 * `.okip-js .okip-ps--animated:not(.is-static)` → sin JS, todo visible.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

$okip_instance = isset($args['instance_id']) ? $args['instance_id'] : 'product-story';
$okip_data     = isset($args['data']) && is_array($args['data']) ? $args['data'] : array();

$content    = isset($okip_data['content'])    ? $okip_data['content']    : array();
$layout     = isset($okip_data['layout'])     ? $okip_data['layout']     : array();
$items      = isset($okip_data['items'])      ? $okip_data['items']      : array();
$animation  = isset($okip_data['animation'])  ? $okip_data['animation']  : array();
$background = isset($okip_data['background'])  ? $okip_data['background'] : array();

if (empty($items)) {
    return;
}

// Layout.
$min_height    = isset($layout['min_height'])    ? $layout['min_height']    : 'auto';
$content_width = isset($layout['content_width']) ? $layout['content_width'] : '1180px';
// z-index raíz por ORDEN de render; layout.z_index>0 = override avanzado (retrocompat).
$z_index       = (isset($layout['z_index']) && (int) $layout['z_index'] > 0)
    ? (int) $layout['z_index']
    : ((isset($args['order']) ? (int) $args['order'] : 0) + 1);

// Contenido.
$title = isset($content['title']) && $content['title'] !== '' ? $content['title'] : 'SOLUCIONES';

// Animación.
$anim_on       = ! empty($animation['enabled']);
$use_gsap      = ! empty($animation['use_gsap']);
$use_vanilla   = ! empty($animation['use_vanilla_fallback']);
$disable_below = isset($animation['disable_below']) ? (int) $animation['disable_below'] : 1024;
$reveal        = isset($animation['reveal']) ? $animation['reveal'] : 'fade-up';

// Fondo del bloque (media-driven, prioridad imagen). Sin media válida → fallback CSS.
$bg_enabled   = ! empty($background['media_enabled']);
$bg_type      = isset($background['media_type']) ? $background['media_type'] : 'image';
$bg_media     = isset($background['media'])      ? $background['media']      : '';
$bg_alt       = isset($background['alt'])        ? $background['alt']        : '';
$bg_has_media = $bg_enabled && ! empty($bg_media) && okip_media_exists($bg_media);
$bg_url       = $bg_has_media ? okip_media_url($bg_media) : '';

$section_classes = 'okip-ps';
$section_classes .= $anim_on ? ' okip-ps--animated' : '';
$section_classes .= ' okip-ps--reveal-' . sanitize_html_class($reveal);
$section_classes .= $bg_has_media ? ' okip-ps--has-bg-media' : '';

$section_style = sprintf(
    '--okip-ps-minh:%s;--okip-ps-cw:%s;--okip-ps-z:%d;',
    esc_attr($min_height),
    esc_attr($content_width),
    $z_index
);
?>
<section
    id="<?php echo esc_attr($okip_instance); ?>"
    class="<?php echo esc_attr($section_classes); ?>"
    data-block-instance="<?php echo esc_attr($okip_instance); ?>"
    data-okip-ps
    data-anim="<?php echo $anim_on ? '1' : '0'; ?>"
    data-use-gsap="<?php echo $use_gsap ? '1' : '0'; ?>"
    data-use-vanilla="<?php echo $use_vanilla ? '1' : '0'; ?>"
    data-disable-below="<?php echo esc_attr((string) $disable_below); ?>"
    data-reveal="<?php echo esc_attr($reveal); ?>"
    style="<?php echo $section_style; ?>">

    <?php if ($bg_has_media) : ?>
        <div class="okip-ps__bg" aria-hidden="true">
            <?php if ($bg_type === 'video') : ?>
                <video class="okip-ps__bg-el" autoplay muted loop playsinline preload="metadata">
                    <source src="<?php echo esc_url($bg_url); ?>" type="video/mp4">
                </video>
            <?php else : /* image, gif, svg — prioridad imagen */ ?>
                <img class="okip-ps__bg-el"
                    src="<?php echo esc_url($bg_url); ?>"
                    alt="<?php echo esc_attr($bg_alt); ?>"
                    loading="lazy">
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="okip-ps__inner">

        <h2 class="okip-ps__title"><?php echo esc_html($title); ?></h2>

        <div class="okip-ps__cards">

            <?php foreach ($items as $idx => $item) :
                $cover_title  = isset($item['cover_title'])  ? $item['cover_title']  : '';
                $heading      = isset($item['heading'])      ? $item['heading']      : '';
                $description  = isset($item['description'])  ? $item['description']  : '';
                $cover_blur   = isset($item['cover_blur'])   ? (int) $item['cover_blur'] : 14;
                $cover_bg     = isset($item['cover_background'])   ? $item['cover_background']   : '#0b1222';
                $cover_op     = isset($item['cover_opacity'])      ? (float) $item['cover_opacity'] : 0.55;
                $cover_border = isset($item['cover_border_color']) ? $item['cover_border_color'] : '#33476e';
                $back_bg      = isset($item['background_color'])   ? $item['background_color']   : '#e7e7e7';

                $media_enabled = ! empty($item['media_enabled']);
                $media_type    = isset($item['media_type']) ? $item['media_type'] : 'image';
                $media         = isset($item['media'])      ? $item['media']      : '';
                $alt           = isset($item['alt'])        ? $item['alt']        : '';
                $has_media     = $media_enabled && $media_type !== 'placeholder' && ! empty($media) && okip_media_exists($media);
                $media_url     = $has_media ? okip_media_url($media) : '';

                // Variables seguras por tarjeta (clamps/saneo ya aplicados en normalize).
                $card_style = sprintf(
                    '--okip-ps-cover-blur:%dpx;--okip-ps-cover-bg:%s;--okip-ps-cover-border:%s;--okip-ps-card-bg:%s;',
                    $cover_blur,
                    esc_attr(okip_hex_to_rgba($cover_bg, $cover_op, '#0b1222')),
                    esc_attr($cover_border),
                    esc_attr($back_bg)
                );

                $cover_label = $cover_title !== '' ? $cover_title : ($heading !== '' ? $heading : 'Detalle');
                ?>
                <article
                    class="okip-ps__card<?php echo $has_media ? ' okip-ps__card--has-media' : ''; ?>"
                    data-okip-ps-card
                    data-index="<?php echo esc_attr((string) $idx); ?>"
                    style="<?php echo $card_style; ?>">

                    <!-- Capa de fondo (gris/clara): heading + descripción (+ media opcional) -->
                    <div class="okip-ps__back">
                        <?php if ($has_media) : ?>
                            <div class="okip-ps__media">
                                <?php if ($media_type === 'video') : ?>
                                    <video class="okip-ps__media-el" muted loop playsinline preload="none">
                                        <source src="<?php echo esc_url($media_url); ?>" type="video/mp4">
                                    </video>
                                <?php else : /* image, gif, svg */ ?>
                                    <img class="okip-ps__media-el"
                                        src="<?php echo esc_url($media_url); ?>"
                                        alt="<?php echo esc_attr($alt); ?>"
                                        loading="<?php echo $media_type === 'gif' ? 'eager' : 'lazy'; ?>">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="okip-ps__back-text">
                            <?php if ($heading !== '') : ?>
                                <p class="okip-ps__heading"><?php echo esc_html($heading); ?></p>
                            <?php endif; ?>
                            <?php if ($description !== '') : ?>
                                <p class="okip-ps__desc"><?php echo esc_html($description); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Capa cover (vidrio/blur): título grande; se descubre al hover/tap -->
                    <button
                        type="button"
                        class="okip-ps__cover"
                        data-okip-ps-cover
                        aria-expanded="false"
                        aria-label="<?php echo esc_attr($cover_label); ?>">
                        <span class="okip-ps__cover-title"><?php echo esc_html($cover_label); ?></span>
                    </button>

                </article>
            <?php endforeach; ?>

        </div>
    </div>
</section>
