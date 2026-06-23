<?php

/**
 * Bloque Product Story (Bloque 4).
 *
 * Ref visual: `bloque 4.png`. Continúa SIN transición tras el Bloque 3
 * (mismo fondo claro). Tres filas de producto, cada una:
 *   - Recuadro visual a la izquierda:
 *       · logo-title   → caja negra con logo/placeholder arriba + título blanco.
 *       · media-caption→ media (o placeholder) con barra de caption inferior.
 *   - Etiqueta gris (pill) debajo del recuadro (RIA / COVIA / GIA).
 *   - Tarjeta gris clara a la derecha: heading + descripción monoespaciados.
 *
 * Animación (desktop, GSAP): un ScrollTrigger con scrub POR FILA. No se pinea el
 * bloque; el root NUNCA recibe transform (se animan nodos internos):
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

            $has_media = $media_type !== 'placeholder' && ! empty($media) && okip_media_exists($media);
            $media_url = $has_media ? okip_media_url($media) : '';
            ?>
            <article
                class="okip-ps__row okip-ps__row--<?php echo esc_attr($variant); ?>"
                data-okip-ps-row
                data-index="<?php echo esc_attr((string) $idx); ?>"
                data-variant="<?php echo esc_attr($variant); ?>">

                <!-- Columna izquierda: recuadro visual + etiqueta -->
                <div class="okip-ps__left">
                    <div class="okip-ps__visual okip-ps__visual--<?php echo esc_attr($variant); ?>">
                        <?php if ($variant === 'media-caption') : ?>
                            <?php if ($has_media && $media_type === 'video') : ?>
                                <video class="okip-ps__media" muted loop playsinline preload="none"
                                    aria-label="<?php echo esc_attr($alt); ?>">
                                    <source src="<?php echo esc_url($media_url); ?>" type="video/mp4">
                                </video>
                            <?php elseif ($has_media) : ?>
                                <img class="okip-ps__media"
                                    src="<?php echo esc_url($media_url); ?>"
                                    alt="<?php echo esc_attr($alt); ?>"
                                    loading="lazy">
                            <?php else : ?>
                                <span class="okip-ps__media-ph" aria-hidden="true"></span>
                            <?php endif; ?>
                            <?php if ($title_left !== '') : ?>
                                <span class="okip-ps__caption"><?php echo esc_html($title_left); ?></span>
                            <?php endif; ?>
                        <?php else : /* logo-title */ ?>
                            <span class="okip-ps__logo" aria-hidden="true">
                                <?php if ($has_media && in_array($media_type, array('image', 'svg'), true)) : ?>
                                    <img class="okip-ps__logo-img"
                                        src="<?php echo esc_url($media_url); ?>"
                                        alt=""
                                        loading="lazy">
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
