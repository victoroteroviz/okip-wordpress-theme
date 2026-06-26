<?php

/**
 * Esquema / defaults del bloque News (Bloque 6).
 *
 * Carrusel claro de noticias/referencias. Usa posts nativos por categoría y,
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

        // Behavior.
        $data['behavior']['dots']   = okip_bool($data['behavior']['dots']);
        $data['behavior']['arrows'] = okip_bool($data['behavior']['arrows']);

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
    'fallback_items' => array(
        array(
            'title'    => 'Seguridad mundialista en México',
            'category' => 'Nacional',
            'image'    => 'img/news/new-1.png',
            'alt'      => 'Elementos de Guardia Nacional en un operativo de seguridad',
            'url'      => '',
        ),
        array(
            'title'    => 'Seguridad democrática ¿qué es?',
            'category' => 'Formación',
            'image'    => 'img/news/new-2.png',
            'alt'      => 'Equipo revisando un mapa de seguridad en un centro de monitoreo',
            'url'      => '',
        ),
        array(
            'title'    => 'La feria más segura de México',
            'category' => 'Caso de éxito',
            'image'    => 'img/news/new-3.png',
            'alt'      => 'Feria iluminada durante la noche con juegos mecánicos y visitantes',
            'url'      => '',
        ),
        array(
            'title'            => 'Nueva referencia en preparación',
            'category'         => 'Placeholder',
            'image'            => '',
            'alt'              => '',
            'placeholder_note' => 'Placeholder',
            'url'              => '',
        ),
    ),
    'layout' => array(
        'background'     => '#ffffff',
        'padding_top'    => '3rem',
        'padding_bottom' => '3.35rem',
        'card_width'     => 'clamp(300px, 28vw, 520px)',
        'card_height'    => 'clamp(330px, 32vw, 500px)',
        'gap'            => '14px',
        'z_index'        => 6,
    ),
    'behavior' => array(
        'dots'   => true,
        'arrows' => true,
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
