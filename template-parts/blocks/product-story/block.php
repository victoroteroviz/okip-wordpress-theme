<?php

/**
 * Bloque Product Story (Bloque 4).
 *
 * Ref visual: `bloque 4.png`. Continúa SIN transición tras el Bloque 3
 * (mismo fondo claro). Tres filas de producto, cada una:
 *   - Recuadro visual a la izquierda:
 *       · estado base  → caja negra con logo + título blanco.
 *       · hover/focus  → imagen de producto (o placeholder) + caption.
 *   - Etiqueta gris (pill) debajo del recuadro (RIA / COVIA / GIA).
 *   - Tarjeta gris clara a la derecha: heading + descripción monoespaciados.
 *
 * Animación (desktop, GSAP): un ScrollTrigger con scrub POR FILA. El bloque puede
 * tener un handoff pin final hacia Mission; el root NUNCA recibe transform:
 *   1) recuadro izquierdo entra (left_enter, p.ej. mask-slide),
 *   2) fondo de la tarjeta derecha hace wipe horizontal (copy_bg_enter),
 *   3) heading + descripción se revelan como escritura progresiva (text_reveal).
 * El texto completo se conserva para lectores de pantalla (copia .okip-ps__sr).
 *
 * Sin GSAP (desktop): IO añade `is-revealed` por fila. Móvil/reduce-motion/anim
 * off: `is-static`, todo legible. Los estados iniciales ocultos viven SOLO bajo
 * `.okip-js .okip-ps--animated:not(.is-static)` → sin JS, todo visible.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

$okip_instance = isset($args['instance_id']) ? $args['instance_id'] : 'product-story';
$okip_data     = isset($args['data']) && is_array($args['data']) ? $args['data'] : array();

$content   = isset($okip_data['content'])   ? $okip_data['content']   : array();
$layout    = isset($okip_data['layout'])    ? $okip_data['layout']    : array();
$items     = isset($okip_data['items'])     ? $okip_data['items']     : array();
$animation = isset($okip_data['animation']) ? $okip_data['animation'] : array();
$transition = isset($okip_data['transition']) ? $okip_data['transition'] : array();

// Layout.
$min_height    = isset($layout['min_height'])    ? $layout['min_height']    : 'auto';
$content_width = isset($layout['content_width']) ? $layout['content_width'] : '1100px';
$z_index       = isset($layout['z_index'])       ? (int) $layout['z_index'] : 4;

// Contenido.
$section_label = isset($content['section_label']) ? $content['section_label'] : '';

// Animación.
$anim_on       = ! empty($animation['enabled']);
$use_gsap      = ! empty($animation['use_gsap']);
$use_vanilla   = ! empty($animation['use_vanilla_fallback']);
$disable_below = isset($animation['disable_below']) ? (int) $animation['disable_below'] : 1024;
$scrub         = isset($animation['scrub']) ? (float) $animation['scrub'] : 1;
$left_enter    = isset($animation['left_enter'])    ? $animation['left_enter']    : 'mask-slide';
$copy_bg_enter = isset($animation['copy_bg_enter']) ? $animation['copy_bg_enter'] : 'wipe-left';
$text_reveal   = isset($animation['text_reveal'])   ? $animation['text_reveal']   : 'scroll-typewriter';
$handoff_pin   = ! empty($transition['handoff_pin']);
$handoff_duration_vh = isset($transition['duration_vh']) ? (int) $transition['duration_vh'] : 132;
$handoff_disable_below = isset($transition['disable_below']) ? (int) $transition['disable_below'] : $disable_below;

$section_classes = 'okip-ps';
$section_classes .= $anim_on ? ' okip-ps--animated' : '';

$section_style = sprintf(
    '--okip-ps-minh:%s;--okip-ps-cw:%s;--okip-ps-z:%d;',
    esc_attr($min_height),
    esc_attr($content_width),
    $z_index
);

if (empty($items)) {
    return;
}
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
    data-scrub="<?php echo esc_attr((string) $scrub); ?>"
    data-left-enter="<?php echo esc_attr($left_enter); ?>"
    data-copy-bg-enter="<?php echo esc_attr($copy_bg_enter); ?>"
    data-text-reveal="<?php echo esc_attr($text_reveal); ?>"
    data-handoff-pin="<?php echo $handoff_pin ? '1' : '0'; ?>"
    data-handoff-duration-vh="<?php echo esc_attr((string) $handoff_duration_vh); ?>"
    data-handoff-disable-below="<?php echo esc_attr((string) $handoff_disable_below); ?>"
    style="<?php echo $section_style; ?>">

    <div class="okip-ps__inner">

        <?php if ($section_label !== '') : ?>
            <p class="okip-ps__section-label"><?php echo esc_html($section_label); ?></p>
        <?php endif; ?>

        <?php foreach ($items as $idx => $item) :
            $label       = isset($item['label'])          ? $item['label']          : '';
            $title_left  = isset($item['title_left'])     ? $item['title_left']     : '';
            $heading     = isset($item['heading'])        ? $item['heading']        : '';
            $description = isset($item['description'])     ? $item['description']    : '';
            $variant     = isset($item['visual_variant']) ? $item['visual_variant'] : 'logo-title';
            $media_type  = isset($item['media_type'])     ? $item['media_type']     : 'placeholder';
            $media       = isset($item['media'])          ? $item['media']          : '';
            $alt         = isset($item['alt'])            ? $item['alt']            : $title_left;
            $hover_media = isset($item['hover_media'])    ? $item['hover_media']    : '';
            $hover_alt   = isset($item['hover_alt']) && $item['hover_alt'] !== '' ? $item['hover_alt'] : $title_left;
            $hover_placeholder = ! empty($item['hover_placeholder']);

            $has_media       = $media_type !== 'placeholder' && ! empty($media) && okip_media_exists($media);
            $media_url       = $has_media ? okip_media_url($media) : '';
            $hover_has_media = ! empty($hover_media) && okip_media_exists($hover_media);
            $hover_url       = $hover_has_media ? okip_media_url($hover_media) : '';
            $has_hover       = $hover_has_media || $hover_placeholder;
            $product_key     = sanitize_html_class(sanitize_key($label !== '' ? $label : ('item-' . $idx)));
            $row_classes     = 'okip-ps__row okip-ps__row--' . sanitize_html_class($variant) . ' okip-ps__row--product-' . $product_key;
            $visual_classes  = 'okip-ps__visual okip-ps__visual--logo-title';
            $visual_classes .= $has_hover ? ' okip-ps__visual--has-hover' : '';
            ?>
            <article
                class="<?php echo esc_attr($row_classes); ?>"
                data-okip-ps-row
                data-index="<?php echo esc_attr((string) $idx); ?>"
                data-product="<?php echo esc_attr($product_key); ?>"
                data-variant="<?php echo esc_attr($variant); ?>">

                <!-- Columna izquierda: recuadro visual + etiqueta -->
                <div class="okip-ps__left">
                    <div class="<?php echo esc_attr($visual_classes); ?>"<?php echo $has_hover ? ' tabindex="0"' : ''; ?>>
                        <div class="okip-ps__visual-base">
                            <span class="okip-ps__logo" aria-hidden="true">
                                <?php if ($has_media && $media_type === 'video') : ?>
                                    <video class="okip-ps__logo-video" muted loop playsinline preload="none">
                                        <source src="<?php echo esc_url($media_url); ?>" type="video/mp4">
                                    </video>
                                <?php elseif ($has_media) : /* image, gif, svg */ ?>
                                    <img class="okip-ps__logo-img"
                                        src="<?php echo esc_url($media_url); ?>"
                                        alt=""
                                        loading="<?php echo $media_type === 'gif' ? 'eager' : 'lazy'; ?>">
                                <?php else : ?>
                                    <!-- Placeholder sobrio: marca abstracta de nodos -->
                                    <svg class="okip-ps__logo-ph" viewBox="0 0 64 40" fill="none" aria-hidden="true">
                                        <circle cx="10" cy="20" r="2.4" fill="currentColor"/>
                                        <circle cx="28" cy="9" r="2.4" fill="currentColor"/>
                                        <circle cx="30" cy="31" r="2.4" fill="currentColor"/>
                                        <circle cx="48" cy="15" r="2.4" fill="currentColor"/>
                                        <circle cx="54" cy="29" r="2.4" fill="currentColor"/>
                                        <path d="M10 20 L28 9 L48 15 M28 9 L30 31 L54 29 M48 15 L54 29 M10 20 L30 31"
                                            stroke="currentColor" stroke-width="1" stroke-opacity=".55"/>
                                    </svg>
                                <?php endif; ?>
                            </span>
                            <?php if ($title_left !== '') : ?>
                                <h3 class="okip-ps__title"><?php echo esc_html($title_left); ?></h3>
                            <?php endif; ?>
                        </div>

                        <?php if ($has_hover) : ?>
                            <div class="okip-ps__hover">
                                <span class="okip-ps__hover-frame">
                                    <?php if ($hover_has_media) : ?>
                                        <img class="okip-ps__hover-media"
                                            src="<?php echo esc_url($hover_url); ?>"
                                            alt="<?php echo esc_attr($hover_alt); ?>"
                                            loading="lazy">
                                    <?php else : ?>
                                        <span class="okip-ps__hover-ph" aria-hidden="true">
                                            <span class="okip-ps__hover-ph-label"><?php echo esc_html($label); ?></span>
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($title_left !== '') : ?>
                                    <span class="okip-ps__hover-caption"><?php echo esc_html($title_left); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($label !== '') : ?>
                        <span class="okip-ps__label"><?php echo esc_html($label); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Columna derecha: tarjeta gris con heading + descripción -->
                <div class="okip-ps__copy">
                    <div class="okip-ps__card">
                        <span class="okip-ps__card-bg" aria-hidden="true"></span>
                        <div class="okip-ps__card-inner">
                            <?php if ($heading !== '') : ?>
                                <p class="okip-ps__heading">
                                    <span class="okip-ps__type"><?php echo esc_html($heading); ?></span>
                                </p>
                            <?php endif; ?>
                            <?php if ($description !== '') : ?>
                                <p class="okip-ps__desc">
                                    <span class="okip-ps__type"><?php echo esc_html($description); ?></span>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </article>
        <?php endforeach; ?>

    </div>
</section>
