<?php

/**
 * Bloque Video con Título (video-w-title).
 *
 * Sección secundaria casi full-screen: video de fondo a sangre completa (capa 1)
 * → overlay opcional (capa 2) → bloque de texto centrado (capa 3). Sustituye al
 * antiguo `parallax-monitor` entre el Hero y el Industry Carousel.
 *
 * Traspaso de salida: `transition.mode = sticky-cover`. El OUTER `.okip-vwt` queda
 * `position:sticky` (CSS, ver assets/css/transitions.css) — su contenedor es <main>,
 * así que se queda fijo y el bloque siguiente (z mayor, opaco) lo cubre, igual que el
 * Hero. `.okip-vwt__stage.okip-cover-stage` es solo la escena visible (100svh) anclada
 * al top; el resto del outer es el `--okip-hold-vh` (scroll extra). Sin ScrollTrigger.
 *
 * Media-driven: el video solo se pinta si el media existe (okip_media_exists);
 * si no → fallback sobrio (color sólido por CSS, sin diseño decorativo falso).
 *
 * Scope por instancia: id + data-block-instance + data-okip-vwt. El reveal lo "arma"
 * el script.js (clase `is-anim-armed`) → si el JS falla, el texto queda visible.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

$okip_instance = isset($args['instance_id']) ? $args['instance_id'] : 'video-w-title';
$okip_data     = isset($args['data']) && is_array($args['data']) ? $args['data'] : array();
$okip_order    = isset($args['order']) ? (int) $args['order'] : 0;

$content    = isset($okip_data['content']) ? $okip_data['content'] : array();
$video      = isset($okip_data['video']) ? $okip_data['video'] : array();
$overlay    = isset($okip_data['overlay']) ? $okip_data['overlay'] : array();
$layout     = isset($okip_data['layout']) ? $okip_data['layout'] : array();
$animation  = isset($okip_data['animation']) ? $okip_data['animation'] : array();
$transition = isset($okip_data['transition']) ? $okip_data['transition'] : array();

// --- Layout / escena ---
$min_height    = isset($layout['min_height']) ? $layout['min_height'] : '100svh';
$content_width = isset($layout['content_width']) ? $layout['content_width'] : '1100px';
// z-index raíz por ORDEN de render; layout.z_index>0 = override avanzado (retrocompat).
$z_index       = (isset($layout['z_index']) && (int) $layout['z_index'] > 0)
    ? (int) $layout['z_index']
    : ($okip_order + 1);
$alignment     = isset($layout['alignment']) && in_array($layout['alignment'], array('left', 'center'), true)
    ? $layout['alignment']
    : 'center';

// --- Video (media-driven) ---
$vid_has    = okip_media_exists(isset($video['media']) ? $video['media'] : '');
$vid_url    = $vid_has ? okip_media_url($video['media']) : '';
$vid_poster = isset($video['poster']) && okip_media_exists($video['poster']) ? okip_media_url($video['poster']) : '';
$vid_auto   = ! empty($video['autoplay']);
$vid_loop   = ! empty($video['loop']);
$vid_muted  = ! empty($video['muted']);
$vid_inline = ! empty($video['playsinline']);

// MIME del <source> derivado de la extensión del media.
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

// --- Overlay ---
$overlay_on      = ! empty($overlay['enabled']);
$overlay_color   = isset($overlay['color']) ? $overlay['color'] : '#05080f';
$overlay_opacity = isset($overlay['opacity']) ? (float) $overlay['opacity'] : 0.45;

// --- Animación de entrada (reveal) ---
$anim_on = ! empty($animation['enabled']);

// --- Traspaso de salida (sistema híbrido) ---
$hold_vh = isset($transition['hold_vh']) ? (int) $transition['hold_vh'] : 0;

// --- Contenido ---
$eyebrow     = isset($content['eyebrow']) ? $content['eyebrow'] : '';
$title       = isset($content['title']) ? $content['title'] : '';
$hl          = isset($content['highlighted_text']) ? $content['highlighted_text'] : '';
$subtitle    = isset($content['subtitle']) ? $content['subtitle'] : '';
$description = isset($content['description']) ? $content['description'] : '';

// --- Cuadros de texto posicionables (modelo del editor admin) ---
// Solo cuentan los cuadros activos CON contenido; si no hay ninguno, se cae al
// render legacy (.okip-vwt__inner) para conservar instancias antiguas sin tocar.
$text_boxes   = isset($okip_data['text_boxes']) && is_array($okip_data['text_boxes']) ? $okip_data['text_boxes'] : array();
$active_boxes = array();
foreach ($text_boxes as $box) {
    if (! is_array($box)) {
        continue;
    }
    if (! empty($box['active']) && isset($box['content']) && trim((string) $box['content']) !== '') {
        $active_boxes[] = $box;
    }
}
$use_boxes = ! empty($active_boxes);

// Etiqueta HTML por rol del cuadro.
$okip_box_tag = function ($role) {
    return $role === 'title' ? 'h2' : 'p';
};

// Variables CSS de presentación (seguras: ya clampadas / saneadas).
$section_style = sprintf(
    '--okip-vwt-minh:%s;--okip-vwt-cw:%s;--okip-vwt-z:%d;--okip-vwt-overlay-color:%s;--okip-vwt-overlay-opacity:%s;--okip-hold-vh:%d;',
    esc_attr($min_height),
    esc_attr($content_width),
    (int) $z_index,
    esc_attr($overlay_color),
    esc_attr((string) max(0, min(1, $overlay_opacity))),
    (int) $hold_vh
);

$section_classes = 'okip-vwt okip-vwt--align-' . sanitize_html_class($alignment);
$section_classes .= $anim_on ? ' okip-vwt--animated' : '';
$section_classes .= $vid_has ? ' okip-vwt--has-video' : ' okip-vwt--no-video';
?>
<section
    id="<?php echo esc_attr($okip_instance); ?>"
    class="<?php echo esc_attr($section_classes); ?>"
    data-block-instance="<?php echo esc_attr($okip_instance); ?>"
    data-okip-vwt
    data-anim="<?php echo $anim_on ? '1' : '0'; ?>"
    <?php echo okip_transition_attrs($transition); ?>
    style="<?php echo $section_style; ?>">

    <!-- Stage: el "viewport" visible que queda sticky (sticky-cover) mientras el
         bloque siguiente lo cubre. El outer (.okip-vwt) reserva el scroll del hold. -->
    <div class="okip-vwt__stage okip-cover-stage">

        <!-- Capa 1: video de fondo a sangre completa. Sin media real → el fondo
             queda en color sólido (fallback sobrio por CSS). -->
        <div class="okip-vwt__bg" data-okip-vwt-bg aria-hidden="true">
            <?php if ($vid_has) : ?>
                <video class="okip-vwt__bg-media"
                    <?php echo $vid_muted ? 'muted' : ''; ?>
                    <?php echo $vid_auto ? 'autoplay' : ''; ?>
                    <?php echo $vid_loop ? 'loop' : ''; ?>
                    <?php echo $vid_inline ? 'playsinline' : ''; ?>
                    preload="auto"
                    <?php echo $vid_poster ? 'poster="' . esc_url($vid_poster) . '"' : ''; ?>>
                    <source src="<?php echo esc_url($vid_url); ?>" type="<?php echo esc_attr($okip_video_mime($vid_url)); ?>">
                </video>
            <?php endif; ?>
        </div>

        <!-- Capa 2: overlay opcional sobre el video, para legibilidad del texto. -->
        <?php if ($overlay_on) : ?>
            <div class="okip-vwt__overlay" aria-hidden="true"></div>
        <?php endif; ?>

        <!-- Capa 3: cuadros de texto posicionables (si hay activos con contenido). -->
        <?php if ($use_boxes) : ?>
        <div class="okip-vwt__boxes">
            <?php
            foreach ($active_boxes as $box) :
                $b_role  = isset($box['role']) ? $box['role'] : 'text';
                $b_align = isset($box['align']) ? $box['align'] : 'center';
                $b_h     = isset($box['height_px']) ? (int) $box['height_px'] : 0;
                $box_style = okip_css_vars(array(
                    'okip-vwtb-x'     => okip_css_number(isset($box['x']) ? $box['x'] : 50) . '%',
                    'okip-vwtb-y'     => okip_css_number(isset($box['y']) ? $box['y'] : 50) . '%',
                    'okip-vwtb-w'     => okip_css_number(isset($box['width_pct']) ? $box['width_pct'] : 60) . '%',
                    'okip-vwtb-ff'    => okip_font_stack(isset($box['font_family']) ? $box['font_family'] : ''),
                    'okip-vwtb-fs'    => okip_css_number(isset($box['font_size_px']) ? $box['font_size_px'] : 32) . 'px',
                    'okip-vwtb-fw'    => (string) (int) (isset($box['font_weight']) ? $box['font_weight'] : 400),
                    'okip-vwtb-lh'    => okip_css_number(isset($box['line_height']) ? $box['line_height'] : 1.2),
                    'okip-vwtb-ls'    => okip_css_number(isset($box['letter_spacing']) ? $box['letter_spacing'] : 0) . 'px',
                    'okip-vwtb-color' => isset($box['color']) ? $box['color'] : '#ffffff',
                ));
                if ($b_h > 0) {
                    $box_style .= '--okip-vwtb-h:' . $b_h . 'px;';
                }
                $box_classes = 'okip-vwt__box okip-vwt__box--' . sanitize_html_class($b_role) . ' okip-vwt__box--align-' . sanitize_html_class($b_align);
                $box_tag     = call_user_func($okip_box_tag, $b_role);
                ?>
                <<?php echo $box_tag; ?> class="<?php echo esc_attr($box_classes); ?>" style="<?php echo esc_attr($box_style); ?>"><?php echo esc_html($box['content']); ?></<?php echo $box_tag; ?>>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <!-- Capa 3 (legacy): bloque de texto centrado. -->
        <div class="okip-vwt__inner">
            <div class="okip-vwt__text">
                <?php if ($eyebrow !== '') : ?>
                    <p class="okip-vwt__eyebrow"><?php echo esc_html($eyebrow); ?></p>
                <?php endif; ?>

                <?php if ($title !== '') : ?>
                    <h2 class="okip-vwt__title">
                        <?php
                        // Resaltado seguro: parte el título alrededor de la subcadena.
                        $pos = ($hl !== '') ? stripos($title, $hl) : false;
                        if ($pos !== false) {
                            echo esc_html(substr($title, 0, $pos));
                            echo '<span class="okip-vwt__highlight">' . esc_html(substr($title, $pos, strlen($hl))) . '</span>';
                            echo esc_html(substr($title, $pos + strlen($hl)));
                        } else {
                            echo esc_html($title);
                        }
                        ?>
                    </h2>
                <?php endif; ?>

                <?php if ($subtitle !== '') : ?>
                    <p class="okip-vwt__subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>

                <?php if ($description !== '') : ?>
                    <p class="okip-vwt__desc"><?php echo wp_kses_post($description); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

</section>
