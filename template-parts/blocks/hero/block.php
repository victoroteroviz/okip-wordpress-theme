<?php

/**
 * Bloque Hero.
 *
 * Recibe en $args: ['type', 'instance_id', 'data'] (data ya normalizada).
 * Orden de capas (regla del fondo limpio):
 *   1. background media limpio (video | image | svg); gradiente solo de fallback
 *   2. overlay configurable opcional (capa separada y ligera)
 *   3. tarjetas flotantes
 *   4. texto central
 *
 * La lógica de secuencia/reentrada vive en script.js (este template solo expone
 * la config como data-attributes). Scope por instancia: id + data-block-instance.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

$okip_instance = isset($args['instance_id']) ? $args['instance_id'] : 'hero';
$okip_data     = isset($args['data']) && is_array($args['data']) ? $args['data'] : array();

$content    = isset($okip_data['content']) ? $okip_data['content'] : array();
$background = isset($okip_data['background']) ? $okip_data['background'] : array();
$overlay    = isset($okip_data['overlay']) ? $okip_data['overlay'] : array();
$reveal     = isset($okip_data['reveal']) ? $okip_data['reveal'] : array();
$cards      = isset($okip_data['cards']) && is_array($okip_data['cards']) ? $okip_data['cards'] : array();
$animation  = isset($okip_data['animation']) ? $okip_data['animation'] : array();

$bg_type   = isset($background['type']) ? $background['type'] : 'gradient';
$bg_url    = isset($background['media']) ? okip_media_url($background['media']) : '';
$poster    = isset($background['poster']) ? okip_media_url($background['poster']) : '';
$obj_pos   = isset($background['object_position']) ? $background['object_position'] : 'center center';

// Media-driven: solo es fondo real si el tipo es media Y el archivo existe.
// Si no, fallback neutro ('missing'): color sólido oscuro, sin diseño falso.
$has_media = in_array($bg_type, array('video', 'image', 'svg'), true)
    && okip_media_exists(isset($background['media']) ? $background['media'] : '');
$bg_render = $has_media ? $bg_type : 'missing';

$align     = isset($content['alignment']) ? $content['alignment'] : 'center';
$max_width = isset($content['max_width']) ? $content['max_width'] : '1000px';

$overlay_on      = ! empty($overlay['enabled']);
$overlay_color   = isset($overlay['color']) ? $overlay['color'] : '#020711';
$overlay_opacity = isset($overlay['opacity']) ? (float) $overlay['opacity'] : 0.35;

$anim_on  = ! empty($animation['enabled']);
$scroll3d = ! empty($animation['scroll_3d']);

$strategy   = isset($reveal['strategy']) ? $reveal['strategy'] : 'video_end';
$img_delay  = isset($reveal['image_reveal_delay']) ? (int) $reveal['image_reveal_delay'] : 1500;
$fail_to    = isset($reveal['video_fail_timeout']) ? (int) $reveal['video_fail_timeout'] : 2000;
$cards_d    = isset($reveal['cards_reveal_delay']) ? (int) $reveal['cards_reveal_delay'] : 200;
$text_d     = isset($reveal['text_reveal_delay']) ? (int) $reveal['text_reveal_delay'] : 200;
$replay     = ! empty($reveal['replay_on_enter']);
$pause_blur = ! empty($reveal['pause_or_blur_on_fail']);

$overlay_style = sprintf(
    'background-color:%s;opacity:%s;',
    esc_attr(sanitize_hex_color($overlay_color) ? sanitize_hex_color($overlay_color) : '#020711'),
    esc_attr((string) max(0, min(1, $overlay_opacity)))
);
?>
<section
    id="<?php echo esc_attr($okip_instance); ?>"
    class="okip-hero<?php echo $anim_on ? ' okip-hero--animated' : ''; ?>"
    data-block-instance="<?php echo esc_attr($okip_instance); ?>"
    data-okip-hero
    data-bg-type="<?php echo esc_attr($bg_render); ?>"
    data-anim="<?php echo $anim_on ? '1' : '0'; ?>"
    data-scroll3d="<?php echo $scroll3d ? '1' : '0'; ?>"
    data-reveal-strategy="<?php echo esc_attr($strategy); ?>"
    data-image-delay="<?php echo esc_attr((string) $img_delay); ?>"
    data-video-fail-timeout="<?php echo esc_attr((string) $fail_to); ?>"
    data-cards-delay="<?php echo esc_attr((string) $cards_d); ?>"
    data-text-delay="<?php echo esc_attr((string) $text_d); ?>"
    data-replay="<?php echo $replay ? '1' : '0'; ?>"
    data-pause-blur="<?php echo $pause_blur ? '1' : '0'; ?>">

    <!-- Capa 1: fondo media-driven. Sin media real → fallback neutro (solo color). -->
    <div class="okip-hero__bg okip-hero__bg--<?php echo esc_attr(sanitize_html_class($bg_render)); ?>" data-okip-hero-bg>
        <?php if ($bg_render === 'video') : ?>
            <video class="okip-hero__media" muted autoplay playsinline preload="auto"
                style="object-position:<?php echo esc_attr($obj_pos); ?>;"
                <?php echo $poster ? 'poster="' . esc_url($poster) . '"' : ''; ?>>
                <source src="<?php echo esc_url($bg_url); ?>" type="video/mp4">
            </video>
        <?php elseif ($bg_render === 'image') : ?>
            <img class="okip-hero__media" src="<?php echo esc_url($bg_url); ?>" alt="" aria-hidden="true"
                style="object-position:<?php echo esc_attr($obj_pos); ?>;">
        <?php elseif ($bg_render === 'svg') : ?>
            <img class="okip-hero__media okip-hero__media--svg" src="<?php echo esc_url($bg_url); ?>" alt="" aria-hidden="true">
        <?php else : ?>
            <!-- bg-missing: sin media real configurado/encontrado. Fallback neutro por CSS. -->
        <?php endif; ?>
    </div>

    <!-- Capa 2: overlay (separado y opcional) -->
    <?php if ($overlay_on) : ?>
        <div class="okip-hero__overlay" style="<?php echo $overlay_style; ?>" aria-hidden="true"></div>
    <?php endif; ?>

    <!-- Capa 3: tarjetas multimedia flotantes.
         Se renderizan desde config aunque aún no exista media real: en ese caso
         la tarjeta muestra un PLACEHOLDER temporal claro (no un media final falso).
         Cuando se configure media real existente, sustituye al placeholder. -->
    <?php
    // Pre-filtrado: active + tipo válido. Sin media → placeholder (si está habilitado).
    $okip_valid_cards = array();
    foreach ($cards as $card) {
        if (empty($card['active'])) {
            continue;
        }
        $c_type = isset($card['type']) ? $card['type'] : '';
        if (! in_array($c_type, array('video', 'image', 'svg'), true)) {
            continue;
        }
        $card['__has_media'] = okip_media_exists(isset($card['media']) ? $card['media'] : '');
        // Sin media real y placeholder deshabilitado → no se pinta la tarjeta.
        if (! $card['__has_media'] && empty($card['placeholder_enabled'])) {
            continue;
        }
        $okip_valid_cards[] = $card;
    }
    ?>
    <?php if (! empty($okip_valid_cards)) : ?>
        <div class="okip-hero__cards" data-okip-hero-cards aria-hidden="true">
            <?php foreach ($okip_valid_cards as $card) :
                $c_has   = ! empty($card['__has_media']);
                $c_type  = $card['type'];
                $c_url   = $c_has ? okip_media_url($card['media']) : '';
                $c_post  = isset($card['poster']) ? okip_media_url($card['poster']) : '';
                $c_alt   = isset($card['alt']) ? $card['alt'] : '';
                $c_x     = isset($card['x']) ? (float) $card['x'] : 50;
                $c_y     = isset($card['y']) ? (float) $card['y'] : 50;
                $c_glow  = ! empty($card['glow']);
                $c_scan  = ! empty($card['scanline']);
                $c_hover = ! empty($card['autoplay_on_hover']);
                $c_tap   = ! empty($card['play_on_tap']);
                $c_label = isset($card['placeholder_label']) ? $card['placeholder_label'] : '';

                $card_classes = 'okip-hero__card';
                $card_classes .= $c_glow ? ' okip-hero__card--glow' : '';
                $card_classes .= $c_scan ? ' okip-hero__card--scanline' : '';
                $card_classes .= $c_has ? '' : ' okip-hero__card--placeholder';
            ?>
                <div class="<?php echo esc_attr($card_classes); ?>"
                    data-okip-hero-card
                    data-card-type="<?php echo esc_attr($c_type); ?>"
                    data-has-media="<?php echo $c_has ? '1' : '0'; ?>"
                    data-hover="<?php echo $c_hover ? '1' : '0'; ?>"
                    data-tap="<?php echo $c_tap ? '1' : '0'; ?>"
                    style="--okip-card-x:<?php echo esc_attr((string) $c_x); ?>%;--okip-card-y:<?php echo esc_attr((string) $c_y); ?>%;">
                    <div class="okip-hero__card-media">
                        <?php if ($c_has && $c_type === 'video') : ?>
                            <video class="okip-hero__card-video" muted loop playsinline preload="none"
                                <?php echo $c_post ? 'poster="' . esc_url($c_post) . '"' : ''; ?>>
                                <source src="<?php echo esc_url($c_url); ?>" type="video/mp4">
                            </video>
                        <?php elseif ($c_has) : ?>
                            <img src="<?php echo esc_url($c_url); ?>" alt="<?php echo esc_attr($c_alt); ?>">
                        <?php else : ?>
                            <!-- Placeholder temporal (sin media real): marca dónde irá la tarjeta. -->
                            <span class="okip-hero__card-ph" aria-hidden="true">
                                <span class="okip-hero__card-ph-icon"></span>
                                <?php if ($c_label !== '') : ?>
                                    <span class="okip-hero__card-ph-label"><?php echo esc_html($c_label); ?></span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($c_scan) : ?><span class="okip-hero__card-scan" aria-hidden="true"></span><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Capa 4: contenido central -->
    <div class="okip-hero__content okip-hero__content--<?php echo esc_attr(sanitize_html_class($align)); ?>"
        data-okip-hero-content
        style="--okip-hero-maxw:<?php echo esc_attr($max_width); ?>;">
        <h1 class="okip-hero__title">
            <?php if (! empty($content['title_line_1'])) : ?>
                <span class="okip-hero__title-line"><?php echo esc_html($content['title_line_1']); ?></span>
            <?php endif; ?>
            <?php if (! empty($content['title_line_2'])) : ?>
                <span class="okip-hero__title-line"><?php echo esc_html($content['title_line_2']); ?></span>
            <?php endif; ?>
        </h1>
        <?php if (! empty($content['description'])) : ?>
            <div class="okip-hero__desc"><?php echo wp_kses_post($content['description']); ?></div>
        <?php endif; ?>
    </div>

</section>
