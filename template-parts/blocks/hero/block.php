<?php

/**
 * Bloque Hero.
 *
 * Recibe en $args: ['type', 'instance_id', 'data'] (data ya normalizada).
 * Orden de capas:
 *   1. background CSS editable o media limpio (video | image | svg)
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
$transition = isset($okip_data['transition']) ? $okip_data['transition'] : array();
$autoplay   = isset($okip_data['autoplay']) && is_array($okip_data['autoplay']) ? $okip_data['autoplay'] : array();
$cards      = isset($okip_data['cards']) && is_array($okip_data['cards']) ? $okip_data['cards'] : array();
$motion     = okip_normalize_motion(isset($okip_data['motion']) ? $okip_data['motion'] : array(), array('background', 'text', 'cards'));
$typography = isset($okip_data['typography']) ? $okip_data['typography'] : array();

$logo       = isset($content['logo']) && is_array($content['logo']) ? $content['logo'] : array();
$logo_enabled = ! empty($logo['enabled']);
$logo_media = isset($logo['media']) ? $logo['media'] : '';
$logo_on    = $logo_enabled && okip_media_exists($logo_media);
$logo_url   = $logo_on ? okip_media_url($logo_media) : '';
$logo_alt   = isset($logo['alt']) ? $logo['alt'] : 'Logo';
$logo_width = isset($logo['width']) ? $logo['width'] : '120px';

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

// Render del fondo: css-motion | video | image | svg | missing (neutro).
if ($bg_type === 'css_motion') {
    $bg_render = 'css-motion';
} elseif ($has_video_layer) {
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

$motion_on = ! empty($motion['enabled']);
$intro_fail  = isset($intro['fail_timeout']) ? (int) $intro['fail_timeout'] : 2500;

$crossfade    = ! empty($transition['intro_to_loop_crossfade']);
$crossfade_ms = isset($transition['crossfade_duration']) ? (int) $transition['crossfade_duration'] : 700;
$effective_crossfade_ms = $crossfade ? $crossfade_ms : 0;
$content_entry_delay = isset($transition['content_entry_delay']) ? (int) $transition['content_entry_delay'] : 900;

// Snap del traspaso Hero → bloque siguiente (scroll-jack, solo desktop; ver script.js).
$snap_cover    = ! empty($transition['snap_cover']);
$snap_duration = isset($transition['snap_duration']) ? (int) $transition['snap_duration'] : 700;

// Activación automática de tarjetas (disparos aleatorios sin interacción).
$autoplay_on       = ! empty($autoplay['enabled']);
$autoplay_min      = isset($autoplay['min_delay_ms']) ? (int) $autoplay['min_delay_ms'] : 2500;
$autoplay_max      = isset($autoplay['max_delay_ms']) ? (int) $autoplay['max_delay_ms'] : 6500;
$autoplay_start    = isset($autoplay['start_delay_ms']) ? (int) $autoplay['start_delay_ms'] : 1200;
$autoplay_on_hover = ! empty($autoplay['pause_on_hover']);

// Fallback de fondo disponible para el crossfade de FALLO (solo en modo video).
$has_fallback_layer = $has_video_layer && $fallback_url !== '';

$title_typography = okip_normalize_typography(
    isset($typography['title']) ? $typography['title'] : array(),
    'hero_title'
);
$desc_typography = okip_normalize_typography(
    isset($typography['description']) ? $typography['description'] : array(),
    'hero_description'
);

$css_motion_speed = isset($background['css_motion_speed']) ? max(0.2, min(3, (float) $background['css_motion_speed'])) : 1;
$css_duration = function ($seconds) use ($css_motion_speed) {
    return okip_css_number(((float) $seconds) / $css_motion_speed) . 's';
};
$css_motion_interval = isset($background['css_motion_interval']) ? max(2, min(20, (float) $background['css_motion_interval'])) : 8;
$css_motion_enabled = ! empty($background['css_motion_enabled']);
$css_variant_allowed = array('future_grid', 'liquid_aurora', 'signal_field');
$css_variant = okip_one_of(isset($background['css_variant']) ? $background['css_variant'] : 'liquid_aurora', $css_variant_allowed, 'liquid_aurora');
$css_variant_class = ' okip-hero__css-bg--' . sanitize_html_class(str_replace('_', '-', $css_variant));
$css_motion_class = $css_variant_class . ($css_motion_enabled ? ' is-motion-enabled' : '');

// z-index raíz por ORDEN de render (el Hero conserva su sticky CSS; solo el z es dinámico).
$hero_style = '--okip-hero-z:' . (int) ((isset($args['order']) ? (int) $args['order'] : 0) + 1) . ';';
$hero_style .= '--okip-hero-xfade:' . esc_attr((string) $effective_crossfade_ms) . 'ms;';
$hero_style .= okip_typography_css_vars('okip-hero-title', $title_typography);
$hero_style .= okip_typography_css_vars('okip-hero-desc', $desc_typography);
$hero_style .= okip_css_vars(array(
    'okip-hero-css-bg'                 => isset($background['css_bg']) ? $background['css_bg'] : '#020711',
    'okip-hero-css-accent'             => isset($background['css_accent']) ? $background['css_accent'] : '#00a9ff',
    'okip-hero-css-accent-2'           => isset($background['css_accent_2']) ? $background['css_accent_2'] : '#6ee7ff',
    'okip-hero-css-grid-opacity'       => isset($background['css_grid_opacity']) ? okip_css_number($background['css_grid_opacity']) : '0.24',
    'okip-hero-css-scanline-opacity'   => isset($background['css_scanline_opacity']) ? okip_css_number($background['css_scanline_opacity']) : '0.16',
    'okip-hero-css-noise-opacity'      => isset($background['css_noise_opacity']) ? okip_css_number($background['css_noise_opacity']) : '0.1',
    'okip-hero-css-motion-intensity'   => isset($background['css_motion_intensity']) ? okip_css_number($background['css_motion_intensity']) : '0.34',
    'okip-hero-css-motion-alpha'       => $css_motion_enabled ? '1' : '0',
    'okip-hero-css-signal-duration'    => $css_duration(28),
    'okip-hero-css-noise-duration'     => $css_duration(1.65),
    'okip-hero-css-liquid-duration'    => $css_duration(18),
    'okip-hero-css-field-duration'     => $css_duration(34),
    'okip-hero-css-chroma-duration'    => okip_css_number($css_motion_interval / $css_motion_speed) . 's',
    'okip-hero-css-chroma-offset'      => isset($background['css_chroma_offset']) ? okip_css_number($background['css_chroma_offset']) . 'px' : '8px',
));

$motion_json = okip_motion_config_json($motion, array(
    'background' => '[data-okip-motion-target="background"]',
    'text'       => '[data-okip-motion-target="text"]',
    'cards'      => '[data-okip-motion-target="cards"]',
));

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
    class="okip-hero<?php echo $motion_on ? ' okip-hero--animated is-hero-entering' : ''; ?>"
    data-block-instance="<?php echo esc_attr($okip_instance); ?>"
    data-okip-hero
    data-bg-type="<?php echo esc_attr($bg_render); ?>"
    data-motion-enabled="<?php echo $motion_on ? '1' : '0'; ?>"
    data-has-intro="<?php echo $intro_url !== '' ? '1' : '0'; ?>"
    data-has-loop="<?php echo $loop_url !== '' ? '1' : '0'; ?>"
    data-has-fallback="<?php echo $has_fallback_layer ? '1' : '0'; ?>"
    data-intro-fail="<?php echo esc_attr((string) $intro_fail); ?>"
    data-crossfade="<?php echo $crossfade ? '1' : '0'; ?>"
    data-crossfade-ms="<?php echo esc_attr((string) $crossfade_ms); ?>"
    data-content-entry-delay="<?php echo esc_attr((string) max(0, $content_entry_delay)); ?>"
    data-cards-autoplay="<?php echo $autoplay_on ? '1' : '0'; ?>"
    data-cards-autoplay-min="<?php echo esc_attr((string) max(0, $autoplay_min)); ?>"
    data-cards-autoplay-max="<?php echo esc_attr((string) max(0, $autoplay_max)); ?>"
    data-cards-autoplay-start="<?php echo esc_attr((string) max(0, $autoplay_start)); ?>"
    data-cards-autoplay-hover="<?php echo $autoplay_on_hover ? '1' : '0'; ?>"
    data-snap-cover="<?php echo $snap_cover ? '1' : '0'; ?>"
    data-snap-duration="<?php echo esc_attr((string) max(0, $snap_duration)); ?>"
    style="<?php echo $hero_style; ?>">
    <script type="application/json" data-okip-motion-config><?php echo $motion_json; ?></script>

    <!-- Capa 1: fondo CSS o media-driven. Intro (una vez) → crossfade → loop (bucle).
         Sin CSS/media: fallback neutro (solo color).
         En modo video el target de MOTION va en este wrapper (drift/reveal de toda la
         capa); el CROSSFADE vive SOLO en los nodos intro/loop/fallback (opacidad por
         clase). Nunca el mismo nodo para ambas cosas → el motion no pisa el crossfade. -->
    <div class="okip-hero__bg okip-hero__bg--<?php echo esc_attr(sanitize_html_class($bg_render)); ?>" data-okip-hero-bg<?php echo $bg_render === 'video' ? ' data-okip-motion-target="background"' : ''; ?>>
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
        <?php elseif ($bg_render === 'css-motion') : ?>
            <div class="okip-hero__css-bg<?php echo esc_attr($css_motion_class); ?>" data-css-variant="<?php echo esc_attr($css_variant); ?>" data-okip-motion-target="background" aria-hidden="true">
                <span class="okip-hero__css-grid"></span>
                <span class="okip-hero__css-signal"></span>
                <span class="okip-hero__css-noise"></span>
            </div>
        <?php elseif ($bg_render === 'image') : ?>
            <img class="okip-hero__media" data-okip-motion-target="background" src="<?php echo esc_url($single_img_url); ?>" alt="" aria-hidden="true"
                style="object-position:<?php echo esc_attr($obj_pos); ?>;">
        <?php elseif ($bg_render === 'svg') : ?>
            <img class="okip-hero__media okip-hero__media--svg" data-okip-motion-target="background" src="<?php echo esc_url($single_img_url); ?>" alt="" aria-hidden="true">
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
        if (! in_array($c_type, array('video', 'image', 'svg', 'gif'), true)) {
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
        <div class="okip-hero__cards" data-okip-hero-cards>
            <?php foreach ($okip_valid_cards as $card) :
                $c_id    = isset($card['id']) ? $card['id'] : '';
                $c_has   = ! empty($card['__has_media']);
                $c_type  = $card['type'];
                $c_url   = $c_has ? okip_media_url($card['media']) : '';
                $c_post  = isset($card['poster']) ? okip_media_url($card['poster']) : '';
                $c_alt   = isset($card['alt']) ? $card['alt'] : '';
                $c_x     = isset($card['x']) ? (float) $card['x'] : 50;
                $c_y     = isset($card['y']) ? (float) $card['y'] : 50;
                $c_w     = isset($card['width_pct']) ? (float) $card['width_pct'] : 14;
                $c_glow  = ! empty($card['glow']);
                $c_scan  = ! empty($card['scanline']);
                $c_label = isset($card['placeholder_label']) ? $card['placeholder_label'] : '';
                $c_play  = isset($card['play_mode']) ? $card['play_mode'] : 'hover';
                $c_duration = isset($card['play_duration_ms']) ? (int) $card['play_duration_ms'] : 0;
                $c_reset = ! empty($card['reset_on_leave']);
                $c_is_gif = ($c_type === 'gif');
                $c_is_gif_interactive = ($c_is_gif && $c_has && $c_url !== '');

                $card_classes = 'okip-hero__card';
                $card_classes .= $c_glow ? ' okip-hero__card--glow' : '';
                $card_classes .= $c_scan ? ' okip-hero__card--scanline' : '';
                $card_classes .= $c_is_gif ? ' okip-hero__card--gif' : '';
                $card_classes .= $c_has ? '' : ' okip-hero__card--placeholder';
            ?>
                <div class="<?php echo esc_attr($card_classes); ?>"
                    data-okip-hero-card
                    data-card-id="<?php echo esc_attr($c_id); ?>"
                    data-card-type="<?php echo esc_attr($c_type); ?>"
                    data-has-media="<?php echo $c_has ? '1' : '0'; ?>"
                    data-play-mode="<?php echo esc_attr($c_play); ?>"
                    data-play-duration-ms="<?php echo esc_attr((string) max(0, $c_duration)); ?>"
                    data-reset-on-leave="<?php echo $c_reset ? '1' : '0'; ?>"
                    <?php echo $c_is_gif_interactive ? 'data-gif-src="' . esc_url($c_url) . '"' : ''; ?>
                    style="--okip-card-x:<?php echo esc_attr((string) $c_x); ?>%;--okip-card-y:<?php echo esc_attr((string) $c_y); ?>%;--okip-card-w:<?php echo esc_attr((string) $c_w); ?>vw;">
                    <!-- Wrapper de MOTION (entry/playback/exit): nodo separado del media.
                         El transform del runtime vive aquí; el hover/glow/scanline en
                         .okip-hero__card-media (nodos distintos → sin conflicto). -->
                    <div class="okip-hero__card-motion" data-okip-motion-target="cards">
                        <div class="okip-hero__card-media">
                            <?php if ($c_has && $c_type === 'video') : ?>
                                <video class="okip-hero__card-video" muted loop playsinline preload="none"
                                    <?php echo $c_post ? 'poster="' . esc_url($c_post) . '"' : ''; ?>>
                                    <source src="<?php echo esc_url($c_url); ?>" type="video/mp4">
                                </video>
                            <?php elseif ($c_is_gif_interactive) : ?>
                                <img class="okip-hero__card-gif" data-gif-src="<?php echo esc_url($c_url); ?>" alt="" aria-hidden="true" decoding="async">
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
                <span class="okip-hero__title-line okip-hero__title-line--primary" data-okip-motion-target="text"><?php echo esc_html($content['title_line_1']); ?></span>
            <?php endif; ?>
            <?php if (! empty($content['title_line_2'])) : ?>
                <span class="okip-hero__title-line okip-hero__title-line--secondary" data-okip-motion-target="text"><?php echo esc_html($content['title_line_2']); ?></span>
            <?php endif; ?>
        </h1>
        <?php if ($logo_on) : ?>
            <div class="okip-hero__logo" style="--okip-logo-width:<?php echo esc_attr($logo_width); ?>;" data-okip-motion-target="text">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($logo_alt); ?>" class="okip-hero__logo-img">
            </div>
        <?php endif; ?>
        <?php if (! empty($content['description'])) : ?>
            <div class="okip-hero__desc" data-okip-motion-target="text"><?php echo wp_kses_post($content['description']); ?></div>
        <?php endif; ?>
    </div>

</section>
