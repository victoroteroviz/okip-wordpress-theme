<?php

/**
 * Bloque Parallax Monitor (Bloque 2).
 *
 * Capas: 1) fondo (media-driven, fallback neutro) · 2) overlay opcional ·
 * 3) monitor/pantalla (media real o marco geométrico mínimo) · 4) texto (izq.).
 *
 * Toda la lógica de scroll/parallax vive en script.js. Scope por instancia con
 * id + data-block-instance. El monitor y el fondo exponen data-speed para el JS.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

$okip_instance = isset($args['instance_id']) ? $args['instance_id'] : 'parallax-monitor';
$okip_data     = isset($args['data']) && is_array($args['data']) ? $args['data'] : array();

$content    = isset($okip_data['content']) ? $okip_data['content'] : array();
$background  = isset($okip_data['background']) ? $okip_data['background'] : array();
$monitor    = isset($okip_data['monitor']) ? $okip_data['monitor'] : array();
$cta        = isset($okip_data['cta']) ? $okip_data['cta'] : array();
$overlay    = isset($okip_data['overlay']) ? $okip_data['overlay'] : array();
$animation  = isset($okip_data['animation']) ? $okip_data['animation'] : array();

// --- Fondo (media-driven) ---
$bg_type   = isset($background['type']) ? $background['type'] : 'image';
$bg_has    = in_array($bg_type, array('image', 'video', 'svg'), true)
    && okip_media_exists(isset($background['media']) ? $background['media'] : '');
$bg_url    = $bg_has ? okip_media_url($background['media']) : '';
$bg_poster = isset($background['poster']) && okip_media_exists($background['poster']) ? okip_media_url($background['poster']) : '';
$bg_render = $bg_has ? $bg_type : 'missing';

// --- Monitor ---
$mon_frame  = okip_media_exists(isset($monitor['image']) ? $monitor['image'] : '');
$mon_frame_url = $mon_frame ? okip_media_url($monitor['image']) : '';
$scr_video  = okip_media_exists(isset($monitor['screen_video']) ? $monitor['screen_video'] : '');
$scr_image  = okip_media_exists(isset($monitor['screen_image']) ? $monitor['screen_image'] : '');
$scr_video_url = $scr_video ? okip_media_url($monitor['screen_video']) : '';
$scr_image_url = $scr_image ? okip_media_url($monitor['screen_image']) : '';
$mon_alt    = isset($monitor['alt']) ? $monitor['alt'] : '';
$frame_on   = ! empty($monitor['frame_enabled']);

// --- Overlay ---
$overlay_on      = ! empty($overlay['enabled']);
$overlay_opacity = isset($overlay['opacity']) ? (float) $overlay['opacity'] : 0.25;

// --- Animación ---
$anim_on   = ! empty($animation['enabled']);
$strength  = isset($animation['parallax_strength']) ? (float) $animation['parallax_strength'] : 1.0;
$mon_speed = isset($animation['monitor_speed']) ? (float) $animation['monitor_speed'] : 0.18;
$bg_speed  = isset($animation['background_speed']) ? (float) $animation['background_speed'] : 0.08;
$text_reveal = ! empty($animation['text_reveal']);

// --- CTA ---
$cta_on    = ! empty($cta['enabled']) && ! empty($cta['label']) && ! empty($cta['url']);

// --- Título con resaltado seguro ---
$title = isset($content['title']) ? $content['title'] : '';
$hl    = isset($content['highlighted_text']) ? $content['highlighted_text'] : '';
?>
<section
    id="<?php echo esc_attr($okip_instance); ?>"
    class="okip-pm<?php echo $anim_on ? ' okip-pm--animated' : ''; ?>"
    data-block-instance="<?php echo esc_attr($okip_instance); ?>"
    data-okip-pm
    data-anim="<?php echo $anim_on ? '1' : '0'; ?>"
    data-text-reveal="<?php echo $text_reveal ? '1' : '0'; ?>"
    data-strength="<?php echo esc_attr((string) $strength); ?>">

    <!-- Capa 1: fondo -->
    <div class="okip-pm__bg okip-pm__bg--<?php echo esc_attr(sanitize_html_class($bg_render)); ?>"
        data-okip-pm-bg data-speed="<?php echo esc_attr((string) $bg_speed); ?>">
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

    <div class="okip-pm__inner okip-container">

        <!-- Capa 4: texto (izquierda) -->
        <div class="okip-pm__text" data-okip-pm-text>
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

        <!-- Capa 3: monitor/pantalla (derecha) -->
        <div class="okip-pm__monitor<?php echo $frame_on ? ' okip-pm__monitor--frame' : ''; ?>"
            data-okip-pm-monitor data-speed="<?php echo esc_attr((string) $mon_speed); ?>">
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
                <?php else : ?>
                    <!-- Sin media de pantalla: queda el marco/pantalla neutra (CSS). -->
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>
