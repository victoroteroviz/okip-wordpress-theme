<?php

/**
 * Bloque Parallax Monitor (Bloque 2).
 *
 * Escena oscura full-screen que cubre al Hero sticky por flujo/z-index al salir
 * del primer viewport. Tres capas reales con z-index y ritmos distintos:
 *   1) background (z1, data-okip-pm-layer="background") — entra primero/rápido
 *   2) computer   (z2, data-okip-pm-layer="computer")   — entra con retardo
 *   3) text       (z3, data-okip-pm-layer="text")        — entra al final, arriba
 *
 * Toda la lógica de reveal/parallax/pin vive en script.js (GSAP+ScrollTrigger si
 * disponibles, con fallback vanilla rAF). El template solo expone config como data-* y vars
 * CSS. Scope por instancia con id + data-block-instance.
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
$computer   = isset($okip_data['computer']) ? $okip_data['computer'] : array();
$cta        = isset($okip_data['cta']) ? $okip_data['cta'] : array();
$overlay    = isset($okip_data['overlay']) ? $okip_data['overlay'] : array();
$glow       = isset($okip_data['glow']) ? $okip_data['glow'] : array();
$animation  = isset($okip_data['animation']) ? $okip_data['animation'] : array();

// --- Layout / escena ---
$min_height    = isset($layout['min_height']) ? $layout['min_height'] : '100svh';
$content_width = isset($layout['content_width']) ? $layout['content_width'] : '1200px';
$overlap_on    = ! empty($layout['overlap_previous']);
$overlap_start = isset($layout['overlap_start']) ? (float) $layout['overlap_start'] : 0.85;
$overlap_amt   = isset($layout['overlap_amount']) ? $layout['overlap_amount'] : '18vh';
$overlap_vh    = (float) preg_replace('/[^0-9.\-]/', '', (string) $overlap_amt); // valor numérico en vh para el JS
$z_index       = isset($layout['z_index']) ? (int) $layout['z_index'] : 2;

// --- Fondo (media-driven) ---
$bg_type   = isset($background['type']) ? $background['type'] : 'image';
$bg_has    = in_array($bg_type, array('image', 'video', 'svg'), true)
    && okip_media_exists(isset($background['media']) ? $background['media'] : '');
$bg_url    = $bg_has ? okip_media_url($background['media']) : '';
$bg_poster = isset($background['poster']) && okip_media_exists($background['poster']) ? okip_media_url($background['poster']) : '';
$bg_render   = $bg_has ? $bg_type : 'missing';
$bg_gradient = ! empty($background['gradient']);
$bg_color    = isset($background['color']) ? $background['color'] : '#050a16';

// --- Computadora (capa media del monitor) ---
$cmp_type   = isset($computer['type']) ? $computer['type'] : 'placeholder';
$cmp_has    = in_array($cmp_type, array('video', 'image', 'svg'), true)
    && okip_media_exists(isset($computer['media']) ? $computer['media'] : '');
$cmp_url    = $cmp_has ? okip_media_url($computer['media']) : '';
$cmp_poster = isset($computer['poster']) && okip_media_exists($computer['poster']) ? okip_media_url($computer['poster']) : '';
$cmp_alt    = isset($computer['alt']) ? $computer['alt'] : '';
$cmp_auto   = ! empty($computer['autoplay_on_enter']);
$frame_on   = ! empty($computer['frame_enabled']);
$ph_on      = ! empty($computer['placeholder_enabled']);

// --- Overlay ---
$overlay_on      = ! empty($overlay['enabled']);
$overlay_opacity = isset($overlay['opacity']) ? (float) $overlay['opacity'] : 0.25;

// --- Glow (detrás del monitor) ---
$glow_on        = ! empty($glow['enabled']);
$glow_intensity = isset($glow['intensity']) ? (float) $glow['intensity'] : 0.6;

// --- Animación / transición / parallax ---
$anim_on      = ! empty($animation['enabled']);
$use_gsap     = ! empty($animation['use_gsap']);
$use_vanilla  = ! empty($animation['use_vanilla_fallback']);
$parallax_on  = ! empty($animation['parallax_enabled']);
$overlap_anim = ! empty($animation['overlap_transition_enabled']);
$pin_on       = ! empty($animation['pin_enabled']);
$bg_pin_on    = ! empty($animation['background_pin']);
$bg_pin_vh    = isset($animation['background_pin_vh']) ? (int) $animation['background_pin_vh'] : 100;
$text_reveal  = ! empty($animation['text_reveal']);
$start_prog   = isset($animation['start_progress']) ? (float) $animation['start_progress'] : $overlap_start;
$bg_speed      = isset($animation['background_speed']) ? (float) $animation['background_speed'] : 0.28;
$cmp_speed     = isset($animation['computer_speed']) ? (float) $animation['computer_speed'] : 0.62;
$txt_speed     = isset($animation['text_speed']) ? (float) $animation['text_speed'] : 0.95;
$anim_drift_px = isset($animation['parallax_drift_px']) ? (int) $animation['parallax_drift_px'] : 140;
$disable_below = isset($animation['disable_parallax_below']) ? (int) $animation['disable_parallax_below'] : 0;

$bg_range  = isset($animation['background_enter_range']) ? $animation['background_enter_range'] : array(0.00, 0.35);
$cmp_range = isset($animation['computer_enter_range']) ? $animation['computer_enter_range'] : array(0.25, 0.70);
$txt_range = isset($animation['text_enter_range']) ? $animation['text_enter_range'] : array(0.55, 1.00);
$range_str = function ($r) {
    $a = isset($r[0]) ? (float) $r[0] : 0;
    $b = isset($r[1]) ? (float) $r[1] : 1;
    return $a . ',' . $b;
};

// La transición de overlap está activa solo si: overlap_previous + su flag + animación.
$transition_on = ($overlap_on && $overlap_anim && $anim_on);

// --- CTA ---
$cta_on = ! empty($cta['enabled']) && ! empty($cta['label']) && ! empty($cta['url']);

// --- Título con resaltado seguro ---
$title    = isset($content['title']) ? $content['title'] : '';
$hl       = isset($content['highlighted_text']) ? $content['highlighted_text'] : '';
$subtitle = isset($content['subtitle']) ? $content['subtitle'] : '';

// Variables CSS de layout/escena (seguras, solo de presentación).
$section_style = sprintf(
    '--okip-pm-minh:%s;--okip-pm-cw:%s;--okip-pm-z:%d;--okip-pm-overlap:%s;--okip-pm-glow:%s;--okip-pm-bg-color:%s;',
    esc_attr($min_height),
    esc_attr($content_width),
    (int) $z_index,
    esc_attr($overlap_amt),
    esc_attr((string) max(0, min(1, $glow_intensity))),
    esc_attr($bg_color)
);

$section_classes = 'okip-pm';
$section_classes .= $anim_on ? ' okip-pm--animated' : '';
$section_classes .= $transition_on ? ' okip-pm--transition' : '';
?>
<section
    id="<?php echo esc_attr($okip_instance); ?>"
    class="<?php echo esc_attr($section_classes); ?>"
    data-block-instance="<?php echo esc_attr($okip_instance); ?>"
    data-okip-pm
    data-anim="<?php echo $anim_on ? '1' : '0'; ?>"
    data-transition="<?php echo $transition_on ? '1' : '0'; ?>"
    data-parallax="<?php echo $parallax_on ? '1' : '0'; ?>"
    data-use-gsap="<?php echo $use_gsap ? '1' : '0'; ?>"
    data-use-vanilla="<?php echo $use_vanilla ? '1' : '0'; ?>"
    data-text-reveal="<?php echo $text_reveal ? '1' : '0'; ?>"
    data-pin="<?php echo $pin_on ? '1' : '0'; ?>"
    data-overlap-start="<?php echo esc_attr((string) $start_prog); ?>"
    data-overlap-vh="<?php echo esc_attr((string) $overlap_vh); ?>"
    data-drift-max="<?php echo esc_attr((string) $anim_drift_px); ?>"
    data-bg-pin="<?php echo $bg_pin_on ? '1' : '0'; ?>"
    data-bg-pin-vh="<?php echo esc_attr((string) $bg_pin_vh); ?>"
    data-disable-below="<?php echo esc_attr((string) $disable_below); ?>"
    style="<?php echo $section_style; ?>">

    <!-- Capa 1: fondo. EXTERIOR = parallax (transform + headroom).
         INTERIOR (.okip-pm__bg-inner) = reveal (opacidad) y media/gradiente.
         Nunca reveal y parallax en el mismo nodo. -->
    <div class="okip-pm__bg"
        data-okip-pm-layer="background"
        data-speed="<?php echo esc_attr((string) $bg_speed); ?>"
        data-enter="<?php echo esc_attr($range_str($bg_range)); ?>">
        <div class="okip-pm__bg-inner okip-pm__bg-inner--<?php echo esc_attr(sanitize_html_class($bg_render)); ?><?php echo ($bg_gradient && $bg_render === 'missing') ? ' okip-pm__bg-inner--gradient' : ''; ?>">
            <?php if ($bg_render === 'video') : ?>
                <video class="okip-pm__bg-media" muted autoplay loop playsinline preload="auto"
                    <?php echo $bg_poster ? 'poster="' . esc_url($bg_poster) . '"' : ''; ?>>
                    <source src="<?php echo esc_url($bg_url); ?>" type="video/mp4">
                </video>
            <?php elseif ($bg_render === 'image' || $bg_render === 'svg') : ?>
                <img class="okip-pm__bg-media" src="<?php echo esc_url($bg_url); ?>" alt="" aria-hidden="true">
            <?php else : ?>
                <!-- bg-missing: sin media real. Fallback neutro por CSS en el inner. -->
            <?php endif; ?>
        </div>
    </div>

    <!-- Capa 2 (overlay opcional, sobre el fondo) -->
    <?php if ($overlay_on) : ?>
        <div class="okip-pm__overlay" aria-hidden="true"
            style="opacity:<?php echo esc_attr((string) max(0, min(1, $overlay_opacity))); ?>;"></div>
    <?php endif; ?>

    <!-- Piso / reflejo de escena: luz azul ambiental en la base, bajo el monitor.
         Iluminación de escena (no fondo decorativo falso); estable, sin parallax. -->
    <div class="okip-pm__floor" aria-hidden="true"></div>

    <div class="okip-pm__inner">

        <!-- Capa 3: texto (z3, entra al final, queda arriba).
             Wrapper exterior = PARALLAX (transform por JS) · interior = REVEAL
             (opacidad/translate por CLASE latcheada). Nunca el mismo nodo. -->
        <div class="okip-pm__text"
            data-okip-pm-layer="text"
            data-speed="<?php echo esc_attr((string) $txt_speed); ?>"
            data-enter="<?php echo esc_attr($range_str($txt_range)); ?>">
            <div class="okip-pm__text-reveal">
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

                <?php if ($subtitle !== '') : ?>
                    <p class="okip-pm__subtitle"><?php echo esc_html($subtitle); ?></p>
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
        </div>

        <!-- Capa 2: computadora/monitor (z2, entra tras el fondo). Glow detrás. -->
        <div class="okip-pm__monitor<?php echo $frame_on ? ' okip-pm__monitor--frame' : ''; ?><?php echo $glow_on ? ' okip-pm__monitor--glow' : ''; ?>"
            data-okip-pm-layer="computer"
            data-speed="<?php echo esc_attr((string) $cmp_speed); ?>"
            data-enter="<?php echo esc_attr($range_str($cmp_range)); ?>"
            data-autoplay-on-enter="<?php echo $cmp_auto ? '1' : '0'; ?>">
            <!-- Wrapper interior = REVEAL (clase latcheada). El glow vive en el
                 nodo exterior (parallax), no aquí. -->
            <div class="okip-pm__computer-reveal">
                <div class="okip-pm__screen">
                    <?php if ($cmp_has && $cmp_type === 'video') : ?>
                        <video class="okip-pm__screen-media" muted loop playsinline preload="metadata" data-okip-pm-screen-video
                            <?php echo $cmp_poster ? 'poster="' . esc_url($cmp_poster) . '"' : ''; ?>>
                            <source src="<?php echo esc_url($cmp_url); ?>" type="video/mp4">
                        </video>
                    <?php elseif ($cmp_has) : ?>
                        <img class="okip-pm__screen-media" src="<?php echo esc_url($cmp_url); ?>" alt="<?php echo esc_attr($cmp_alt); ?>">
                    <?php elseif ($ph_on) : ?>
                        <!-- Placeholder esquemático tipo dashboard (sin media real):
                             barra superior + panel principal ("mapa") + tarjetas laterales.
                             Es claramente un wireframe, no un mockup fotográfico falso. -->
                        <span class="okip-pm__screen-ph" aria-hidden="true">
                            <span class="okip-pm__screen-ph-bar">
                                <span class="okip-pm__screen-ph-dot"></span>
                                <span class="okip-pm__screen-ph-dot"></span>
                                <span class="okip-pm__screen-ph-dot"></span>
                            </span>
                            <span class="okip-pm__screen-ph-body">
                                <span class="okip-pm__screen-ph-map"></span>
                                <span class="okip-pm__screen-ph-side">
                                    <span class="okip-pm__screen-ph-card"></span>
                                    <span class="okip-pm__screen-ph-card"></span>
                                    <span class="okip-pm__screen-ph-card"></span>
                                </span>
                            </span>
                        </span>
                    <?php else : ?>
                        <!-- Sin media de pantalla: queda el marco/pantalla neutra (CSS). -->
                    <?php endif; ?>
                </div>
                <span class="okip-pm__stand" aria-hidden="true"></span>
            </div>
        </div>

    </div>
</section>
