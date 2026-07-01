<?php

/**
 * Esquema / defaults del bloque News (Bloque 6).
 *
 * Grilla editorial de noticias/referencias. Usa posts nativos por categoría y,
 * cuando no hay contenido, conserva la composición con placeholders.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('okip_news_fallback_item_defaults')) {
    /**
     * Defaults de un placeholder de noticia.
     *
     * @return array
     */
    function okip_news_fallback_item_defaults()
    {
        return array(
            'title'            => '',
            'category'         => '',
            'image'            => '',
            'alt'              => '',
            'placeholder_note' => 'Placeholder',
            'url'              => '',
            // Variante de maqueta (tamaño + presentación). Vacío = por posición
            // (patrón en block.php). Whitelist: text | feature | wide | mini.
            'variant'          => '',
        );
    }
}

if (! function_exists('okip_normalize_news_data')) {
    /**
     * Normalizador específico del bloque News.
     *
     * @param array $data Data ya mezclada con los defaults.
     * @return array
     */
    function okip_normalize_news_data($data)
    {
        // Content.
        $data['content']['aria_label'] = sanitize_text_field((string) $data['content']['aria_label']);

        // Query.
        $data['query']['source']         = okip_one_of($data['query']['source'], array('category', 'latest', 'selected'), 'category');
        $data['query']['category']       = sanitize_title((string) $data['query']['category']);
        $data['query']['posts_per_page'] = okip_clamp_int($data['query']['posts_per_page'], 1, 12);
        $data['query']['orderby']        = okip_one_of($data['query']['orderby'], array('date', 'title', 'menu_order'), 'date');
        $data['query']['order']          = okip_one_of(strtoupper((string) $data['query']['order']), array('ASC', 'DESC'), 'DESC');
        $data['query']['post_ids']       = array();
        if (! empty($data['query']['selected_posts']) && is_array($data['query']['selected_posts'])) {
            foreach ($data['query']['selected_posts'] as $post_id) {
                $post_id = absint($post_id);
                if ($post_id > 0) {
                    $data['query']['post_ids'][] = $post_id;
                }
            }
        }
        $data['query']['selected_posts'] = $data['query']['post_ids'];

        // Layout.
        $data['layout']['z_index'] = okip_clamp_int($data['layout']['z_index'], 0, 50);

        // Behavior (retrocompatibilidad; el layout desktop ya no usa controles).
        $data['behavior']['dots']   = okip_bool($data['behavior']['dots']);
        $data['behavior']['arrows'] = okip_bool($data['behavior']['arrows']);

        // Card entry animation: reveal individual, one-shot, configurable.
        $a = isset($data['animation']) && is_array($data['animation']) ? $data['animation'] : array();
        $a['enabled']       = okip_bool(isset($a['enabled']) ? $a['enabled'] : true);
        $a['duration_ms']   = okip_clamp_int(isset($a['duration_ms']) ? $a['duration_ms'] : 620, 0, 5000);
        $a['delay_ms']      = okip_clamp_int(isset($a['delay_ms']) ? $a['delay_ms'] : 80, 0, 10000);
        $a['stagger_ms']    = okip_clamp_int(isset($a['stagger_ms']) ? $a['stagger_ms'] : 95, 0, 3000);
        $a['translate_y']   = okip_clamp_int(isset($a['translate_y']) ? $a['translate_y'] : 22, 0, 160);
        $a['threshold']     = okip_clamp_float(isset($a['threshold']) ? $a['threshold'] : .16, .01, 1);
        $a['disable_below'] = okip_clamp_int(isset($a['disable_below']) ? $a['disable_below'] : 0, 0, 9999);
        $data['animation']  = $a;

        // Transition (cover-rise: News sube y cubre a Mission con fade + parallax).
        $t = isset($data['transition']) && is_array($data['transition']) ? $data['transition'] : array();
        $t['enabled']       = okip_bool(isset($t['enabled']) ? $t['enabled'] : true);
        $t['disable_below'] = okip_clamp_int(isset($t['disable_below']) ? $t['disable_below'] : 768, 0, 9999);
        $t['start']         = okip_clamp_float(isset($t['start']) ? $t['start'] : .95, .1, 1.4);
        $t['end']           = okip_clamp_float(isset($t['end']) ? $t['end'] : .42, 0, 1.2);
        if ($t['start'] <= $t['end']) {
            $t['start'] = min(1.4, $t['end'] + .25);
        }
        $t['mission_lift_vh'] = okip_clamp_float(isset($t['mission_lift_vh']) ? $t['mission_lift_vh'] : 16, 0, 80);
        $data['transition'] = $t;

        // Fallback items.
        $item_defaults = okip_news_fallback_item_defaults();
        if (! empty($data['fallback_items']) && is_array($data['fallback_items'])) {
            $items = array();
            foreach ($data['fallback_items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $merged          = array_merge($item_defaults, $item);
                $merged['title']            = sanitize_text_field((string) $merged['title']);
                $merged['category']         = sanitize_text_field((string) $merged['category']);
                $merged['image']            = sanitize_text_field((string) $merged['image']);
                $merged['alt']              = sanitize_text_field((string) $merged['alt']);
                $merged['placeholder_note'] = sanitize_text_field((string) $merged['placeholder_note']);
                $merged['url']              = esc_url_raw((string) $merged['url']);
                $merged['variant']          = okip_one_of((string) $merged['variant'], array('text', 'feature', 'wide', 'mini'), '');
                $items[] = $merged;
            }
            $data['fallback_items'] = $items;
        } else {
            $data['fallback_items'] = array();
        }

        return $data;
    }
}

return array(
    'content' => array(
        'aria_label' => 'Noticias y referencias',
    ),
    'query' => array(
        'source'         => 'category', // category | latest | selected
        'category'       => 'noticias',
        'selected_posts' => array(),    // IDs de posts para el futuro panel admin.
        'posts_per_page' => 6,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ),
    // Maqueta tipo "bento" editorial (ref referencias/image.png). El orden y la
    // variante definen el acomodo: text (solo texto), feature (imagen grande,
    // título debajo), wide (imagen ancha, título superpuesto), mini (imagen
    // chica, título debajo). Sin media real → placeholder/gris (media-driven).
    'fallback_items' => array(
        array(
            'variant'  => 'text',
            'title'    => 'Presentamos OKIP Labs',
            'category' => 'Investigación',
            'image'    => '',
            'alt'      => '',
            'url'      => '',
        ),
        array(
            'variant'  => 'feature',
            'title'    => 'Cómo las instituciones despliegan inteligencia que sí funciona',
            'category' => 'Institucional',
            'image'    => 'img/news/new-1.png',
            'alt'      => 'Centro de monitoreo institucional con analistas frente a pantallas',
            'url'      => '',
        ),
        array(
            'variant'  => 'feature',
            'title'    => 'La siguiente fase de la política de seguridad en México',
            'category' => 'Sector público',
            'image'    => 'img/news/new-2.png',
            'alt'      => 'Edificio institucional bajo un cielo despejado',
            'url'      => '',
        ),
        array(
            'variant'  => 'mini',
            'title'    => 'OKIP y aliados unen fuerzas para modernizar el borde táctico',
            'category' => 'Sector público',
            'image'    => 'img/news/new-3.png',
            'alt'      => 'Operativo táctico en terreno abierto',
            'url'      => '',
        ),
        array(
            'variant'  => 'mini',
            'title'    => 'Inteligencia confiable para el futuro de la salud',
            'category' => 'Salud',
            'image'    => 'img/news/new-4.png',
            'alt'      => 'Personal médico caminando por un pasillo hospitalario',
            'url'      => '',
        ),
        array(
            'variant'  => 'mini',
            'title'    => 'Elevando el estándar de la seguridad democrática',
            'category' => 'Investigación',
            'image'    => 'img/news/new-5.png',
            'alt'      => 'Panel de métricas y evaluación de desempeño',
            'url'      => '',
        ),
        array(
            'variant'  => 'wide',
            'title'    => 'Pavimentando el futuro digital de la seguridad nacional',
            'category' => 'Institucional',
            'image'    => 'img/news/new-6.png',
            'alt'      => 'Vista aérea de una ciudad con infraestructura moderna',
            'url'      => '',
        ),
        array(
            'variant'  => 'mini',
            'title'    => 'Ampliando el motor de datos para la inteligencia física',
            'category' => 'Producto',
            'image'    => 'img/news/new-7.png',
            'alt'      => 'Brazo robótico operando en un entorno controlado',
            'url'      => '',
        ),
    ),
    'layout' => array(
        'background'     => '#ffffff',
        'padding_top'    => '3rem',
        'padding_bottom' => '3.35rem',
        'grid_max_width' => '1120px',
        'card_width'     => 'min(82vw, 350px)', // Mobile horizontal cards.
        'card_height'    => 'min(112vw, 430px)',
        'gap'            => '8px',
        'z_index'        => 0, // 0 = z-index automático por orden de render (override si >0)
    ),
    'behavior' => array(
        'dots'   => false,
        'arrows' => false,
    ),
    'animation' => array(
        'enabled'       => true,
        'duration_ms'   => 620,
        'delay_ms'      => 80,
        'stagger_ms'    => 95,
        'translate_y'   => 22,
        'threshold'     => .16,
        'disable_below' => 0,
    ),
    'transition' => array(
        'enabled'       => true,
        'disable_below' => 768,
        // Cover-rise: progreso del "depth-out" de Mission según el top de News.
        // start = News top entra (~95vh, casi al fondo) → p=0;
        // end   = News top sube hasta ~42vh → p=1 (Mission ya atenuada/escalada).
        'start'         => .95,
        'end'           => .42,
        // Cuánto se "aleja" Mission al ser cubierta (lift en vh). Sutil = más limpio.
        'mission_lift_vh' => 16,
    ),
);
