<?php

/**
 * Shell de cabecera: <head>, wp_head() y navbar global.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class('okip-body'); ?>>
    <?php wp_body_open(); ?>

    <a class="okip-skip-link" href="#okip-content"><?php esc_html_e('Saltar al contenido', 'okip'); ?></a>

    <?php get_template_part('template-parts/layout/navbar'); ?>

    <main id="okip-content" class="okip-main" role="main">
