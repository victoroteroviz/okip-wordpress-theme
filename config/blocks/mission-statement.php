<?php

/**
 * Esquema / defaults del bloque Mission Statement (Bloque 5).
 *
 * Sección institucional oscura con texto centrado y gradiente azul animado desde
 * la base. En el futuro admin, el fondo podrá reemplazarse por imagen, video o
 * svg; por ahora el gradiente es el fallback principal.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('okip_ms_sanitize_hex')) {
    /**
     * Sanitiza un color hex con fallback.
     *
     * @param mixed  $value
     * @param string $fallback
     * @return string
     */
    function okip_ms_sanitize_hex($value, $fallback)
    {
        $color = function_exists('sanitize_hex_color') ? sanitize_hex_color((string) $value) : '';
        return $color ? $color : $fallback;
    }
}

if (! function_exists('okip_normalize_mission_statement_data')) {
    /**
     * Normalizador específico del bloque Mission Statement.
     *
     * @param array $data Data ya mezclada con los defaults.
     * @return array
     */
    function okip_normalize_mission_statement_data($data)
    {
        // Content.
        $lines = array();
        if (! empty($data['content']['lines']) && is_array($data['content']['lines'])) {
            foreach ($data['content']['lines'] as $line) {
                $line = sanitize_text_field((string) $line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }
        $data['content']['lines']       = $lines;
        $data['content']['strong_line'] = sanitize_text_field((string) $data['content']['strong_line']);
        $data['content']['kicker']      = sanitize_text_field((string) $data['content']['kicker']);

        // Background.
        $data['background']['mode']  = okip_one_of($data['background']['mode'], array('gradient', 'image', 'video', 'svg'), 'gradient');
        $data['background']['media'] = sanitize_text_field((string) $data['background']['media']);

        $g = $data['background']['gradient'];
        $g['dark_color']  = okip_ms_sanitize_hex($g['dark_color'], '#000000');
        $g['blue_color']  = okip_ms_sanitize_hex($g['blue_color'], '#006fcf');
        $g['intensity']   = okip_clamp_float($g['intensity'], 0, 1);
        $g['blue_glow']   = okip_hex_to_rgba($g['blue_color'], $g['intensity'], '#006fcf');
        $g['blue_soft']   = okip_hex_to_rgba($g['blue_color'], $g['intensity'] * 0.36, '#006fcf');
        $g['duration_ms'] = okip_clamp_int($g['duration_ms'], 0, 30000);
        $g['x']           = okip_clamp_int($g['x'], 0, 100);
        $g['y']           = okip_clamp_int($g['y'], 0, 120);
        $data['background']['gradient'] = $g;

        // Layout.
        $data['layout']['z_index'] = okip_clamp_int($data['layout']['z_index'], 0, 50);

        // Animation.
        $data['animation']['enabled'] = okip_bool($data['animation']['enabled']);
        $data['animation']['text']    = okip_one_of($data['animation']['text'], array('fade-up', 'stagger-lines', 'reveal', 'none'), 'fade-up');

        return $data;
    }
}

return array(
    'content' => array(
        'lines' => array(
            'Tecnología 100% mexicana desarrollada por',
            'talento nacional con la misión de hacer de',
            'latinoamérica un territorio más',
        ),
        'strong_line' => 'inteligente, conectado e independiente',
        'kicker'      => 'CREANDO ENTORNOS SEGUROS',
    ),
    'background' => array(
        'mode'     => 'gradient',
        'media'    => '',
        'gradient' => array(
            'dark_color'  => '#000000',
            'blue_color'  => '#006fcf',
            'duration_ms' => 6500,
            'intensity'   => 0.82,
            'x'           => 50,
            'y'           => 104,
        ),
    ),
    'layout' => array(
        'padding_top'    => '7rem',
        'padding_bottom' => '6.5rem',
        'content_width'  => '820px',
        'z_index'        => 5,
    ),
    'animation' => array(
        'enabled' => true,
        'text'    => 'fade-up',
    ),
);
