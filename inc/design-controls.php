<?php

/**
 * Controles de diseño compartidos: tipografía, variables CSS y Google Fonts.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Catálogo local inicial de Google Fonts. El panel permite escribir/buscar libremente,
 * pero este catálogo da sugerencias rápidas y pesos conocidos sin consultar red.
 *
 * @return array<int,array<string,mixed>>
 */
function okip_google_fonts_seed_catalog()
{
    $families = array(
        array('family' => 'Inter', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Roboto', 'weights' => array(300, 400, 500, 700, 900)),
        array('family' => 'Montserrat', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Poppins', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Manrope', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Space Grotesk', 'weights' => array(300, 400, 500, 600, 700)),
        array('family' => 'Archivo', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'IBM Plex Sans', 'weights' => array(300, 400, 500, 600, 700)),
        array('family' => 'DM Sans', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Plus Jakarta Sans', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Urbanist', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Sora', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Outfit', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Raleway', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Nunito Sans', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Lato', 'weights' => array(300, 400, 700, 900)),
        array('family' => 'Open Sans', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Source Sans 3', 'weights' => array(300, 400, 500, 600, 700, 800, 900)),
        array('family' => 'Work Sans', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Figtree', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Geist', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Barlow', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Exo 2', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Rajdhani', 'weights' => array(300, 400, 500, 600, 700)),
        array('family' => 'Orbitron', 'weights' => array(400, 500, 600, 700, 800, 900)),
        array('family' => 'Oxanium', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Titillium Web', 'weights' => array(300, 400, 600, 700, 900)),
        array('family' => 'Rubik', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Noto Sans', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Noto Sans Display', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Libre Franklin', 'weights' => array(300, 400, 500, 600, 700, 800)),
        array('family' => 'Afacad', 'weights' => array(400, 500, 600, 700)),
    );

    foreach ($families as $i => $font) {
        $families[$i]['label'] = $font['family'];
    }

    return $families;
}

/**
 * Catálogo de fuentes para el panel.
 *
 * @return array<int,array<string,mixed>>
 */
function okip_google_fonts_catalog()
{
    // Cache en proceso: evita repetir get_option (y deserializar el blob, que con
    // el catálogo de API puede ser grande) en cada normalización tipográfica.
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $stored = get_option('okip_google_fonts_catalog');
    $cache = (is_array($stored) && ! empty($stored)) ? $stored : okip_google_fonts_seed_catalog();
    return $cache;
}

/**
 * Refresca el catálogo. Si existe OKIP_GOOGLE_FONTS_API_KEY usa la API oficial;
 * si no, restablece el catálogo local seed.
 *
 * @return int Cantidad de fuentes disponibles.
 */
function okip_refresh_google_fonts_catalog()
{
    $catalog = array();

    if (defined('OKIP_GOOGLE_FONTS_API_KEY') && OKIP_GOOGLE_FONTS_API_KEY) {
        $url = add_query_arg(
            array(
                'key'  => OKIP_GOOGLE_FONTS_API_KEY,
                'sort' => 'popularity',
            ),
            'https://www.googleapis.com/webfonts/v1/webfonts'
        );
        $response = wp_remote_get($url, array('timeout' => 12));
        if (! is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (! empty($body['items']) && is_array($body['items'])) {
                foreach ($body['items'] as $item) {
                    if (empty($item['family'])) {
                        continue;
                    }
                    $weights = array();
                    if (! empty($item['variants']) && is_array($item['variants'])) {
                        foreach ($item['variants'] as $variant) {
                            if ($variant === 'regular') {
                                $weights[] = 400;
                            } elseif (preg_match('/^([1-9]00)$/', (string) $variant, $m)) {
                                $weights[] = (int) $m[1];
                            }
                        }
                    }
                    $weights = array_values(array_unique(array_filter($weights)));
                    $catalog[] = array(
                        'family'  => okip_sanitize_google_font_family($item['family']),
                        'label'   => sanitize_text_field($item['family']),
                        'weights' => ! empty($weights) ? $weights : array(400, 700),
                    );
                }
            }
        }
    }

    if (empty($catalog)) {
        $catalog = okip_google_fonts_seed_catalog();
    }

    update_option('okip_google_fonts_catalog', $catalog, false);
    return count($catalog);
}

/**
 * Limpia un nombre de fuente de Google Fonts.
 *
 * @param mixed $family Valor recibido.
 * @return string
 */
function okip_sanitize_google_font_family($family)
{
    $family = trim(str_replace(array('"', "'", '\\'), '', (string) $family));
    $family = preg_replace('/[^A-Za-z0-9 _-]/', '', $family);
    $family = preg_replace('/\s+/', ' ', $family);
    return substr(trim((string) $family), 0, 80);
}

/**
 * Devuelve metadatos conocidos para una fuente, permitiendo valores libres seguros.
 *
 * @param mixed $family
 * @return array<string,mixed>
 */
function okip_google_font_meta($family)
{
    $family = okip_sanitize_google_font_family($family);
    if ($family === '' || strtolower($family) === 'system') {
        return array(
            'family'  => 'system',
            'label'   => 'System',
            'weights' => array(300, 400, 500, 600, 700, 800),
            'google'  => false,
        );
    }

    // Mapa indexado por familia en minúsculas: lookup O(1) en vez de recorrer el
    // catálogo entero en cada normalización tipográfica.
    static $index = null;
    if ($index === null) {
        $index = array();
        foreach (okip_google_fonts_catalog() as $font) {
            $known = isset($font['family']) ? okip_sanitize_google_font_family($font['family']) : '';
            if ($known === '') {
                continue;
            }
            $index[strtolower($known)] = array(
                'family'  => $known,
                'label'   => isset($font['label']) ? $font['label'] : $known,
                'weights' => ! empty($font['weights']) && is_array($font['weights']) ? array_map('intval', $font['weights']) : array(400, 700),
                'google'  => true,
            );
        }
    }
    $key = strtolower($family);
    if (isset($index[$key])) {
        return $index[$key];
    }

    // Familia libre (no en catálogo): permitir el rango completo de pesos.
    // Google Fonts CSS2 sirve el peso más cercano disponible, así que no se
    // descarta la elección del usuario (100/200/900 incluidos).
    return array(
        'family'  => $family,
        'label'   => $family,
        'weights' => array(100, 200, 300, 400, 500, 600, 700, 800, 900),
        'google'  => true,
    );
}

/**
 * Familia CSS segura con fallback.
 *
 * @param string $family
 * @return string
 */
function okip_font_stack($family)
{
    $family = okip_sanitize_google_font_family($family);
    if ($family === '' || strtolower($family) === 'system') {
        return 'var(--okip-font-base)';
    }
    return '"' . esc_attr($family) . '", var(--okip-font-base)';
}

/**
 * Defaults tipográficos por preset.
 *
 * @param string $preset
 * @return array<string,mixed>
 */
function okip_typography_defaults($preset = 'body')
{
    $defaults = array(
        'font_family'    => 'system',
        'google_family'  => '',
        'font_weight'    => 400,
        'min_px'         => 16,
        'fluid_vw'       => 2,
        'max_px'         => 20,
        'line_height'    => 1.4,
        'letter_spacing' => 0,
        'color'          => '',
    );

    if ($preset === 'hero_title') {
        $defaults = array_merge($defaults, array(
            'font_family' => 'Montserrat',
            'font_weight' => 300,
            'min_px'      => 42,
            'fluid_vw'    => 5.2,
            'max_px'      => 78,
            'line_height' => 1.08,
            'color'       => '#ffffff',
        ));
    } elseif ($preset === 'hero_description') {
        $defaults = array_merge($defaults, array(
            'font_family' => 'Inter',
            'font_weight' => 400,
            'min_px'      => 16,
            'fluid_vw'    => 1.8,
            'max_px'      => 22,
            'line_height' => 1.5,
            'color'       => '#d9e8f7',
        ));
    } elseif ($preset === 'kicker') {
        $defaults = array_merge($defaults, array(
            'font_weight'    => 600,
            'min_px'         => 11,
            'fluid_vw'       => .9,
            'max_px'         => 13,
            'line_height'    => 1.3,
            'letter_spacing' => 1.8,
        ));
    }

    return $defaults;
}

/**
 * Normaliza un grupo tipográfico.
 *
 * @param mixed  $data
 * @param string $preset
 * @return array<string,mixed>
 */
function okip_normalize_typography($data, $preset = 'body')
{
    $data = okip_merge_defaults(is_array($data) ? $data : array(), okip_typography_defaults($preset));

    $family = '';
    if (! empty($data['font_family'])) {
        $family = okip_sanitize_google_font_family($data['font_family']);
    } elseif (! empty($data['google_family'])) {
        $family = okip_sanitize_google_font_family($data['google_family']);
    }

    $meta = okip_google_font_meta($family);
    $weights = ! empty($meta['weights']) ? $meta['weights'] : array(400, 700);

    $data['font_family'] = $meta['family'];
    $data['google_family'] = ! empty($meta['google']) ? $meta['family'] : '';
    $data['font_weight'] = okip_clamp_int($data['font_weight'], 100, 900);
    if (! in_array((int) $data['font_weight'], $weights, true)) {
        $nearest = $weights[0];
        foreach ($weights as $weight) {
            if (abs($weight - $data['font_weight']) < abs($nearest - $data['font_weight'])) {
                $nearest = $weight;
            }
        }
        $data['font_weight'] = (int) $nearest;
    }

    $data['min_px'] = okip_clamp_float($data['min_px'], 10, 140);
    $data['fluid_vw'] = okip_clamp_float($data['fluid_vw'], .1, 12);
    $data['max_px'] = okip_clamp_float($data['max_px'], $data['min_px'], 180);
    $data['line_height'] = okip_clamp_float($data['line_height'], .8, 2.4);
    $data['letter_spacing'] = okip_clamp_float($data['letter_spacing'], 0, 12);
    $data['color'] = sanitize_hex_color((string) $data['color']) ?: '';

    return $data;
}

/**
 * Crea declaraciones CSS custom-property para un grupo tipográfico.
 *
 * @param string $prefix Prefijo sin `--`, por ejemplo okip-hero-title.
 * @param array  $typography Grupo normalizado.
 * @return string
 */
function okip_typography_css_vars($prefix, array $typography)
{
    $prefix = preg_replace('/[^a-z0-9_-]/i', '', $prefix);
    if ($prefix === '') {
        return '';
    }

    $vars = array(
        $prefix . '-font-family'    => okip_font_stack($typography['font_family']),
        $prefix . '-font-size'      => sprintf('clamp(%spx, %svw, %spx)', okip_css_number($typography['min_px']), okip_css_number($typography['fluid_vw']), okip_css_number($typography['max_px'])),
        $prefix . '-font-weight'    => (string) (int) $typography['font_weight'],
        $prefix . '-line-height'    => okip_css_number($typography['line_height']),
        $prefix . '-letter-spacing' => okip_css_number($typography['letter_spacing']) . 'px',
    );
    if (! empty($typography['color'])) {
        $vars[$prefix . '-color'] = $typography['color'];
    }

    return okip_css_vars($vars);
}

/**
 * Número CSS compacto.
 *
 * @param mixed $value
 * @return string
 */
function okip_css_number($value)
{
    return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
}

/**
 * Imprime un mapa de variables CSS de forma segura.
 *
 * @param array<string,string> $vars
 * @return string
 */
function okip_css_vars(array $vars)
{
    $out = '';
    foreach ($vars as $name => $value) {
        $name = preg_replace('/[^a-z0-9_-]/i', '', (string) $name);
        if ($name === '') {
            continue;
        }
        $out .= '--' . $name . ':' . esc_attr((string) $value) . ';';
    }
    return $out;
}

/**
 * Recolecta las fuentes de Google usadas por una página.
 *
 * @param array $blocks
 * @return array<string,int[]>
 */
function okip_collect_page_google_fonts($blocks)
{
    $fonts = array();
    $walk = function ($value) use (&$walk, &$fonts) {
        if (! is_array($value)) {
            return;
        }
        if (isset($value['font_family']) || isset($value['google_family'])) {
            $family = okip_sanitize_google_font_family(! empty($value['google_family']) ? $value['google_family'] : $value['font_family']);
            if ($family !== '' && strtolower($family) !== 'system') {
                $weight = isset($value['font_weight']) ? okip_clamp_int($value['font_weight'], 100, 900) : 400;
                if (! isset($fonts[$family])) {
                    $fonts[$family] = array();
                }
                $fonts[$family][] = $weight;
                $fonts[$family][] = 400;
            }
        }
        foreach ($value as $child) {
            $walk($child);
        }
    };
    if (is_array($blocks)) {
        foreach ($blocks as $block) {
            if (is_array($block) && ! empty($block['type'])) {
                $type = sanitize_key($block['type']);
                $data = isset($block['data']) && is_array($block['data']) ? $block['data'] : array();
                $walk(okip_normalize_block_data($type, $data));
            } else {
                $walk($block);
            }
        }
    }

    foreach ($fonts as $family => $weights) {
        $fonts[$family] = array_values(array_unique(array_map('intval', $weights)));
        sort($fonts[$family]);
    }

    return $fonts;
}

/**
 * URL única para Google Fonts CSS2.
 *
 * @param array<string,int[]> $fonts
 * @return string
 */
function okip_google_fonts_url(array $fonts)
{
    if (empty($fonts)) {
        return '';
    }

    $families = array();
    foreach ($fonts as $family => $weights) {
        $family = okip_sanitize_google_font_family($family);
        if ($family === '' || strtolower($family) === 'system') {
            continue;
        }
        $weights = array_values(array_unique(array_map('intval', $weights)));
        $weights = array_filter($weights, function ($weight) {
            return $weight >= 100 && $weight <= 900;
        });
        sort($weights);
        $query_family = str_replace('%20', '+', rawurlencode($family));
        if (! empty($weights)) {
            $query_family .= ':wght@' . implode(';', $weights);
        }
        $families[] = $query_family;
    }

    if (empty($families)) {
        return '';
    }

    return 'https://fonts.googleapis.com/css2?' . implode('&', array_map(function ($family) {
        return 'family=' . $family;
    }, $families)) . '&display=swap';
}
