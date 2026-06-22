<?php

/**
 * Esquema / defaults del bloque Hero.
 *
 * La data de cada instancia (config/pages/*.php) se mezcla sobre estos defaults,
 * así una instancia parcial nunca rompe el render.
 *
 * Whitelists:
 *   background.type  : video | image | svg | gradient   (gradient = fallback)
 *   card.type        : video | image | svg
 *   reveal.strategy  : video_end | delay | canplay
 *
 * Regla del fondo: el fondo real es el MEDIA (video/image/svg) limpio. El
 * gradiente CSS solo actúa como fallback (sin media o si el video falla). El
 * overlay es una capa separada y opcional, no un reemplazo del fondo.
 *
 * Las funciones se declaran ANTES del return (con function_exists) porque el
 * archivo se incluye para obtener su array de defaults.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('okip_hero_card_defaults')) {
    /**
     * Defaults de una sola tarjeta del Hero.
     *
     * @return array
     */
    function okip_hero_card_defaults()
    {
        return array(
            'id'                  => '',
            'active'              => true,
            'type'                => 'image', // video | image | svg
            'media'               => '',
            'poster'              => '',
            'alt'                 => '',
            'x'                   => 50.0,     // % (0..100) — solo desktop
            'y'                   => 50.0,     // % (0..100) — solo desktop
            'autoplay_on_hover'   => true,
            'play_on_tap'         => true,
            'glow'                => true,
            'scanline'            => false,
            'placeholder_label'   => '',      // texto del placeholder temporal
            'placeholder_enabled' => true,    // mostrar placeholder si no hay media real
        );
    }
}

if (! function_exists('okip_normalize_hero_data')) {
    /**
     * Normalizador específico del Hero: valida whitelists y normaliza tarjetas.
     *
     * @param array $data Data ya mezclada con los defaults del bloque.
     * @return array
     */
    function okip_normalize_hero_data($data)
    {
        $align_allowed    = array('left', 'center', 'right');
        $bg_allowed       = array('video', 'image', 'svg', 'gradient');
        $card_allowed     = array('video', 'image', 'svg');
        $strategy_allowed = array('video_end', 'delay', 'canplay');

        // Contenido.
        $data['content']['alignment'] = okip_one_of($data['content']['alignment'], $align_allowed, 'center');

        // Fondo.
        $data['background']['type'] = okip_one_of($data['background']['type'], $bg_allowed, 'gradient');

        // Overlay (capa separada).
        $data['overlay']['enabled'] = okip_bool($data['overlay']['enabled']);
        $data['overlay']['opacity'] = okip_clamp_float($data['overlay']['opacity'], 0, 1);

        // Reveal.
        $data['reveal']['strategy']           = okip_one_of($data['reveal']['strategy'], $strategy_allowed, 'video_end');
        $data['reveal']['image_reveal_delay'] = okip_clamp_int($data['reveal']['image_reveal_delay'], 0, 20000);
        $data['reveal']['video_fail_timeout'] = okip_clamp_int($data['reveal']['video_fail_timeout'], 0, 20000);
        $data['reveal']['cards_reveal_delay'] = okip_clamp_int($data['reveal']['cards_reveal_delay'], 0, 10000);
        $data['reveal']['text_reveal_delay']  = okip_clamp_int($data['reveal']['text_reveal_delay'], 0, 10000);
        $data['reveal']['replay_on_enter']    = okip_bool($data['reveal']['replay_on_enter']);
        $data['reveal']['pause_or_blur_on_fail'] = okip_bool($data['reveal']['pause_or_blur_on_fail']);

        // Animación.
        $data['animation']['enabled']   = okip_bool($data['animation']['enabled']);
        $data['animation']['scroll_3d'] = okip_bool($data['animation']['scroll_3d']);

        // Tarjetas.
        $cards  = isset($data['cards']) && is_array($data['cards']) ? $data['cards'] : array();
        $result = array();
        foreach ($cards as $i => $card) {
            if (! is_array($card)) {
                continue;
            }
            $card = okip_merge_defaults($card, okip_hero_card_defaults());

            $card['id']     = okip_sanitize_instance_id(! empty($card['id']) ? $card['id'] : 'card-' . ($i + 1), 'card-' . ($i + 1));
            $card['active'] = okip_bool($card['active']);
            $card['type']   = okip_one_of($card['type'], $card_allowed, 'image');
            $card['x']      = okip_clamp_float($card['x'], 0, 100);
            $card['y']      = okip_clamp_float($card['y'], 0, 100);
            $card['autoplay_on_hover']   = okip_bool($card['autoplay_on_hover']);
            $card['play_on_tap']         = okip_bool($card['play_on_tap']);
            $card['glow']                = okip_bool($card['glow']);
            $card['scanline']            = okip_bool($card['scanline']);
            $card['placeholder_enabled'] = okip_bool($card['placeholder_enabled']);
            $card['placeholder_label']   = is_string($card['placeholder_label']) ? $card['placeholder_label'] : '';

            $result[] = $card;
        }
        $data['cards'] = $result;

        return $data;
    }
}

return array(
    'content' => array(
        'title_line_1' => '',
        'title_line_2' => '',
        'description'  => '',
        'alignment'    => 'center', // left | center | right
        'max_width'    => '1000px',
    ),
    'background' => array(
        'type'            => 'gradient', // video | image | svg | gradient (gradient = fallback)
        'media'           => '',         // ruta a assets/, URL o ID de attachment
        'poster'          => '',         // imagen de respaldo para video
        'object_position' => 'center center',
    ),
    'overlay' => array(
        'enabled' => true,
        'color'   => '#020711',
        'opacity' => 0.35,  // 0..1 — capa ligera, no reemplaza el fondo
    ),
    'reveal' => array(
        'strategy'              => 'video_end', // video_end | delay | canplay
        'image_reveal_delay'    => 1500,        // ms (image/svg/gradient o strategy=delay)
        'video_fail_timeout'    => 2000,        // ms (si el video no arranca)
        'cards_reveal_delay'    => 200,         // ms tras el fondo
        'text_reveal_delay'     => 200,         // ms tras las tarjetas
        'replay_on_enter'       => true,        // reiniciar la escena al volver al Hero
        'pause_or_blur_on_fail' => true,        // al fallar el video: pausar + degradar el fondo
    ),
    'cards' => array(
        // Lista de tarjetas multimedia flotantes (ver okip_hero_card_defaults()).
    ),
    'animation' => array(
        'enabled'   => true,
        'scroll_3d' => true, // efecto 3D al hacer scroll (ScrollTrigger)
    ),
);
