<?php

/**
 * SVG inline del Hero: mapa tecnológico de México.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

$okip_svg_uid = isset($args['instance_id']) ? sanitize_html_class($args['instance_id']) : 'okip-hero';
$okip_grad_bg = $okip_svg_uid . '-mx-bg';
$okip_grad_map = $okip_svg_uid . '-mx-map';
$okip_filter_glow = $okip_svg_uid . '-mx-glow';
?>
<svg class="okip-hero-map" viewBox="0 0 1600 900" role="img" aria-label="<?php esc_attr_e('Mapa digital de México con red de inteligencia', 'okip'); ?>" preserveAspectRatio="xMidYMid slice">
    <defs>
        <radialGradient id="<?php echo esc_attr($okip_grad_bg); ?>" cx="48%" cy="42%" r="78%">
            <stop offset="0%" stop-color="var(--okip-hero-svg-accent)" stop-opacity=".18" />
            <stop offset="42%" stop-color="var(--okip-hero-svg-bg)" stop-opacity=".88" />
            <stop offset="100%" stop-color="#00040a" stop-opacity="1" />
        </radialGradient>
        <linearGradient id="<?php echo esc_attr($okip_grad_map); ?>" x1="18%" y1="12%" x2="86%" y2="82%">
            <stop offset="0%" stop-color="var(--okip-hero-svg-accent-2)" stop-opacity=".72" />
            <stop offset="48%" stop-color="var(--okip-hero-svg-accent)" stop-opacity=".42" />
            <stop offset="100%" stop-color="var(--okip-hero-svg-accent-2)" stop-opacity=".58" />
        </linearGradient>
        <filter id="<?php echo esc_attr($okip_filter_glow); ?>" x="-40%" y="-40%" width="180%" height="180%">
            <feGaussianBlur stdDeviation="5" result="blur" />
            <feColorMatrix in="blur" type="matrix" values="0 0 0 0 0  0 0 0 0 .72  0 0 0 0 1  0 0 0 .75 0" result="glow" />
            <feMerge>
                <feMergeNode in="glow" />
                <feMergeNode in="SourceGraphic" />
            </feMerge>
        </filter>
        <pattern id="<?php echo esc_attr($okip_svg_uid); ?>-grid" width="80" height="80" patternUnits="userSpaceOnUse">
            <path d="M80 0H0V80" fill="none" stroke="var(--okip-hero-svg-accent)" stroke-opacity=".22" stroke-width="1" />
        </pattern>
    </defs>

    <rect class="okip-hero-map__base" width="1600" height="900" fill="url(#<?php echo esc_attr($okip_grad_bg); ?>)" />
    <rect class="okip-hero-map__grid" width="1600" height="900" fill="url(#<?php echo esc_attr($okip_svg_uid); ?>-grid)" />

    <g class="okip-hero-map__ambient" aria-hidden="true">
        <circle cx="330" cy="190" r="150" />
        <circle cx="1130" cy="640" r="190" />
        <circle cx="1320" cy="250" r="120" />
    </g>

    <g class="okip-hero-map__land" filter="url(#<?php echo esc_attr($okip_filter_glow); ?>)">
        <path class="okip-hero-map__baja" d="M219 126c50 20 82 48 111 87 32 44 58 86 104 118 17 12 22 28 11 42-14 17-43 10-77-20-64-56-110-116-151-182-18-29-25-48-18-58 4-6 11-4 20 13Z" />
        <path class="okip-hero-map__main" d="M327 139c75 2 144 15 205 44 55 26 90 66 135 91 41 23 80 8 122 30 44 23 54 73 104 92 36 14 72 8 105 26 53 30 61 97 119 126 47 23 99 8 145 34 62 35 73 96 128 127 39 22 90 18 124 48-52 30-116 35-173 9-48-22-90-64-144-75-61-13-111-8-161-52-34-30-58-75-105-83-44-8-72 18-116-7-51-29-75-80-128-100-43-17-89-7-128-33-46-30-58-84-103-116-41-29-90-34-132-61-49-32-74-81-81-141 27-20 57-29 84-29Z" />
        <path class="okip-hero-map__yucatan" d="M1303 601c52-17 111-12 163 12 24 11 43 26 57 47-35 25-83 38-130 34-51-5-81-34-113-64 4-13 12-23 23-29Z" />
    </g>

    <g class="okip-hero-map__routes" aria-hidden="true">
        <path d="M373 231C520 246 587 343 704 387S904 405 1030 511 1200 639 1395 651" />
        <path d="M473 326C618 298 759 304 884 394s208 167 338 203" />
        <path d="M582 198C665 287 700 379 824 456s225 82 328 166" />
        <path d="M322 171C454 214 552 271 639 357s144 149 243 177" />
        <path d="M875 392C954 330 1052 292 1162 284s197 7 294 52" />
    </g>

    <g class="okip-hero-map__nodes" aria-hidden="true">
        <circle cx="355" cy="224" r="4" />
        <circle cx="430" cy="285" r="3" />
        <circle cx="520" cy="250" r="5" />
        <circle cx="602" cy="348" r="4" />
        <circle cx="705" cy="386" r="6" />
        <circle cx="785" cy="310" r="4" />
        <circle cx="866" cy="424" r="5" />
        <circle cx="948" cy="472" r="4" />
        <circle cx="1046" cy="519" r="7" />
        <circle cx="1124" cy="600" r="4" />
        <circle cx="1226" cy="596" r="5" />
        <circle cx="1358" cy="650" r="6" />
        <circle cx="1450" cy="642" r="4" />
        <circle cx="466" cy="367" r="3" />
        <circle cx="640" cy="232" r="3" />
        <circle cx="1012" cy="365" r="3" />
    </g>

    <g class="okip-hero-map__particles" aria-hidden="true">
        <circle class="p p1" cx="240" cy="650" r="2.2" />
        <circle class="p p2 alt" cx="330" cy="134" r="1.8" />
        <circle class="p p3" cx="522" cy="540" r="2.4" />
        <circle class="p p4 alt" cx="644" cy="162" r="1.7" />
        <circle class="p p5" cx="785" cy="690" r="2" />
        <circle class="p p6 alt" cx="912" cy="240" r="2.2" />
        <circle class="p p7" cx="1098" cy="736" r="1.8" />
        <circle class="p p8 alt" cx="1190" cy="340" r="2.6" />
        <circle class="p p9" cx="1336" cy="196" r="2" />
        <circle class="p p10 alt" cx="1432" cy="760" r="2.4" />
        <circle class="p p11" cx="154" cy="378" r="1.8" />
        <circle class="p p12 alt" cx="1515" cy="440" r="2" />
    </g>

    <g class="okip-hero-map__hud" aria-hidden="true">
        <rect x="1212" y="106" width="228" height="128" rx="4" />
        <path d="M1240 198h172M1240 178h78M1332 178h80M1240 158h132" />
        <circle cx="1262" cy="136" r="18" />
        <path d="M1262 118v36M1244 136h36M1262 136l13-12" />
        <path class="okip-hero-map__hud-wave" d="M1316 204c15-29 31-29 46 0s31 29 47 0" />
        <rect x="1326" y="124" width="85" height="8" rx="2" />
        <rect x="1326" y="142" width="58" height="8" rx="2" />
    </g>
</svg>
