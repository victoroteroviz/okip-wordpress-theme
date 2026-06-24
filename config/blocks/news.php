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
            'title' => '',
            'url'   => '',
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

        // Fallback items.
        $item_defaults = okip_news_fallback_item_defaults();
        if (! empty($data['fallback_items']) && is_array($data['fallback_items'])) {
            $items = array();
            foreach ($data['fallback_items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $merged          = array_merge($item_defaults, $item);
                $merged['title'] = sanitize_text_field((string) $merged['title']);
                $merged['url']   = esc_url_raw((string) $merged['url']);
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
            'title' => 'Referencia 1',
            'url'   => '',
        ),
        array(
            'title' => 'Referencia 2',
            'url'   => '',
        ),
        array(
            'title' => 'Referencia 3',
            'url'   => '',
        ),
    ),
    'layout' => array(
        'background'     => '#f6f6f4',
        'padding_top'    => '1.45rem',
        'padding_bottom' => '2.55rem',
        'card_width'     => '264px',
        'card_height'    => '190px',
        'gap'            => '1.35rem',
        'z_index'        => 6,
    ),
    'behavior' => array(
        'dots'   => true,
        'arrows' => true,
    ),
);
