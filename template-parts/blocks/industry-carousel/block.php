<?php

/**
 * Bloque Industry Carousel (Bloque 3).
 *
 * Ref visual: `bloque 3.png`.
 *
 * Layout (top → bottom):
 *   1. Texto centrado: heading_main + heading_sub + naranja dinámico + CTA
 *   2. Cinta de imágenes full-width: activa a color/escala mayor, inactivas en grises
 *
 * Con GSAP + ScrollTrigger (desktop >disable_below px):
 *   - UN SOLO ScrollTrigger pin+scrub.
 *   - Track empieza con ítem 0 centrado; termina con ítem N-1 centrado.
 *   - Índice activo = Math.round(progress × (N-1)).
 *
 * Sin GSAP / móvil: is-static, scroll horizontal nativo, IO para activo.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

$okip_instance = isset($args['instance_id']) ? $args['instance_id'] : 'industry-carousel';
$okip_data     = isset($args['data']) && is_array($args['data']) ? $args['data'] : array();

$content   = isset($okip_data['content'])   ? $okip_data['content']   : array();
$layout    = isset($okip_data['layout'])    ? $okip_data['layout']    : array();
$cta_cfg    = isset($okip_data['cta'])        ? $okip_data['cta']        : array();
$items      = isset($okip_data['items'])      ? $okip_data['items']      : array();
$animation  = isset($okip_data['animation'])  ? $okip_data['animation']  : array();
$transition = isset($okip_data['transition']) ? $okip_data['transition'] : array();

// Layout.
$min_height = isset($layout['min_height']) ? $layout['min_height'] : '100svh';
// z-index raíz por ORDEN de render; layout.z_index>0 = override avanzado (retrocompat).
$z_index    = (isset($layout['z_index']) && (int) $layout['z_index'] > 0)
    ? (int) $layout['z_index']
    : ((isset($args['order']) ? (int) $args['order'] : 0) + 1);

// Animación.
$anim_on       = ! empty($animation['enabled']);
$pin_on        = ! empty($animation['pin_enabled']);
$disable_below = isset($animation['disable_below']) ? (int) $animation['disable_below'] : 1024;
$scrub         = isset($animation['scrub']) ? (float) $animation['scrub'] : 1;

// Contenido del bloque.
$eyebrow      = isset($content['eyebrow'])      ? $content['eyebrow']      : '';
$heading_main = isset($content['heading_main']) ? $content['heading_main'] : '';
$heading_sub  = isset($content['heading_sub'])  ? $content['heading_sub']  : '';

// CTA.
$cta_on    = ! empty($cta_cfg['enabled']) && ! empty($cta_cfg['label']) && ! empty($cta_cfg['url']);
$cta_label = isset($cta_cfg['label']) ? $cta_cfg['label'] : 'Saber más';
$cta_url   = isset($cta_cfg['url'])   ? $cta_cfg['url']   : '';

// Texto naranja del primer ítem (el activo inicial).
$first_orange = (! empty($items) && isset($items[0]['orange_text'])) ? $items[0]['orange_text'] : '';

$item_count = count($items);

$section_classes = 'okip-ic';
$section_classes .= $anim_on ? ' okip-ic--animated' : '';

$section_style = sprintf(
    '--okip-ic-minh:%s;--okip-ic-z:%d;',
    esc_attr($min_height),
    $z_index
);
?>
<section
    id="<?php echo esc_attr($okip_instance); ?>"
    class="<?php echo esc_attr($section_classes); ?>"
    data-block-instance="<?php echo esc_attr($okip_instance); ?>"
    data-okip-ic
    data-anim="<?php echo $anim_on ? '1' : '0'; ?>"
    data-pin="<?php echo $pin_on ? '1' : '0'; ?>"
    data-disable-below="<?php echo esc_attr((string) $disable_below); ?>"
    data-scrub="<?php echo esc_attr((string) $scrub); ?>"
    <?php echo okip_transition_attrs($transition); ?>
    style="<?php echo $section_style; ?>">

    <!-- Bloque de texto centrado (fijo durante el pin) -->
    <div class="okip-ic__content">
        <?php if ($eyebrow !== '') : ?>
            <p class="okip-ic__eyebrow"><?php echo esc_html($eyebrow); ?></p>
        <?php endif; ?>

        <?php if ($heading_main !== '') : ?>
            <h2 class="okip-ic__heading-main"><?php echo esc_html($heading_main); ?></h2>
        <?php endif; ?>

        <?php if ($heading_sub !== '') : ?>
            <p class="okip-ic__heading-sub"><?php echo esc_html($heading_sub); ?></p>
        <?php endif; ?>

        <!-- Texto naranja: todos pre-renderizados, JS alterna is-active para evitar layout shift -->
        <p class="okip-ic__orange-line" aria-live="polite">
            <span class="okip-ic__orange-wrap" aria-hidden="true">
                <?php foreach ($items as $idx => $item) : ?>
                    <?php
                    $title_color = ! empty($item['title_color']) ? sanitize_hex_color((string) $item['title_color']) : '';
                    $title_color = $title_color ?: '';
                    ?>
                    <span
                        class="okip-ic__orange-text<?php echo $idx === 0 ? ' is-active' : ''; ?>"
                        data-index="<?php echo esc_attr((string) $idx); ?>"
                        <?php if ($title_color !== '') : ?>
                            style="<?php echo esc_attr('--okip-ic-title-color:' . $title_color . ';'); ?>"
                        <?php endif; ?>>
                        <?php echo esc_html($item['orange_text']); ?>
                    </span>
                <?php endforeach; ?>
            </span>
            <!-- Texto visible para lectores de pantalla -->
            <span class="okip-ic__orange-sr okip-sr-only"><?php echo esc_html($first_orange); ?></span>
        </p>

        <?php if ($cta_on) : ?>
            <a class="okip-ic__cta" href="<?php echo esc_url($cta_url); ?>">
                <?php echo esc_html($cta_label); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Cinta de imágenes full-width.
         El JS mueve .okip-ic__track con transform inline (parallax horizontal). -->
    <div class="okip-ic__strip" aria-label="<?php echo esc_attr($eyebrow !== '' ? $eyebrow : 'Carrusel de industrias'); ?>">
        <ul class="okip-ic__track" role="list">
            <?php foreach ($items as $idx => $item) : ?>
                <?php
                $img_exists = ! empty($item['image']) && okip_media_exists($item['image']);
                $img_url    = $img_exists ? okip_media_url($item['image']) : '';
                $vid_exists = ! empty($item['video']) && okip_media_exists($item['video']);
                $vid_url    = $vid_exists ? okip_media_url($item['video']) : '';
                ?>
                <li class="okip-ic__item<?php echo $idx === 0 ? ' is-active' : ''; ?>"
                    data-index="<?php echo esc_attr((string) $idx); ?>"
                    aria-label="<?php echo esc_attr($item['title']); ?>">
                    <?php if ($vid_exists) : ?>
                        <video class="okip-ic__item-media" muted loop playsinline preload="none">
                            <source src="<?php echo esc_url($vid_url); ?>" type="video/mp4">
                        </video>
                    <?php elseif ($img_exists) : ?>
                        <img class="okip-ic__item-media"
                            src="<?php echo esc_url($img_url); ?>"
                            alt="<?php echo esc_attr($item['alt']); ?>"
                            loading="lazy">
                    <?php else : ?>
                        <!-- Placeholder cuando no hay media real -->
                        <span class="okip-ic__item-ph" aria-hidden="true">
                            <span class="okip-ic__item-ph-label"><?php echo esc_html($item['title']); ?></span>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Indicadores de progreso -->
    <?php if ($item_count > 1) : ?>
        <div class="okip-ic__dots" role="tablist" aria-label="Industrias">
            <?php foreach ($items as $idx => $item) : ?>
                <button
                    class="okip-ic__dot<?php echo $idx === 0 ? ' is-active' : ''; ?>"
                    role="tab"
                    aria-selected="<?php echo $idx === 0 ? 'true' : 'false'; ?>"
                    aria-label="<?php echo esc_attr($item['title']); ?>"
                    data-index="<?php echo esc_attr((string) $idx); ?>"
                    tabindex="<?php echo $idx === 0 ? '0' : '-1'; ?>">
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>
