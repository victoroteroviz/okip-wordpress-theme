<?php

/**
 * Esquema / defaults del footer del sitio (ref `referencias/image.png`).
 *
 * Footer oscuro: logo (imagen) a la izquierda, columnas de enlaces, bloque de
 * redes sociales ("Contáctanos") y línea legal abajo a la derecha.
 *
 * Se consume directamente con okip_block_defaults('footer') desde
 * template-parts/layout/footer-site.php (no pasa por el motor de bloques).
 * Mientras no exista el panel admin, las URLs son placeholders ('#').
 *
 * - logo:    imagen (ruta a assets/, URL o ID de attachment) + alt de respaldo.
 * - columns: lista de columnas de enlaces (title + links[label,url]).
 * - social:  título + redes (network ∈ facebook|instagram|linkedin|youtube, url).
 * - legal:   línea inferior (cookies, copyright con %s para el año, términos).
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

return array(
    'logo' => array(
        'image' => 'img/okip-logo-footer.png', // ruta a assets/, URL o ID de attachment.
        'alt'   => 'OKIP',
    ),
    'columns' => array(
        array(
            'title' => 'Servicios',
            'links' => array(
                array('label' => 'COVIA',              'url' => '#'),
                array('label' => 'GIA',                'url' => '#'),
                array('label' => 'RIA',                'url' => '#'),
                array('label' => 'Agentes y escoltas', 'url' => '#'),
                array('label' => 'Vigilancia aérea',   'url' => '#'),
                array('label' => 'Vigilancia CCTV',    'url' => '#'),
            ),
        ),
        array(
            'title' => 'Nosotros',
            'links' => array(
                array('label' => 'Quiénes somos',  'url' => '#'),
                array('label' => 'Sé parte',       'url' => '#'),
                array('label' => 'Nuestro equipo', 'url' => '#'),
                array('label' => 'Misión',         'url' => '#'),
            ),
        ),
    ),
    'social' => array(
        'title' => 'Contáctanos',
        'links' => array(
            array('network' => 'facebook',  'url' => '#', 'label' => 'Facebook'),
            array('network' => 'instagram', 'url' => '#', 'label' => 'Instagram'),
            array('network' => 'linkedin',  'url' => '#', 'label' => 'LinkedIn'),
            array('network' => 'youtube',   'url' => '#', 'label' => 'YouTube'),
        ),
    ),
    'legal' => array(
        'cookies_label'     => 'Gestionar preferencias de cookies',
        'cookies_url'       => '#',
        // %s se sustituye por el año actual (date_i18n) en el template.
        'copyright_format'  => 'Copyright © %s Okip, S.A de C.V. Todos los derechos reservados',
        'terms_label'       => 'Términos de uso y Política de privacidad',
        'terms_url'         => '#',
    ),
);
