<?php

/**
 * Bloque Parallax Monitor (Bloque 2).
 *
 * Escena oscura full-screen que se SUPERPONE al bloque anterior (Hero) durante el
 * scroll. Capas con parallax real a velocidades distintas:
 *   1) fondo (media-driven, fallback neutro)  — lento
 *   2) overlay opcional
 *   3) glow azul detrás del monitor (iluminación, no fondo)
 *   4) monitor/pantalla (media real o marco/placeholder geométrico) — rápido
 *   5) texto (izquierda) — muy ligero
 *
 * Toda la lógica de scroll/parallax/overlap vive en script.js. Cada capa expone
 * data-speed; la sección expone la config como data-* y variables CSS de layout.
 * Scope por instancia con id + data-block-instance.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

$okip_instance = isset($args['instance_id']) ? $args['instance_id'] : 'parallax-monitor';
$okip_data     = isset($args['data']) && is_array($args['data']) ? $args['data'] : array();

$content    = isset($okip_data['content']) ? $okip_data['content'] : array();
$layout     = isset($okip_data['layout']) ? $okip_data['layout'] : array();
$background  = isset($okip_data['background']) ? $okip_data['background'] : array();
$monitor    = isset($okip_data['monitor']) ? $okip_data['monitor'] : array();
$cta        = isset($okip_data['cta']) ? $okip_data['cta'] : array();
$overlay    = isset($okip_data['overlay']) ? $okip_data['overlay'] : array();
$glow       = isset($okip_data['glow']) ? $okip_data['glow'] : array();
$animation  = isset($okip_data['animation']) ? $okip_data['animation'] : array();

// --- Layout / escena ---
$min_height    = isset($layout['min_height']) ? $layout['min_height'] : '100svh';
$content_width = isset($layout['content_width']) ? $layout['content_width'] : '1200px';
$overlap_on    = ! empty($layout['overlap_previous']);
$overlap_amt   = isset($layout['overlap_amount']) ? $layout['overlap_amount'] : '18vh';
$z_index       = isset($layout['z_index']) ? (int) $layout['z_index'] : 2;

// --- Fondo (media-driven) ---
$bg_type   = isset($background['type']) ? $background['type'] : 'image';
$bg_has    = in_array($bg_type, array('image', 'video', 'svg'), true)
    && okip_media_exists(isset($background['media']) ? $background['media'] : '');
$bg_url    = $bg_has ? okip_media_url($background['media']) : '';
$bg_poster = isset($background['poster']) && okip_media_exists($background['poster']) ? okip_media_url($background['poster']) : '';
$bg_render = $bg_has ? $bg_type : 'missing';

// --- Monitor ---
$mon_frame     = okip_media_exists(isset($monitor['image']) ? $monitor['image'] : '');
$mon_frame_url = $mon_frame ? okip_media_url($monitor['image']) : '';
$scr_video     = okip_media_exists(isset($monitor['screen_video']) ? $monitor['screen_video'] : '');
$scr_image     = okip_media_exists(isset($monitor['screen_image']) ? $monitor['screen_image'] : '');
$scr_video_url = $scr_video ? okip_media_url($monitor['screen_video']) : '';
$scr_image_url = $scr_image ? okip_media_url($monitor['screen_image']) : '';
$scr_has       = ($scr_video_url || $scr_image_url);
$mon_alt       = isset($monitor['alt']) ? $monitor['alt'] : '';
$frame_on      = ! empty($monitor['frame_enabled']);
$ph_on         = ! empty($monitor['placeholder_enabled']);

// --- Overlay ---
$overlay_on      = ! empty($overlay['enabled']);
$overlay_opacity = isset($overlay['opacity']) ? (float) $overlay['opacity'] : 0.25;

// --- Glow (detrás del monitor) ---
$glow_on        = ! empty($glow['enabled']);
$glow_intensity = isset($glow['intensity']) ? (float) $glow['intensity'] : 0.6;

// --- Animación / parallax ---
$anim_on    = ! empty($animation['enabled']);
$use_gsap   = ! empty($animation['use_gsap']);
$use_vanilla = ! empty($animation['use_vanilla_fallback']);
$parallax_on = ! empty($animation['parallax_enabled']);
$overlap_anim = ! empty($animation['overlap_transition_enabled']);
$bg_speed   = isset($animation['background_speed']) ? (float) $animation['background_speed'] : 0.18;
$mon_speed  = isset($animation['monitor_speed']) ? (float) $animation['monitor_speed'] : 0.45;
$txt_speed  = isset($animation['text_speed']) ? (float) $animation['text_speed'] : 0.12;
$scroll_dur = isset($animation['scroll_duration']) ? (float) $animation['scroll_duration'] : 1.0;
$pin_on     = ! empty($animation['pin_enabled']);
$text_reveal = ! empty($animation['text_reveal']);

// --- CTA ---
$cta_on = ! empty($cta['enabled']) && ! empty($cta['label']) && ! empty($cta['url']);

// --- Título con resaltado seguro ---
$title = isset($content['title']) ? $content['title'] : '';
$hl    = isset($content['highlighted_text']) ? $content['highlighted_text'] : '';

// Variables CSS de layout/escena (seguras, solo de presentación).
$section_style = sprintf(
    '--okip-pm-minh:%s;--okip-pm-cw:%s;--okip-pm-z:%d;--okip-pm-overlap:%s;--okip-pm-glow:%s;',
    esc_attr($min_height),
    esc_attr($content_width),
    (int) $z_index,
    esc_attr(($overlap_on && $overlap_anim) ? $overlap_amt : '0px'),
    esc_attr((string) max(0, min(1, $glow_intensity)))
);

$section_classes = 'okip-pm';
$section_classes .= $anim_on ? ' okip-pm--animated' : '';
$section_classes .= ($overlap_on && $overlap_anim) ? ' okip-pm--overlap' : '';
?>
<section
    id="<?php echo esc_attr($okip_instance); ?>"
    class="<?php echo esc_attr($section_classes); ?>"
    data-block-instance="<?php echo esc_attr($okip_instance); ?>"
    data-okip-pm
    data-anim="<?php echo $anim_on ? '1' : '0'; ?>"
    data-parallax="<?php echo $parallax_on ? '1' : '0'; ?>"
    data-use-gsap="<?php echo $use_gsap ? '1' : '0'; ?>"
    data-use-vanilla="<?php echo $use_vanilla ? '1' : '0'; ?>"
    data-text-reveal="<?php echo $text_reveal ? '1' : '0'; ?>"
    data-pin="<?php echo $pin_on ? '1' : '0'; ?>"
    data-scroll-duration="<?php echo esc_attr((string) $scroll_dur); ?>"
    style="<?php echo $section_style; ?>">

    <!-- Capa 1: fondo (parallax lento) -->
    <div class="okip-pm__bg okip-pm__bg--<?php echo esc_attr(sanitize_html_class($bg_render)); ?>"
        data-okip-pm-bg data-okip-pm-layer data-speed="<?php echo esc_attr((string) $bg_speed); ?>">
        <?php if ($bg_render === 'video') : ?>
            <video class="okip-pm__bg-media" muted autoplay loop playsinline preload="auto"
                <?php echo $bg_poster ? 'poster="' . esc_url($bg_poster) . '"' : ''; ?>>
                <source src="<?php echo esc_url($bg_url); ?>" type="video/mp4">
            </video>
        <?php elseif ($bg_render === 'image' || $bg_render === 'svg') : ?>
            <img class="okip-pm__bg-media" src="<?php echo esc_url($bg_url); ?>" alt="" aria-hidden="true">
        <?php else : ?>
            <!-- bg-missing: sin media real. Fallback neutro por CSS. -->
        <?php endif; ?>
    </div>

    <!-- Capa 2: overlay opcional -->
    <?php if ($overlay_on) : ?>
        <div class="okip-pm__overlay" aria-hidden="true"
            style="opacity:<?php echo esc_attr((string) max(0, min(1, $overlay_opacity))); ?>;"></div>
    <?php endif; ?>

    <div class="okip-pm__inner">

        <!-- Capa 5: texto (izquierda, parallax muy ligero) -->
        <div class="okip-pm__text" data-okip-pm-text data-okip-pm-layer data-speed="<?php echo esc_attr((string) $txt_speed); ?>">
            <?php if (! empty($content['eyebrow'])) : ?>
                <p class="okip-pm__eyebrow"><?php echo esc_html($content['eyebrow']); ?></p>
            <?php endif; ?>

            <?php if ($title !== '') : ?>
                <h2 class="okip-pm__title">
                    <?php
                    // Resaltado seguro: parte el título alrededor de la subcadena.
                    $pos = ($hl !== '') ? stripos($title, $hl) : false;
                    if ($pos !== false) {
                        echo esc_html(substr($title, 0, $pos));
                        echo '<span class="okip-pm__highlight">' . esc_html(substr($title, $pos, strlen($hl))) . '</span>';
                        echo esc_html(substr($title, $pos + strlen($hl)));
                    } else {
                        echo esc_html($title);
                    }
                    ?>
                </h2>
            <?php endif; ?>

            <?php if (! empty($content['description'])) : ?>
                <p class="okip-pm__desc"><?php echo wp_kses_post($content['description']); ?></p>
            <?php endif; ?>

            <?php if ($cta_on) : ?>
                <a class="okip-button okip-pm__cta" href="<?php echo esc_url($cta['url']); ?>">
                    <?php echo esc_html($cta['label']); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Capa 4: monitor/pantalla (derecha, parallax rápido). Glow detrás (capa 3). -->
        <div class="okip-pm__monitor<?php echo $frame_on ? ' okip-pm__monitor--frame' : ''; ?><?php echo $glow_on ? ' okip-pm__monitor--glow' : ''; ?>"
            data-okip-pm-monitor data-okip-pm-layer data-speed="<?php echo esc_attr((string) $mon_speed); ?>">
            <?php if ($mon_frame_url) : ?>
                <img class="okip-pm__device" src="<?php echo esc_url($mon_frame_url); ?>" alt="<?php echo esc_attr($mon_alt); ?>">
            <?php endif; ?>
            <div class="okip-pm__screen">
                <?php if ($scr_video_url) : ?>
                    <video class="okip-pm__screen-media" muted autoplay loop playsinline preload="auto">
                        <source src="<?php echo esc_url($scr_video_url); ?>" type="video/mp4">
                    </video>
                <?php elseif ($scr_image_url) : ?>
                    <img class="okip-pm__screen-media" src="<?php echo esc_url($scr_image_url); ?>" alt="<?php echo esc_attr($mon_alt); ?>">
                <?php elseif ($ph_on) : ?>
                    <!-- Placeholder geométrico de pantalla (sin media real). -->
                    <span class="okip-pm__screen-ph" aria-hidden="true">
                        <span class="okip-pm__screen-ph-bar"></span>
                        <span class="okip-pm__screen-ph-grid"></span>
                    </span>
                <?php else : ?>
                    <!-- Sin media de pantalla: queda el marco/pantalla neutra (CSS). -->
                <?php endif; ?>
            </div>
            <span class="okip-pm__stand" aria-hidden="true"></span>
        </div>

    </div>
</section>
