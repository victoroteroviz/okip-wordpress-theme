<?php

/**
 * Bloque Mission Statement (Bloque 5).
 *
 * Sección institucional centrada con fondo oscuro. Si existe un medio de fondo
 * válido, reemplaza el gradiente; si no, se usa el gradiente animado azul.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

$okip_instance = isset($args['instance_id']) ? $args['instance_id'] : 'mission-statement';
$okip_data     = isset($args['data']) && is_array($args['data']) ? $args['data'] : array();

$content    = isset($okip_data['content'])    ? $okip_data['content']    : array();
$background = isset($okip_data['background']) ? $okip_data['background'] : array();
$layout     = isset($okip_data['layout'])     ? $okip_data['layout']     : array();
$animation  = isset($okip_data['animation'])  ? $okip_data['animation']  : array();

$lines       = isset($content['lines']) && is_array($content['lines']) ? $content['lines'] : array();
$strong_line = isset($content['strong_line']) ? $content['strong_line'] : '';
$kicker      = isset($content['kicker']) ? $content['kicker'] : '';

$mode  = isset($background['mode']) ? $background['mode'] : 'gradient';
$media = isset($background['media']) ? $background['media'] : '';
$media_exists = $media !== '' && okip_media_exists($media);
$media_url    = $media_exists ? okip_media_url($media) : '';
$has_media    = $media_exists && $media_url !== '';

$gradient = isset($background['gradient']) && is_array($background['gradient']) ? $background['gradient'] : array();
$dark_color  = isset($gradient['dark_color']) ? $gradient['dark_color'] : '#000000';
$blue_color  = isset($gradient['blue_color']) ? $gradient['blue_color'] : '#006fcf';
$blue_glow   = isset($gradient['blue_glow']) ? $gradient['blue_glow'] : 'rgba(0,111,207,.82)';
$blue_soft   = isset($gradient['blue_soft']) ? $gradient['blue_soft'] : 'rgba(0,111,207,.3)';
$duration_ms = isset($gradient['duration_ms']) ? (int) $gradient['duration_ms'] : 6500;
$intensity   = isset($gradient['intensity']) ? (float) $gradient['intensity'] : 0.82;
$grad_x      = isset($gradient['x']) ? (int) $gradient['x'] : 50;
$grad_y      = isset($gradient['y']) ? (int) $gradient['y'] : 104;

$padding_top    = isset($layout['padding_top'])    ? $layout['padding_top']    : '7rem';
$padding_bottom = isset($layout['padding_bottom']) ? $layout['padding_bottom'] : '6.5rem';
$content_width  = isset($layout['content_width'])  ? $layout['content_width']  : '820px';
$z_index        = isset($layout['z_index'])        ? (int) $layout['z_index']  : 5;

$anim_on   = ! empty($animation['enabled']);
$text_anim = isset($animation['text']) ? $animation['text'] : 'fade-up';

$section_classes = 'okip-ms';
$section_classes .= $has_media ? ' okip-ms--has-media' : ' okip-ms--gradient';
$section_classes .= $anim_on && $text_anim !== 'none' ? ' okip-ms--animated' : ' okip-ms--static';
$section_classes .= ' okip-ms--anim-' . sanitize_html_class($text_anim);

$section_style = sprintf(
    '--okip-ms-bg:%s;--okip-ms-blue:%s;--okip-ms-blue-glow:%s;--okip-ms-blue-soft:%s;--okip-ms-duration:%dms;--okip-ms-intensity:%s;--okip-ms-x:%d%%;--okip-ms-y:%d%%;--okip-ms-pt:%s;--okip-ms-pb:%s;--okip-ms-cw:%s;--okip-ms-z:%d;',
    esc_attr($dark_color),
    esc_attr($blue_color),
    esc_attr($blue_glow),
    esc_attr($blue_soft),
    $duration_ms,
    esc_attr((string) $intensity),
    $grad_x,
    $grad_y,
    esc_attr($padding_top),
    esc_attr($padding_bottom),
    esc_attr($content_width),
    $z_index
);

if (empty($lines) && $strong_line === '' && $kicker === '') {
    return;
}
?>
<section
    id="<?php echo esc_attr($okip_instance); ?>"
    class="<?php echo esc_attr($section_classes); ?>"
    data-block-instance="<?php echo esc_attr($okip_instance); ?>"
    data-okip-ms
    data-anim="<?php echo $anim_on ? '1' : '0'; ?>"
    data-text-anim="<?php echo esc_attr($text_anim); ?>"
    style="<?php echo $section_style; ?>">

    <div class="okip-ms__background" aria-hidden="true">
        <?php if ($has_media && $mode === 'video') : ?>
            <video class="okip-ms__media" muted loop playsinline autoplay preload="metadata">
                <source src="<?php echo esc_url($media_url); ?>" type="video/mp4">
            </video>
        <?php elseif ($has_media) : ?>
            <img class="okip-ms__media" src="<?php echo esc_url($media_url); ?>" alt="">
        <?php endif; ?>
    </div>

    <div class="okip-ms__inner">
        <p class="okip-ms__statement" aria-label="<?php echo esc_attr(trim(implode(' ', $lines) . ' ' . $strong_line)); ?>">
            <?php foreach ($lines as $line) : ?>
                <span class="okip-ms__line"><?php echo esc_html($line); ?></span>
            <?php endforeach; ?>
            <?php if ($strong_line !== '') : ?>
                <strong class="okip-ms__line okip-ms__line--strong"><?php echo esc_html($strong_line); ?></strong>
            <?php endif; ?>
        </p>

        <?php if ($kicker !== '') : ?>
            <p class="okip-ms__kicker"><?php echo esc_html($kicker); ?></p>
        <?php endif; ?>
    </div>
</section>
