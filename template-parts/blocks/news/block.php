<?php

/**
 * Bloque News (Bloque 6).
 *
 * Carrusel horizontal de posts nativos con fallback visual cuando no hay posts
 * publicados en la categoría configurada.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

$okip_instance = isset($args['instance_id']) ? $args['instance_id'] : 'news';
$okip_data     = isset($args['data']) && is_array($args['data']) ? $args['data'] : array();

$content  = isset($okip_data['content']) ? $okip_data['content'] : array();
$query    = isset($okip_data['query']) ? $okip_data['query'] : array();
$layout   = isset($okip_data['layout']) ? $okip_data['layout'] : array();
$behavior = isset($okip_data['behavior']) ? $okip_data['behavior'] : array();
$transition = isset($okip_data['transition']) ? $okip_data['transition'] : array();
$fallback = isset($okip_data['fallback_items']) && is_array($okip_data['fallback_items']) ? $okip_data['fallback_items'] : array();

$aria_label = isset($content['aria_label']) ? $content['aria_label'] : 'Noticias y referencias';

$source         = isset($query['source']) ? $query['source'] : 'category';
$category       = isset($query['category']) ? $query['category'] : 'noticias';
$selected_posts = isset($query['selected_posts']) && is_array($query['selected_posts']) ? $query['selected_posts'] : array();
$posts_per_page = isset($query['posts_per_page']) ? (int) $query['posts_per_page'] : 6;
$orderby        = isset($query['orderby']) ? $query['orderby'] : 'date';
$order          = isset($query['order']) ? $query['order'] : 'DESC';

$background     = isset($layout['background']) ? $layout['background'] : '#f6f6f4';
$padding_top    = isset($layout['padding_top']) ? $layout['padding_top'] : '1.45rem';
$padding_bottom = isset($layout['padding_bottom']) ? $layout['padding_bottom'] : '2.55rem';
$card_width     = isset($layout['card_width']) ? $layout['card_width'] : '264px';
$card_height    = isset($layout['card_height']) ? $layout['card_height'] : '190px';
$gap            = isset($layout['gap']) ? $layout['gap'] : '1.35rem';
// z-index raíz por ORDEN de render; layout.z_index>0 = override avanzado (retrocompat).
$z_index        = (isset($layout['z_index']) && (int) $layout['z_index'] > 0)
    ? (int) $layout['z_index']
    : ((isset($args['order']) ? (int) $args['order'] : 0) + 1);

$dots_on   = ! empty($behavior['dots']);
$arrows_on = ! empty($behavior['arrows']);

$reveal_enabled = ! empty($transition['enabled']);
$reveal_disable_below = isset($transition['disable_below']) ? (int) $transition['disable_below'] : 768;
$reveal_start = isset($transition['start']) ? (float) $transition['start'] : .95;
$reveal_end = isset($transition['end']) ? (float) $transition['end'] : .42;
$reveal_mission_lift_vh = isset($transition['mission_lift_vh']) ? (float) $transition['mission_lift_vh'] : 16;

$items = array();
$query_args = array(
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => $posts_per_page,
    'orderby'             => $orderby,
    'order'               => $order,
    'ignore_sticky_posts' => true,
);

if ($source === 'selected' && ! empty($selected_posts)) {
    $query_args['post__in'] = array_map('absint', $selected_posts);
    $query_args['orderby']  = 'post__in';
    $query_args['posts_per_page'] = min($posts_per_page, count($selected_posts));
} elseif ($source === 'selected') {
    $query_args['post__in'] = array(0);
} elseif ($source === 'category' && $category !== '') {
    $query_args['category_name'] = $category;
}

$news_query = new WP_Query($query_args);

if ($news_query->have_posts()) {
    while ($news_query->have_posts()) {
        $news_query->the_post();
        $thumb_id = get_post_thumbnail_id();
        $categories = get_the_category();
        $category_name = '';
        if (! empty($categories) && ! is_wp_error($categories)) {
            $category_name = $categories[0]->name;
        }
        $items[] = array(
            'type'             => 'post',
            'title'            => get_the_title(),
            'category'         => $category_name,
            'url'              => get_permalink(),
            'thumb_url'        => $thumb_id ? wp_get_attachment_image_url($thumb_id, 'large') : '',
            'thumb_alt'        => $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '',
            'placeholder_note' => __('Sin imagen', 'okip'),
        );
    }
    wp_reset_postdata();
} else {
    wp_reset_postdata();
    foreach ($fallback as $item) {
        if (! is_array($item)) {
            continue;
        }
        $image_ref = isset($item['image']) ? $item['image'] : '';
        $image_url = (! empty($image_ref) && okip_media_exists($image_ref)) ? okip_media_url($image_ref) : '';
        $items[] = array(
            'type'             => 'fallback',
            'title'            => isset($item['title']) ? $item['title'] : '',
            'category'         => isset($item['category']) ? $item['category'] : '',
            'url'              => isset($item['url']) ? $item['url'] : '',
            'thumb_url'        => $image_url,
            'thumb_alt'        => isset($item['alt']) ? $item['alt'] : '',
            'placeholder_note' => isset($item['placeholder_note']) ? $item['placeholder_note'] : 'Placeholder',
        );
    }
}

if (empty($items)) {
    return;
}

$section_style = sprintf(
    '--okip-news-bg:%s;--okip-news-pt:%s;--okip-news-pb:%s;--okip-news-card-w:%s;--okip-news-card-h:%s;--okip-news-gap:%s;--okip-news-z:%d;',
    esc_attr($background),
    esc_attr($padding_top),
    esc_attr($padding_bottom),
    esc_attr($card_width),
    esc_attr($card_height),
    esc_attr($gap),
    $z_index
);
?>
<section
    id="<?php echo esc_attr($okip_instance); ?>"
    class="okip-news<?php echo $reveal_enabled ? ' okip-news--cover' : ''; ?>"
    data-block-instance="<?php echo esc_attr($okip_instance); ?>"
    data-okip-news
    data-reveal="<?php echo $reveal_enabled ? '1' : '0'; ?>"
    data-reveal-disable-below="<?php echo esc_attr((string) $reveal_disable_below); ?>"
    data-reveal-start="<?php echo esc_attr((string) $reveal_start); ?>"
    data-reveal-end="<?php echo esc_attr((string) $reveal_end); ?>"
    data-reveal-mission-lift-vh="<?php echo esc_attr((string) $reveal_mission_lift_vh); ?>"
    style="<?php echo $section_style; ?>">

    <div class="okip-news__viewport" aria-label="<?php echo esc_attr($aria_label); ?>" data-okip-news-track>
        <ul class="okip-news__track" role="list">
            <?php foreach ($items as $idx => $item) :
                $is_post = isset($item['type']) && $item['type'] === 'post';
                $title   = isset($item['title']) ? $item['title'] : '';
                $category = isset($item['category']) ? $item['category'] : '';
                $url     = isset($item['url']) ? $item['url'] : '';
                $thumb_url = isset($item['thumb_url']) ? $item['thumb_url'] : '';
                $thumb_alt = isset($item['thumb_alt']) ? $item['thumb_alt'] : '';
                $placeholder_note = ! empty($item['placeholder_note']) ? $item['placeholder_note'] : 'Placeholder';
                $card_classes = 'okip-news__card' . ($is_post ? ' okip-news__card--post' : ' okip-news__card--fallback');
                $card_label   = $title !== '' ? $title : ('Referencia ' . ($idx + 1));
                ?>
                <li class="okip-news__item" data-okip-news-item>
                    <div class="okip-news__card-shell">
                        <?php if ($url !== '') : ?>
                            <a class="<?php echo esc_attr($card_classes); ?>" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr($card_label); ?>">
                        <?php else : ?>
                            <div class="<?php echo esc_attr($card_classes); ?>" aria-label="<?php echo esc_attr($card_label); ?>">
                        <?php endif; ?>

                            <span class="okip-news__media">
                                <?php if ($thumb_url !== '') : ?>
                                    <img
                                        class="okip-news__image"
                                        src="<?php echo esc_url($thumb_url); ?>"
                                        alt="<?php echo esc_attr($thumb_alt !== '' ? $thumb_alt : $title); ?>"
                                        loading="lazy">
                                <?php else : ?>
                                    <span class="okip-news__placeholder">
                                        <span class="okip-news__placeholder-label"><?php echo esc_html($placeholder_note); ?></span>
                                    </span>
                                <?php endif; ?>

                                <?php if ($category !== '') : ?>
                                    <span class="okip-news__category">
                                        <span class="okip-news__category-icon" aria-hidden="true">
                                            <svg viewBox="0 0 28 20" focusable="false">
                                                <path d="M14 1.5c5.8 0 10.3 4.4 12.2 8.5-1.9 4.1-6.4 8.5-12.2 8.5S3.7 14.1 1.8 10C3.7 5.9 8.2 1.5 14 1.5Z" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
                                                <circle cx="14" cy="10" r="3.4" fill="currentColor" />
                                            </svg>
                                        </span>
                                        <span class="okip-news__category-text"><?php echo esc_html($category); ?></span>
                                    </span>
                                <?php endif; ?>
                            </span>

                            <?php if ($title !== '') : ?>
                                <span class="okip-news__body">
                                    <span class="okip-news__title"><?php echo esc_html($title); ?></span>
                                </span>
                            <?php endif; ?>

                        <?php if ($url !== '') : ?>
                            </a>
                        <?php else : ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="okip-news__controls">
        <?php if ($arrows_on) : ?>
            <button class="okip-news__nav okip-news__nav--prev" type="button" data-okip-news-prev aria-label="<?php echo esc_attr__('Noticia anterior', 'okip'); ?>">
                <span class="okip-news__nav-icon" aria-hidden="true"></span>
            </button>
        <?php endif; ?>

        <?php if ($dots_on) : ?>
            <div class="okip-news__dots" aria-label="<?php echo esc_attr__('Paginación de noticias', 'okip'); ?>" data-okip-news-dots>
                <?php foreach ($items as $idx => $_item) : ?>
                    <button
                        class="okip-news__dot<?php echo $idx === 0 ? ' is-active' : ''; ?>"
                        type="button"
                        data-okip-news-dot="<?php echo esc_attr((string) $idx); ?>"
                        aria-label="<?php echo esc_attr(sprintf(__('Ir a referencia %d', 'okip'), $idx + 1)); ?>"
                        aria-current="<?php echo $idx === 0 ? 'true' : 'false'; ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($arrows_on) : ?>
            <button class="okip-news__nav okip-news__nav--next" type="button" data-okip-news-next aria-label="<?php echo esc_attr__('Siguiente noticia', 'okip'); ?>">
                <span class="okip-news__nav-icon" aria-hidden="true"></span>
            </button>
        <?php endif; ?>
    </div>
</section>
