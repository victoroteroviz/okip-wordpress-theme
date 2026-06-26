<?php

/**
 * Bloque Parallax Monitor (Bloque 2).
 *
 * Escena oscura full-screen que cubre al Hero sticky por flujo/z-index al salir
 * del primer viewport. TRES capas reales superpuestas a nivel de sección, cada
 * una con su profundidad de parallax (nodo EXTERIOR) y su reveal (nodo INTERIOR):
 *   1) background (z1, data-okip-pm-layer="background") — fondo, parallax lento
 *   2) computer   (z2, data-okip-pm-layer="computer")   — video/webm full-bleed, medio
 *   3) text       (z3, data-okip-pm-layer="text")        — texto izquierda, rápido
 *
 * Las capas NO viven en un grid (se solaparían mal): son hermanas absolutas de la
 * sección y se apilan por z-index. El texto va en .okip-pm__inner (ancho de
 * contenido + padding) por encima de la escena.
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
$cmp_has    = in_array($cmp_type, array('video', 'image', 'svg', 'gif'), true)
    && okip_media_exists(isset($computer['media']) ? $computer['media'] : '');
$cmp_url    = $cmp_has ? okip_media_url($computer['media']) : '';
$cmp_poster = isset($computer['poster']) && okip_media_exists($computer['poster']) ? okip_media_url($computer['poster']) : '';
$cmp_alt    = isset($computer['alt']) ? $computer['alt'] : '';
$cmp_render_mode = isset($computer['render_mode']) && in_array($computer['render_mode'], array('screen', 'scene'), true)
    ? $computer['render_mode']
    : 'screen';
$cmp_black_key = ! empty($computer['black_key_enabled']);
$cmp_scene     = $cmp_has && $cmp_render_mode === 'scene';
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
$bg_pin_on    = ! empty($animation['background_pin']);
$bg_pin_vh    = isset($animation['background_pin_vh']) ? (int) $animation['background_pin_vh'] : 100;
$entry_vh     = isset($animation['entry_scroll_vh']) ? (int) $animation['entry_scroll_vh'] : 155;
$cover_delay  = isset($animation['cover_delay_vh']) ? (int) $animation['cover_delay_vh'] : 50;
$cover_start  = isset($animation['cover_start_vh']) ? (int) $animation['cover_start_vh'] : 8;
$cover_ramp   = isset($animation['cover_ramp']) ? (float) $animation['cover_ramp'] : 0.45;
$bg_speed      = isset($animation['background_speed']) ? (float) $animation['background_speed'] : 0.45;
$cmp_speed     = isset($animation['computer_speed']) ? (float) $animation['computer_speed'] : 0.78;
$txt_speed     = isset($animation['text_speed']) ? (float) $animation['text_speed'] : 0.95;
$anim_drift_px = isset($animation['parallax_drift_px']) ? (int) $animation['parallax_drift_px'] : 180;
$overlap_bp    = isset($animation['overlap_breakpoint']) ? (int) $animation['overlap_breakpoint'] : 1024;

$bg_range  = isset($animation['background_enter_range']) ? $animation['background_enter_range'] : array(0.00, 0.08);
$cmp_range = isset($animation['computer_enter_range']) ? $animation['computer_enter_range'] : array(0.28, 0.64);
$txt_range = isset($animation['text_enter_range']) ? $animation['text_enter_range'] : array(0.70, 1.00);
$range_str = function ($r) {
    $a = isset($r[0]) ? (float) $r[0] : 0;
    $b = isset($r[1]) ? (float) $r[1] : 1;
    return $a . ',' . $b;
};

// MIME del <source> derivado de la extensión del media (soporta webm/mp4/ogg/mov).
$okip_video_mime = function ($url) {
    $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    $map = array(
        'webm' => 'video/webm',
        'ogv'  => 'video/ogg',
        'ogg'  => 'video/ogg',
        'm4v'  => 'video/mp4',
        'mp4'  => 'video/mp4',
        'mov'  => 'video/quicktime',
    );
    return isset($map[$ext]) ? $map[$ext] : 'video/mp4';
};

// --- CTA ---
$cta_on = ! empty($cta['enabled']) && ! empty($cta['label']) && ! empty($cta['url']);

// --- Título con resaltado seguro ---
$title    = isset($content['title']) ? $content['title'] : '';
$hl       = isset($content['highlighted_text']) ? $content['highlighted_text'] : '';
$subtitle = isset($content['subtitle']) ? $content['subtitle'] : '';

// Variables CSS de layout/escena (seguras, solo de presentación).
$section_style = sprintf(
    '--okip-pm-minh:%s;--okip-pm-cw:%s;--okip-pm-z:%d;--okip-pm-glow:%s;--okip-pm-bg-color:%s;',
    esc_attr($min_height),
    esc_attr($content_width),
    (int) $z_index,
    esc_attr((string) max(0, min(1, $glow_intensity))),
    esc_attr($bg_color)
);

$section_classes = 'okip-pm';
$section_classes .= $anim_on ? ' okip-pm--animated' : '';
$section_classes .= $cmp_scene ? ' okip-pm--computer-scene' : '';

// Markup del monitor (capa 2) — se construye aparte porque ahora es una capa de
// sección (hermana del fondo), no una celda del grid del texto.
$monitor_classes = 'okip-pm__monitor';
$monitor_classes .= $cmp_scene ? ' okip-pm__monitor--scene' : '';
$monitor_classes .= (! $cmp_scene && $frame_on) ? ' okip-pm__monitor--frame' : '';
$monitor_classes .= ($cmp_scene && $cmp_black_key) ? ' okip-pm__monitor--black-key' : '';
$monitor_classes .= $glow_on ? ' okip-pm__monitor--glow' : '';
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
    data-drift-max="<?php echo esc_attr((string) $anim_drift_px); ?>"
    data-bg-pin="<?php echo $bg_pin_on ? '1' : '0'; ?>"
    data-bg-pin-vh="<?php echo esc_attr((string) $bg_pin_vh); ?>"
    data-entry-scroll-vh="<?php echo esc_attr((string) $entry_vh); ?>"
    data-cover-delay-vh="<?php echo esc_attr((string) $cover_delay); ?>"
    data-cover-start-vh="<?php echo esc_attr((string) $cover_start); ?>"
    data-cover-ramp="<?php echo esc_attr((string) $cover_ramp); ?>"
    data-overlap-bp="<?php echo esc_attr((string) $overlap_bp); ?>"
    style="<?php echo $section_style; ?>">

    <!-- Cover rápido B1→B2: capa fija de fondo que tapa al Hero con un gesto corto.
         No contiene contenido y vive por debajo de las capas reales de B2; computer/text
         conservan su scrub normal. -->
    <div class="okip-pm__cover" data-okip-pm-cover aria-hidden="true"></div>

    <!-- Capa 1: fondo real de la escena (z1).
         EXTERIOR (.okip-pm__bg) = PARALLAX (transform por JS) · INTERIOR
         (.okip-pm__bg-inner) = REVEAL (opacidad) + media/gradiente. -->
    <div class="okip-pm__bg"
        data-okip-pm-layer="background"
        data-speed="<?php echo esc_attr((string) $bg_speed); ?>"
        data-enter="<?php echo esc_attr($range_str($bg_range)); ?>">
        <div class="okip-pm__bg-inner okip-pm__bg-inner--<?php echo esc_attr(sanitize_html_class($bg_render)); ?><?php echo ($bg_gradient && $bg_render === 'missing') ? ' okip-pm__bg-inner--gradient' : ''; ?>">
            <?php if ($bg_render === 'video') : ?>
                <video class="okip-pm__bg-media" muted autoplay loop playsinline preload="auto"
                    <?php echo $bg_poster ? 'poster="' . esc_url($bg_poster) . '"' : ''; ?>>
                    <source src="<?php echo esc_url($bg_url); ?>" type="<?php echo esc_attr($okip_video_mime($bg_url)); ?>">
                </video>
            <?php elseif ($bg_render === 'image' || $bg_render === 'svg') : ?>
                <img class="okip-pm__bg-media" src="<?php echo esc_url($bg_url); ?>" alt="" aria-hidden="true">
            <?php else : ?>
                <!-- bg-missing: sin media real. Fallback neutro por CSS en el inner. -->
            <?php endif; ?>
        </div>
    </div>

    <!-- Overlay opcional (sobre el fondo) -->
    <?php if ($overlay_on) : ?>
        <div class="okip-pm__overlay" aria-hidden="true"
            style="opacity:<?php echo esc_attr((string) max(0, min(1, $overlay_opacity))); ?>;"></div>
    <?php endif; ?>

    <!-- Piso / reflejo de escena: luz azul ambiental en la base, bajo el monitor.
         Iluminación de escena (no fondo decorativo falso); estable, sin parallax.
         En modo escena se oculta por CSS (el video ya trae su propio piso). -->
    <div class="okip-pm__floor" aria-hidden="true"></div>

    <!-- Capa 2: video/monitor (z2) — capa de ESCENA a nivel de sección, full-bleed.
         EXTERIOR (.okip-pm__monitor) = PARALLAX · INTERIOR (.okip-pm__computer-reveal)
         = REVEAL. El glow vive en el nodo exterior, no en el interior. -->
    <div class="<?php echo esc_attr($monitor_classes); ?>"
        data-okip-pm-layer="computer"
        data-render-mode="<?php echo esc_attr($cmp_scene ? 'scene' : 'screen'); ?>"
        data-speed="<?php echo esc_attr((string) $cmp_speed); ?>"
        data-enter="<?php echo esc_attr($range_str($cmp_range)); ?>"
        data-autoplay-on-enter="<?php echo $cmp_auto ? '1' : '0'; ?>">
        <div class="okip-pm__computer-reveal">
            <?php if ($cmp_scene) : ?>
                <div class="okip-pm__scene">
                    <?php if ($cmp_type === 'video') : ?>
                        <video class="okip-pm__scene-media" muted loop playsinline preload="metadata" data-okip-pm-computer-video aria-label="<?php echo esc_attr($cmp_alt); ?>"
                            <?php echo $cmp_poster ? 'poster="' . esc_url($cmp_poster) . '"' : ''; ?>>
                            <source src="<?php echo esc_url($cmp_url); ?>" type="<?php echo esc_attr($okip_video_mime($cmp_url)); ?>">
                        </video>
                    <?php else : ?>
                        <img class="okip-pm__scene-media" src="<?php echo esc_url($cmp_url); ?>" alt="<?php echo esc_attr($cmp_alt); ?>" decoding="async">
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="okip-pm__screen">
                    <?php if ($cmp_has && $cmp_type === 'video') : ?>
                        <video class="okip-pm__screen-media" muted loop playsinline preload="metadata" data-okip-pm-computer-video data-okip-pm-screen-video
                            <?php echo $cmp_poster ? 'poster="' . esc_url($cmp_poster) . '"' : ''; ?>>
                            <source src="<?php echo esc_url($cmp_url); ?>" type="<?php echo esc_attr($okip_video_mime($cmp_url)); ?>">
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
            <?php endif; ?>
        </div>
    </div>

    <!-- Capa 3: texto (z3) — capa frontal, alineada a la izquierda, sobre la escena.
         EXTERIOR (.okip-pm__text) = PARALLAX · INTERIOR (.okip-pm__text-reveal) = REVEAL. -->
    <div class="okip-pm__inner">
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
    </div>

</section>
