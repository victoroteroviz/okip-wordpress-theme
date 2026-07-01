# OKIP Theme — Mapa de Dependencias Completo

## 1. Dependencias PHP (Server-side)

### Capa Base (Sin dependencias)
```
inc/sanitize.php
├─ okip_one_of()
├─ okip_clamp_int/float()
├─ okip_bool()
├─ okip_is_list()
├─ okip_merge_defaults()
├─ okip_array_diff_recursive()
├─ okip_hex_to_rgba()
├─ okip_normalize_transition()
└─ okip_transition_attrs()
```

### Capa Media
```
inc/media.php (depende: sanitize)
├─ okip_media_url()
├─ okip_media_exists()
└─ okip_media_alt()

inc/design-controls.php (depende: sanitize)
├─ okip_google_fonts_seed_catalog()
├─ okip_google_fonts_catalog()
├─ okip_refresh_google_fonts_catalog()
├─ okip_sanitize_google_font_family()
├─ okip_google_font_meta()
├─ okip_font_stack()
├─ okip_typography_defaults()
├─ okip_normalize_typography()
├─ okip_typography_css_vars()
├─ okip_css_number()
├─ okip_css_vars()
├─ okip_collect_page_google_fonts()
└─ okip_google_fonts_url()

inc/animation-controls.php (depende: sanitize)
├─ okip_motion_ease_options()
├─ okip_motion_stage_defaults()
├─ okip_motion_defaults()
├─ okip_normalize_motion_stage()
├─ okip_normalize_motion()
├─ okip_motion_config_json()
└─ okip_admin_motion_*()
```

### Capa Motor de Bloques
```
inc/blocks.php (depende: sanitize, media)
├─ okip_allowed_blocks()
├─ okip_is_allowed_block()
├─ okip_block_dir()
├─ okip_block_defaults()
├─ okip_normalize_block_data()        ← Llama okip_normalize_{type}_data()
├─ okip_render_block()
├─ okip_render_page()
└─ okip_used_block_types()

inc/block-loader.php (depende: blocks)
├─ okip_asset_version()
└─ okip_enqueue_block_assets()

inc/data.php (depende: blocks, sanitize)
├─ okip_page_config_file()
├─ okip_page_has_config()
├─ okip_get_page_blocks()             ← [PUNTO CRÍTICO] Aplica filtros okip_page_blocks
├─ okip_get_page_block_overrides()
├─ okip_get_page_block_order()
├─ okip_page_block_order_remap()
├─ okip_order_page_blocks()
├─ okip_apply_page_block_order()      ← add_filter('okip_page_blocks', ..., 10)
├─ okip_apply_page_block_overrides()  ← add_filter('okip_page_blocks', ..., 20)
└─ okip_current_page_slug()
```

### Capa Infraestructura
```
inc/setup.php (depende: nada)
└─ okip_setup() → add_action('after_setup_theme', ...)

inc/enqueue.php (depende: blocks, design-controls, animation-controls, block-loader, data)
├─ okip_has_gsap()
├─ okip_has_scrolltrigger()
├─ okip_html_js_class() → add_action('wp_head', ..., 1)
└─ okip_enqueue_assets() → add_action('wp_enqueue_scripts', ...)

inc/nav.php
├─ okip_navbar_config()
└─ okip_nav_menu()
```

### Configuración (Depende de Animation + Design)
```
config/blocks/hero.php (depende: animation-controls, design-controls)
├─ okip_hero_gif_duration_ms()
├─ okip_hero_card_defaults()
├─ okip_normalize_hero_data()
└─ return [ defaults ]

config/blocks/video-w-title.php (depende: animation-controls, design-controls, sanitize)
├─ okip_vwt_text_box_defaults()
├─ okip_normalize_video_w_title_data()
└─ return [ defaults ]

config/blocks/navbar.php
└─ return [ config ]

config/pages/home.php (depende: animation-controls, design-controls)
├─ okip_motion_defaults()
├─ okip_typography_defaults()
└─ return [ bloques ]
```

---

## 2. Dependencias CSS

### Orden de Carga (Cascada)

```
1. tokens.css (variables CSS globales)
   └─ --okip-color-*, --okip-space-*, --okip-font-*, --okip-z-*, etc.

2. base.css (depende: tokens)
   └─ Reset + estilos de documento global

3. layout.css (depende: base)
   └─ Grid, containers, responsive

4. components.css (depende: layout)
   └─ Botones, cajas, cards, navbar

5. transitions.css (depende: components)
   └─ Sistema sticky-cover, sticky-pin

6. animations.css (depende: transitions)
   └─ Keyframes GSAP y CSS animaciones

7. Google Fonts (depende: nada, pero se inyecta en cascada)
   └─ https://fonts.googleapis.com/css2?family=...

8. template-parts/blocks/{type}/style.css (depende: components)
   └─ Estilos específicos de cada bloque
       ├─ template-parts/blocks/hero/style.css
       ├─ template-parts/blocks/video-w-title/style.css
       ├─ template-parts/blocks/industry-carousel/style.css
       └─ etc.
```

### Variables CSS Consumidas

**Desde tokens.css:**
- Colores: `--okip-color-*`
- Espaciado: `--okip-space-*`
- Tipografía: `--okip-font-*`
- Layout: `--okip-container`, `--okip-radius`, `--okip-z-*`

**Generadas Dinámicamente en block.php:**
- `--okip-hero-z`: Calculado por order
- `--okip-hero-title-*`: Desde okip_typography_css_vars()
- `--okip-navbar-*`: Desde navbar.php
- `--okip-card-x`, `--okip-card-y`, `--okip-card-w`: Posición de tarjetas
- `--okip-hold-vh`: Sticky-cover hold height

---

## 3. Dependencias JavaScript

### Orden de Carga

```
1. app.js (sin dependencias)
   ├─ window.OKIP.reduceMotion (from prefers-reduced-motion)
   ├─ window.OKIP.ready(fn)
   ├─ window.OKIP.breakpoints
   ├─ window.OKIP.toArray()
   ├─ window.OKIP.clamp()
   ├─ window.OKIP.readInt()
   ├─ window.OKIP.readFloat()
   └─ window.OKIP.rafThrottle()

2. gsap-init.js (depende: app.js + GSAP si existe)
   ├─ window.gsap (opcional, externa)
   ├─ window.ScrollTrigger (opcional, externa)
   ├─ window.OKIP_ENV (PHP localized)
   └─ Expone:
      ├─ window.okipGsap.ready (boolean)
      └─ window.okipGsap.hasScrollTrigger (boolean)

3. animations.js (depende: gsap-init.js)
   ├─ window.okipGsap.ready
   ├─ window.gsap (opcional)
   ├─ window.OKIP.readInt
   ├─ window.OKIP.readFloat
   └─ Expone:
      ├─ window.OKIPAnimations.create(root, config)
      ├─ window.OKIPAnimations.parseConfig(root)
      └─ window.OKIPAnimations.reduceMotion

4. navbar.js (depende: app.js, animations.js)
   ├─ window.OKIP.ready()
   ├─ window.OKIP.readInt()
   └─ Efectos secundarios en DOM

5. template-parts/blocks/{type}/script.js (depende: app.js, animations.js, navbar.js)
   ├─ window.OKIP.ready()
   ├─ window.OKIPAnimations.create()
   └─ Efectos secundarios en DOM
       ├─ hero/script.js
       ├─ industry-carousel/script.js
       └─ etc.
```

### Dependencias Explícitas (wp_enqueue_script)

```
app.js:
  → Sin dependencies

gsap-init.js:
  → Depende: ['okip-app']
  → Si GSAP: agrega 'gsap', 'gsap-scrolltrigger'

animations.js:
  → Depende: ['okip-gsap-init']

navbar.js:
  → Depende: ['okip-animations']

okip-block-{type}:
  → Depende: ['okip-animations']
```

---

## 4. Acoplamiento entre Bloques

### Explícito (Intencional)

```
1. Hero ↔ Navbar
   - Hero define geometría del bloque siguiente
   - Navbar detecta cobertura del Hero via getBoundingClientRect()
   - Acoplamiento: débil (por geometría, no por variables)

2. Block Order ↔ Z-index
   - okip_render_block() recibe $order
   - Template calcula z = $order + 1
   - Acoplamiento: fuerte (en motor)

3. Motion Config JSON ↔ JavaScript
   - template-parts/blocks/{type}/block.php genera JSON
   - script.js lo parsea y crea animator
   - Acoplamiento: fuerte (por schema)

4. Transition.mode ↔ CSS + JS
   - sticky-cover → CSS puro (assets/css/transitions.css)
   - scrolltrigger-pin → ScrollTrigger JS
   - Acoplamiento: por design (elegir modo en config)

5. Typography ↔ Google Fonts
   - okip_normalize_typography() valida fuentes
   - okip_collect_page_google_fonts() recolecta
   - okip_google_fonts_url() genera URL
   - Acoplamiento: fuerte (pipeline)
```

### Implícito (A Evitar)

```
❌ NO HAY: Bloque depende de otro bloque específico
❌ NO HAY: Orden hard-coded de bloques en CSS/JS
❌ NO HAY: Variable global entre bloques (fuera de OKIP namespace)
✅ BIEN: Cada bloque es independiente, comunica por data-attributes
```

---

## 5. Dependencias Externas

### GSAP (Opcional, Local)

```
Archivos:
  assets/vendor/gsap/gsap.min.js (requerido si GSAP)
  assets/vendor/gsap/ScrollTrigger.min.js (opcional, para pin/scrub)

Verificación:
  inc/enqueue.php → okip_has_gsap() / okip_has_scrolltrigger()
  
Fallback:
  Si no existen → animaciones CSS puro (animations.js fallback)

Uso:
  industry-carousel/script.js → ScrollTrigger para horizontal-pin
  hero/script.js → Opcional, anima si okipGsap.ready
```

### Google Fonts

```
URL dinámica:
  https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Montserrat:wght@300;600

Generado por:
  okip_collect_page_google_fonts(bloques)
  okip_google_fonts_url(fonts)

Inyectado en:
  wp_enqueue_style('okip-google-fonts', url)

Cacheado:
  option_key: okip_google_fonts_catalog
```

### WordPress Native

```
Hooks utilizados:
  after_setup_theme → okip_setup()
  wp_enqueue_scripts → okip_enqueue_assets()
  wp_head → okip_html_js_class()
  okip_allowed_blocks → apply_filters()
  okip_page_blocks → apply_filters() [prio 10, 20]

Funciones WordPress:
  add_theme_support()
  register_nav_menus()
  wp_enqueue_style/script()
  wp_localize_script()
  get_template_part()
  wp_head(), wp_footer(), wp_body_open()
  get_option(), update_option()
  wp_get_attachment_url()
  is_front_page(), is_page()
  get_post_field()
```

---

## 6. Matriz de Dependencias (Vista Completa)

```
┌─ app.js ─────────────────────┐
│                              │
├─ gsap-init.js                │
│ (depende: app, GSAP)         │
│                              │
├─ animations.js               │
│ (depende: gsap-init)         │
│                              │
├─ navbar.js                   │
│ (depende: app, animations)   │
│                              │
└─ blocks/*.js                 │
  (depende: app, animations)   │
                               │
tokens.css ──────────────────┐ │
 ↓                           │ │
base.css ─────────────────────┤ │
 ↓                           │ │
layout.css                    │ │
 ↓                           │ │
components.css                │ │
 ↓                           │ │
transitions.css               │ │
 ↓                           │ │
animations.css                │ │
 ↓                           │ │
blocks/*.css                  │ │
                              │ │
Google Fonts URL ─────────────┘ │
(dinámico)                      │
                                │
wp_localize_script ─────────────┘
(OKIP_ENV)
```

---

## 7. Diagrama de Ciclo de Vida

```
init (functions.php)
  ↓
Setup (after_setup_theme)
  ↓
Enqueue (wp_enqueue_scripts)
  ├─ CSS global en cascada
  ├─ GSAP si existe (file_exists)
  ├─ JS global (app → gsap-init → animations → navbar)
  └─ Assets por bloque (si tipo usado)
  ↓
wp_head() completa
  ↓
Render página (front-page.php)
  ├─ header.php
  ├─ okip_render_page(okip_get_page_blocks($slug))
  │  ├─ Aplica okip_page_blocks filter
  │  ├─ Por cada bloque: okip_render_block()
  │  └─ get_template_part('blocks/{type}/block')
  ├─ footer.php
  └─ wp_footer() inyecta scripts
  ↓
DOMContentLoaded
  ├─ app.js ready
  ├─ gsap-init.js ready
  ├─ animations.js ready
  ├─ navbar.js init
  ├─ blocks/*.js init
  └─ Motion animaciones starts
  ↓
User interacción
  ├─ Scroll → navbar muestra/oculta
  ├─ Hover → tarjeta play
  ├─ Click → menu toggle
  └─ Scroll → animations ejecutan
```

