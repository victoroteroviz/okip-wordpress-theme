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
$intro      = isset($okip_data['intro']) ? $okip_data['intro'] : array();
$loop       = isset($okip_data['loop']) ? $okip_data['loop'] : array();
$overlay    = isset($okip_data['overlay']) ? $okip_data['overlay'] : array();
$reveal     = isset($okip_data['reveal']) ? $okip_data['reveal'] : array();
$transition = isset($okip_data['transition']) ? $okip_data['transition'] : array();
$cards      = isset($okip_data['cards']) && is_array($okip_data['cards']) ? $okip_data['cards'] : array();
$animation  = isset($okip_data['animation']) ? $okip_data['animation'] : array();

$bg_type = isset($background['type']) ? $background['type'] : 'gradient';
$poster  = isset($background['poster']) && okip_media_exists($background['poster']) ? okip_media_url($background['poster']) : '';
$obj_pos = isset($background['object_position']) ? $background['object_position'] : 'center center';

// --- Resolución de medios (intro / loop / fallback / imagen) ---
// Cada uno admite override en su grupo; si no, cae al de background.
$intro_src = ! empty($intro['media']) ? $intro['media'] : (isset($background['intro_media']) ? $background['intro_media'] : '');
$loop_src  = ! empty($loop['media'])  ? $loop['media']  : (isset($background['loop_media'])  ? $background['loop_media']  : '');
// Compat: media único de background → se trata como loop si no hay intro ni loop.
if ($loop_src === '' && $intro_src === '' && $bg_type === 'video' && ! empty($background['media'])) {
    $loop_src = $background['media'];
}

$intro_on = ! empty($intro['enabled']) && $bg_type === 'video' && okip_media_exists($intro_src);
$loop_on  = ! empty($loop['enabled'])  && $bg_type === 'video' && okip_media_exists($loop_src);

$intro_url = $intro_on ? okip_media_url($intro_src) : '';
$loop_url  = $loop_on  ? okip_media_url($loop_src)  : '';

$fallback_src = isset($background['fallback_image']) ? $background['fallback_image'] : '';
$fallback_on  = okip_media_exists($fallback_src);
$fallback_url = $fallback_on ? okip_media_url($fallback_src) : '';

$has_video_layer = ($intro_url !== '' || $loop_url !== '');

// Imagen/SVG estática (cuando el tipo es image|svg) o fallback como única capa.
$img_on  = in_array($bg_type, array('image', 'svg'), true)
    && okip_media_exists(isset($background['media']) ? $background['media'] : '');
$img_url = $img_on ? okip_media_url($background['media']) : '';
$single_img_url = '';
if ($img_on) {
    $single_img_url = $img_url;
} elseif (! $has_video_layer && $fallback_url !== '') {
    $single_img_url = $fallback_url; // sin videos pero con fallback → es el fondo
}

// Render del fondo: video | image | svg | missing (neutro).
if ($has_video_layer) {
    $bg_render = 'video';
} elseif ($single_img_url !== '') {
    $bg_render = ($bg_type === 'svg') ? 'svg' : 'image';
} else {
    $bg_render = 'missing';
}

$align     = isset($content['alignment']) ? $content['alignment'] : 'center';
$max_width = isset($content['max_width']) ? $content['max_width'] : '1000px';

$overlay_on      = ! empty($overlay['enabled']);
$overlay_color   = isset($overlay['color']) ? $overlay['color'] : '#020711';
$overlay_opacity = isset($overlay['opacity']) ? (float) $overlay['opacity'] : 0.35;

$anim_on  = ! empty($animation['enabled']);
$scroll3d = ! empty($animation['scroll_3d']);

$img_delay   = isset($reveal['image_reveal_delay']) ? (int) $reveal['image_reveal_delay'] : 1500;
$cards_d     = isset($reveal['cards_delay_after_intro']) ? (int) $reveal['cards_delay_after_intro'] : 300;
$text_d      = isset($reveal['text_delay_after_intro']) ? (int) $reveal['text_delay_after_intro'] : 600;
$pause_blur  = ! empty($reveal['pause_or_blur_on_fail']);
$intro_fail  = isset($intro['fail_timeout']) ? (int) $intro['fail_timeout'] : 2500;

$crossfade    = ! empty($transition['intro_to_loop_crossfade']);
$crossfade_ms = isset($transition['crossfade_duration']) ? (int) $transition['crossfade_duration'] : 700;

// Fallback de fondo disponible para el crossfade de FALLO (solo en modo video).
$has_fallback_layer = $has_video_layer && $fallback_url !== '';

$overlay_style = sprintf(
    'background-color:%s;opacity:%s;',
    esc_attr(sanitize_hex_color($overlay_color) ? sanitize_hex_color($overlay_color) : '#020711'),
    esc_attr((string) max(0, min(1, $overlay_opacity)))
);

$loop_attrs  = (! empty($loop['muted']) ? ' muted' : '')
    . (! empty($loop['loop']) ? ' loop' : '')
    . (! empty($loop['playsinline']) ? ' playsinline' : '')
    . (! empty($loop['autoplay']) ? ' autoplay' : '');
?>
<section
    id="<?php echo esc_attr($okip_instance); ?>"
    class="okip-hero<?php echo $anim_on ? ' okip-hero--animated' : ''; ?>"
    data-block-instance="<?php echo esc_attr($okip_instance); ?>"
    data-okip-hero
    data-bg-type="<?php echo esc_attr($bg_render); ?>"
    data-anim="<?php echo $anim_on ? '1' : '0'; ?>"
    data-scroll3d="<?php echo $scroll3d ? '1' : '0'; ?>"
    data-has-intro="<?php echo $intro_url !== '' ? '1' : '0'; ?>"
    data-has-loop="<?php echo $loop_url !== '' ? '1' : '0'; ?>"
    data-has-fallback="<?php echo $has_fallback_layer ? '1' : '0'; ?>"
    data-image-delay="<?php echo esc_attr((string) $img_delay); ?>"
    data-cards-delay="<?php echo esc_attr((string) $cards_d); ?>"
    data-text-delay="<?php echo esc_attr((string) $text_d); ?>"
    data-intro-fail="<?php echo esc_attr((string) $intro_fail); ?>"
    data-crossfade="<?php echo $crossfade ? '1' : '0'; ?>"
    data-crossfade-ms="<?php echo esc_attr((string) $crossfade_ms); ?>"
    data-pause-blur="<?php echo $pause_blur ? '1' : '0'; ?>"
    style="--okip-hero-xfade:<?php echo esc_attr((string) $crossfade_ms); ?>ms;">

    <!-- Capa 1: fondo media-driven. Intro (una vez) → crossfade → loop (bucle).
         Sin videos: imagen/svg o fallback neutro (solo color). -->
    <div class="okip-hero__bg okip-hero__bg--<?php echo esc_attr(sanitize_html_class($bg_render)); ?>" data-okip-hero-bg>
        <?php if ($bg_render === 'video') : ?>
            <?php if ($intro_url !== '') : ?>
                <video class="okip-hero__media okip-hero__media--intro" data-okip-hero-intro
                    muted playsinline preload="auto"
                    style="object-position:<?php echo esc_attr($obj_pos); ?>;"
                    <?php echo $poster ? 'poster="' . esc_url($poster) . '"' : ''; ?>>
                    <source src="<?php echo esc_url($intro_url); ?>" type="video/mp4">
                </video>
            <?php endif; ?>
            <?php if ($loop_url !== '') : ?>
                <video class="okip-hero__media okip-hero__media--loop" data-okip-hero-loop
                    <?php echo $loop_attrs; ?> preload="auto"
                    style="object-position:<?php echo esc_attr($obj_pos); ?>;"
                    <?php echo $poster ? 'poster="' . esc_url($poster) . '"' : ''; ?>>
                    <source src="<?php echo esc_url($loop_url); ?>" type="video/mp4">
                </video>
            <?php endif; ?>
            <?php if ($has_fallback_layer) : ?>
                <img class="okip-hero__media okip-hero__media--fallback" src="<?php echo esc_url($fallback_url); ?>"
                    alt="" aria-hidden="true" style="object-position:<?php echo esc_attr($obj_pos); ?>;">
            <?php endif; ?>
        <?php elseif ($bg_render === 'image') : ?>
            <img class="okip-hero__media" src="<?php echo esc_url($single_img_url); ?>" alt="" aria-hidden="true"
                style="object-position:<?php echo esc_attr($obj_pos); ?>;">
        <?php elseif ($bg_render === 'svg') : ?>
            <img class="okip-hero__media okip-hero__media--svg" src="<?php echo esc_url($single_img_url); ?>" alt="" aria-hidden="true">
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
                $c_label = isset($card['placeholder_label']) ? $card['placeholder_label'] : '';
                $c_play  = isset($card['play_mode']) ? $card['play_mode'] : 'hover';
                $c_reset = ! empty($card['reset_on_leave']);

                $card_classes = 'okip-hero__card';
                $card_classes .= $c_glow ? ' okip-hero__card--glow' : '';
                $card_classes .= $c_scan ? ' okip-hero__card--scanline' : '';
                $card_classes .= $c_has ? '' : ' okip-hero__card--placeholder';
            ?>
                <div class="<?php echo esc_attr($card_classes); ?>"
                    data-okip-hero-card
                    data-card-type="<?php echo esc_attr($c_type); ?>"
                    data-has-media="<?php echo $c_has ? '1' : '0'; ?>"
                    data-play-mode="<?php echo esc_attr($c_play); ?>"
                    data-reset-on-leave="<?php echo $c_reset ? '1' : '0'; ?>"
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
