
# OKIP Theme — Contexto del proyecto

Documento de transferencia para retomar el desarrollo en una sesión nueva.
Léelo primero: explica qué es el tema, cómo está construido y dónde va cada cosa.

> **Idioma:** código en inglés, contenido visible en español.
> **No** uses Gutenberg, ACF, page builders ni CDNs.

---

## 1. Qué es

Tema **clásico de WordPress**, escalable y **multipágina**, con un **sistema propio
de bloques modulares**. La Home se arma como una lista ordenada de bloques. No es
solo una landing: la arquitectura soporta varias páginas (Contacto, Sala de prensa,
Fábrica de tecnologías, etc.).

- ⚠️ **Ruta real del tema en ESTE entorno de desarrollo: `www/wp-content/themes/okip-theme`.**
  La ruta histórica `okip-wordpress-theme` que se citaba aquí **no existe en este entorno**;
  el tema vivo y completo es `okip-theme`. Trabaja siempre dentro de `okip-theme`.
- Referencias visuales (PNG, NO código): `www/wp-content/themes/okip-theme/referencias/`
  - `navbar.png`, `bloque 1.png` (Hero) … `bloque 6.png`, `idea panel 1/2.png` (panel admin futuro)

---

## 2. Entorno (Docker)

WordPress corre en contenedor; el host **no** tiene PHP/WP-CLI.

| Cosa | Valor |
|---|---|
| Contenedor WP | `okip_landing_wordpress` (PHP 8.x) |
| Sitio | http://localhost:8080/ |
| WP version | 7.0 · tema activo: `okip-theme` (ruta real de este entorno) |
| Host tools | `node`, `python3`, `jq`, `docker` |

Comandos de verificación:
```bash
# Lint PHP (en el contenedor)
docker exec okip_landing_wordpress sh -c 'cd /var/www/html/wp-content/themes/okip-theme && for f in $(find . -name "*.php" -not -path "./.git/*"); do php -l "$f"; done'

# Sintaxis JS (en el host)
node --check assets/js/navbar.js

# Smoke HTTP
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/

# Probar funciones con WP cargado
docker exec okip_landing_wordpress php -r 'define("WP_USE_THEMES",false);require"/var/www/html/wp-load.php";var_dump(okip_get_page_blocks("home"));'
```

---

## 3. Decisiones cerradas

1. Tema clásico, sin Gutenberg/ACF/page builders.
2. Home desde `front-page.php`. Arquitectura multipágina.
3. Una página = **lista ordenada de instancias de bloque**.
4. Cada instancia: `{ type, instance_id, data }`.
5. `instance_id` **manual, legible y estable** (ej. `home-hero-main`, `home-video-w-title`).
   Sirve de ancla (`/#home-hero-main`), scope CSS/JS y futura clave de guardado.
6. Un mismo `type` puede repetirse con distinto `instance_id` y `data`.
7. Datos hoy en `config/` (PHP). Mañana el admin guardará **overrides en `wp_options`**
   vía el filtro `okip_page_blocks` — **nunca** escribir datos editables en archivos del tema.
8. GSAP + ScrollTrigger **locales** en `assets/vendor/gsap/`. Si no existen, el sitio
   **no se rompe** (todo tiene fallback). Nunca CDN.
9. Fondos **media-driven**: si no hay media real, **fallback neutro** (color sólido),
   nunca un diseño decorativo falso (sin gradiente/patrón/fake glow/fake map encima del media).
10. Noticias (futuro): posts nativos por categoría `noticias` / `sala-de-prensa`.

---

## 4. Estructura

```
okip-theme/
├── style.css                 # cabecera del tema
├── functions.php             # bootstrap: define constantes, requiere inc/
├── front-page.php            # HOME → okip_render_page(okip_get_page_blocks('home'))
├── page.php                  # busca config/pages/{slug}.php; si no, the_content()
├── index.php / single.php / 404.php
├── header.php / footer.php   # shell + navbar + main + wp_head/wp_footer
├── page-templates/
│   └── template-blocks.php   # "Página por bloques (OKIP)" (Template Name)
├── template-parts/
│   ├── layout/               # navbar.php, footer-site.php
│   ├── pages/                # (vacío por ahora)
│   └── blocks/<type>/        # block.php + style.css + script.js (autocontenido)
│       ├── hero/
│       └── video-w-title/
├── config/
│   ├── blocks/<type>.php     # DEFAULTS + normalizador del tipo
│   └── pages/<slug>.php      # lista ordenada de instancias de la página
├── inc/
│   ├── setup.php             # add_theme_support, register_nav_menus('primary')
│   ├── enqueue.php           # CSS/JS global + GSAP condicional + OKIP_ENV
│   ├── blocks.php            # whitelist, render, normalize (MOTOR)
│   ├── block-loader.php      # encola CSS/JS por bloque usado (filemtime, sin dups)
│   ├── data.php              # okip_get_page_blocks(), okip_current_page_slug()
│   ├── nav.php               # menú primary o fallback de config
│   ├── sanitize.php          # helpers de saneo + okip_merge_defaults()
│   ├── media.php             # okip_media_url(), okip_media_exists()
│   └── admin/                # STUBS (sin funcionalidad): admin-pages, fields,
│                             #   media-fields, sanitizers
└── assets/
    ├── css/                  # tokens, base, layout, components (globales)
    ├── js/                   # app.js, gsap-init.js, navbar.js (globales)
    ├── img/ video/ svg/      # media real (HOY VACÍO → todo cae a fallback)
    └── vendor/gsap/          # gsap.min.js + ScrollTrigger.min.js (GSAP 3.15.0 instalado)
```

**Regla:** `inc/` = lógica · `config/` = datos+esquema · `template-parts/blocks/<type>/` =
presentación autocontenida · `assets/` = recursos globales.

---

## 5. Motor de bloques (cómo funciona)

Flujo de render (`inc/blocks.php`, `inc/data.php`):
```
config/pages/{slug}.php  →  okip_get_page_blocks($slug)   [+filtro okip_page_blocks (futuro admin)]
   → okip_render_page($list)
      → por instancia: okip_render_block($type, $instance_id, $data)
         → valida type en okip_allowed_blocks() (whitelist; bloquea PHP arbitrario)
         → okip_normalize_block_data($type,$data) = merge defaults + okip_normalize_{type}_data()
         → get_template_part('template-parts/blocks/'.$type.'/block', null, $args)
            $args = ['type','instance_id','data']  (data ya normalizada)
```

- **Whitelist:** `okip_allowed_blocks()` en `inc/blocks.php`. Hoy: `hero`, `video-w-title`,
  `industry-carousel`, `product-story`, `mission-statement`, `news`.
- **Merge:** `okip_merge_defaults($data,$defaults)` (recursivo; las **listas** se
  reemplazan, no se fusionan índice a índice — importante para `cards`, etc.).
- **Normalizador por tipo:** función opcional `okip_normalize_{type}_data($data)`
  (guiones del type → guiones bajos). Valida whitelists, clamps y booleans.
  Se declara **dentro de `config/blocks/{type}.php`, ANTES del `return`** y con
  `function_exists()` (el archivo se incluye varias veces).
- **Assets por bloque:** `inc/block-loader.php` encola `template-parts/blocks/{type}/style.css`
  y `script.js` **solo si existen** y solo para los tipos usados en la página actual.
  Versionado con `filemtime()`. Dedupe por handle `okip-block-{type}`.
  El JS de bloque depende de `okip-gsap-init`; el CSS de `okip-components`.

### Añadir un bloque nuevo
1. `inc/blocks.php`: añade el `type` a `okip_allowed_blocks()`.
2. `config/blocks/{type}.php`: `return` con defaults + (opcional) `okip_normalize_{type}_data()`.
3. `template-parts/blocks/{type}/block.php` (+ `style.css`, `script.js` opcionales).
4. Añade la instancia a `config/pages/{slug}.php`.
   No hay que tocar el motor ni el enqueue.

---

## 6. Convenciones

| Elemento | Convenio | Ejemplo |
|---|---|---|
| Funciones PHP | `okip_`, snake_case | `okip_render_block()` |
| Handles WP | `okip-` | `okip-navbar`, `okip-block-hero` |
| Clases CSS (BEM) | `okip-<bloque>__<el>--<mod>` | `okip-hero__title-line` |
| Estado CSS (JS) | `is-` | `is-hidden`, `is-revealed`, `is-bg-failed` |
| Data attrs | `data-okip-*` / kebab | `data-okip-hero`, `data-block-instance` |

- **Escapar siempre la salida:** `esc_html`, `esc_url`, `esc_attr`, `wp_kses_post`.
- **Scope por instancia:** raíz del bloque con `id="{instance_id}"` +
  `data-block-instance="{instance_id}"`. El JS itera `querySelectorAll('[data-okip-*]')`
  y se protege con un flag `__okip<Block>Init` para no duplicar listeners.
- **Sin CSS inline** salvo variables seguras estrictamente necesarias
  (ej. `--okip-card-x`, `object-position`, opacidades ya clampadas).
- Accesibilidad: `aria-label`, `aria-expanded`/`aria-controls` (hamburguesa),
  navegación por teclado, `prefers-reduced-motion`, skip link.

---

## 7. GSAP (local, condicional) — YA INSTALADO

- **Presente:** `assets/vendor/gsap/{gsap.min.js,ScrollTrigger.min.js}` (GSAP **3.15.0**).
- `inc/enqueue.php` comprueba `file_exists()` y solo encola GSAP/ScrollTrigger si existen.
  Pasa el estado al front por `wp_localize_script` → `window.OKIP_ENV.{hasGsap,hasScrollTrigger}`.
- `assets/js/gsap-init.js` registra ScrollTrigger si está y expone
  `window.okipGsap.{ready, hasScrollTrigger}` (hoy ambos `true`).
- `assets/js/app.js` expone `window.OKIP.{ready(fn), reduceMotion}`.
- **Regla en cada bloque:** si `okipGsap.ready` (+ `hasScrollTrigger`) → animar con
  GSAP/ScrollTrigger; si no → **fallback vanilla** (rAF + lerp / clases CSS / IO).
  El fallback DEBE mantenerse funcional (no romper si GSAP no carga). Respetar `reduceMotion`.

---

## 8. Comportamiento del Navbar

Config en `config/blocks/navbar.php` (grupo `reveal`): `reveal_mode`
(`after_hero|always|manual`), `reveal_offset`, `hide_on_hero`, `use_intersection_observer`.
También define el **menú de respaldo** (si no hay menú `primary` asignado en WP) y el logo.

- **Menú real:** hoy hay un menú asignado a `primary` → se renderiza `wp_nav_menu`
  (NO el fallback). `inc/nav.php` añade `okip-navbar__link` a los `<a>` de `wp_nav_menu`
  vía filtro `nav_menu_link_attributes`. El CSS estiliza por `.okip-navbar__menu a`
  (+ `current-menu-item/current_page_item/current-menu-ancestor > a` para el subrayado
  activo), así funciona con `wp_nav_menu` Y con el fallback.
- **Estilo (ref `navbar.png`):** full-bleed (grid `auto 1fr auto`: logo izq · menú
  centrado · hamburguesa der), fondo casi sólido + blur sutil (tokens `--okip-navbar-*`),
  altura slim 68px, subrayado en el activo.
- **Hamburguesa:** OCULTA en desktop; visible solo en `@media (max-width:1024px)`
  (mismo breakpoint que el menú móvil colapsable).
- **Visibilidad (`assets/js/navbar.js`):** en Home con Hero (`after_hero`+`hide_on_hero`)
  el navbar nace oculto (`okip-navbar--start-hidden` server-side, gated por `.okip-js`) y se
  re-ancla al **bloque que cubre al Hero** (`coverBlock = querySelector('[data-okip-vwt],
  [data-okip-pm]')`, hoy `video-w-title`). Aparece cuando `coverBlock.getBoundingClientRect().top`
  cae bajo el **15% superior del viewport** (el bloque tapa ~85% del Hero) y se oculta al volver.
  No se mide el Hero (es sticky → `rect.top` engañoso). **Perf:** UNA lectura de layout por frame
  (el rect del coverBlock); la geometría del Hero (solo para el fallback sin coverBlock) se cachea
  y recalcula en resize, NO por frame, para evitar reflows forzados. Si el coverBlock es el legacy
  `parallax-monitor` (`data-okip-pm`), sigue su sync `okip:pm-cover`/`is-pm-covered` en su lugar.
  Al ocultarse cierra el menú móvil (`aria-expanded=false`). `is-scrolled` (scrollY>8) solo cambia
  el fondo, no la visibilidad.
- `okip-js` se inyecta en `wp_head` prioridad 1 (antes del `<body>`) → sin parpadeo.

---

## 9. Bloques implementados

### Hero (`hero`) — instancia `home-hero-main` · ref `bloque 1.png`
Capas: **1) background media limpio (video|image|svg)** → **2) overlay opcional** →
**3) tarjetas flotantes** → **4) texto central**.

- **Media-driven:** fondo solo si el media existe (`okip_media_exists`); si no →
  `okip-hero__bg--missing` = color sólido `#05080f` (sin gradiente/glow falso).
- **Tarjetas (ACTUALIZADO):** se renderizan desde config aunque NO haya media real.
  Con `active` + `type` válido: si hay media → se pinta; si no → **placeholder
  geométrico** (`okip-hero__card--placeholder`, label `placeholder_label`). Al añadir
  media real existente, sustituye al placeholder. (`placeholder_enabled=false` + sin
  media → no se pinta esa tarjeta.) Hoy en Home hay 3 tarjetas placeholder.
- **Tarjetas — reproducción (sin autoplay):** el `<video>` de tarjeta es
  `muted loop playsinline preload="none"` SIN `autoplay`; la secuencia de entrada
  NO llama `play()`. Solo se activa por interacción: `play_mode` = `hover|tap|manual`
  (`setupCards()`). `reset_on_leave=false` (default) → al salir del hover NO reinicia
  (continúa). Placeholder (sin `<video>`) se ignora sin error.
- **Secuencia de entrada (JS) — escena dual-video:**
  1. **Intro** (`background.intro_media`): se reproduce UNA vez; tarjetas y texto permanecen ocultos.
  2. Al terminar (o al fallar antes de `intro.fail_timeout`, 2500 ms): crossfade al **loop**
     (`background.loop_media`) que queda en bucle continuo (clase `is-loop-visible`).
  3. Tras el crossfade: revela tarjetas (`reveal.cards_delay_after_intro`, default 300 ms)
     y después texto (`reveal.text_delay_after_intro`, default 600 ms).
  - Sin intro + sin loop (image/svg/missing): revela tras `reveal.image_reveal_delay` (default 1000 ms).
  - Si no hay loop/fallback: fondo neutro (`is-bg-failed`). Nunca rompe.
  - La escena **no se reinicia** al volver al Hero: el loop sigue vivo y el contenido
    permanece visible. (`replay_on_enter` fue eliminado en la simplificación del modelo.)
- **Scroll 3D** (`animation.scroll_3d`): existe pero está **DESACTIVADO en Home**
  (`config/pages/home.php` → hero `animation.scroll_3d=false`) para que el hundimiento
  del Hero lo controle el Bloque 2 (evita doble transform con GSAP). En otras páginas
  sigue disponible.
- Config: `config/blocks/hero.php`. Grupos: `content`, `background`, `intro`, `loop`,
  `overlay`, `reveal`, `transition`, `cards` (lista; defaults en `okip_hero_card_defaults()` —
  incluye `placeholder_label/placeholder_enabled`, `play_mode/reset_on_leave`), `animation`.
- Texto actual: "Inteligencia mexicana" / "al servicio de la humanidad".

### Video con Título (`video-w-title`) — instancia `home-video-w-title` · ref `bloque 2.png`
**Sustituye al antiguo `parallax-monitor`** en la misma posición (entre Hero e Industry
Carousel). Escena secundaria casi full-screen (`min-height:100svh`): video de fondo a sangre
completa + overlay para legibilidad + bloque de texto centrado. **Sin parallax/drift/cover ni
las 3 capas de reveal** del antiguo B2 (eliminados), pero **conserva los DOS overlaps de
traspaso**: el Hero sigue `position:sticky` (desktop, z1) y este bloque (z2, fondo opaco) lo
cubre por flujo al entrar; y a la salida **se auto-pinea (HOLD-PIN)** para que Industry Carousel
(z3) suba desde la base y lo cubra, igual que el traspaso Hero→bloque.

- **3 capas por z-index** (sin layers de parallax): `.okip-vwt__bg` (video, z1) →
  `.okip-vwt__overlay` (z2) → `.okip-vwt__inner` con `.okip-vwt__text` (z3).
- **Media-driven:** el video solo se pinta si el media existe (`okip_media_exists`); sin media
  → **fallback sobrio** = color sólido (`--okip-color-bg`, sin gradiente/patrón/glow falso).
  Video default: `assets/video/video-w-title/background.mp4` (no existe aún → fallback).
- **Título con resaltado:** `highlighted_text` envuelto en `.okip-vwt__highlight` = **negrita
  blanca** (NO color), escapado con el mismo patrón `stripos`/`substr` del Hero. `subtitle` =
  kicker uppercase letterspaced; `eyebrow` y `description` opcionales.
- **Animación de entrada (reveal):** 100% CSS gated por `.okip-js` (`.okip-vwt--animated` →
  `opacity:0`/`translateY`; `.is-revealed` la dispara, escalonado por `nth-child`). El
  `script.js` (`setupReveal`) solo añade `.is-revealed` por IntersectionObserver y es
  **defensivo**: sin IO, con `data-anim=0`, o `prefers-reduced-motion` → revela de inmediato.
- **Overlap de salida (HOLD-PIN, `setupOverlap`):** solo desktop + GSAP+ScrollTrigger. La
  sección se auto-pinea (`pin:true, pinSpacing:false`) durante su propia altura (`+=offsetHeight`,
  = la distancia que el bloque siguiente recorre hasta el top) → queda FIJA mientras Industry
  Carousel (z3, opaco) sube por encima. **NO** escribe transforms sobre otros bloques ni empuja
  con `margin-top` (el antiguo B2 sí, por su coreografía depth-entry; aquí no hace falta). Gated
  por `data-overlap`/`data-overlap-bp` (≤bp, reduce-motion o sin GSAP → flujo apilado, sin pin;
  el resize a ≤bp mata el pin). Requiere `nextElementSibling`. Flag `__okipVwtInit` evita doble init.
- **Navbar:** este bloque NO emite el sync `okip:pm-cover` del antiguo B2. `navbar.js` re-ancla
  el reveal a ESTE bloque (selector `[data-okip-vwt], [data-okip-pm]`): aparece cuando su
  `getBoundingClientRect().top` cae bajo el 15% superior del viewport (tapa ~85% del Hero) y se
  mantiene mientras lo cubra. Una sola lectura de layout por frame; la geometría del Hero (solo
  para el fallback sin bloque-cubierta) se cachea y recalcula en resize, no en cada frame.
- Config: `config/blocks/video-w-title.php`. Grupos: `content` (`eyebrow`, `title`,
  `highlighted_text`, `subtitle`, `description`), `video` (`media`, `poster`, `autoplay`,
  `loop`, `muted`, `playsinline`), `overlay` (`enabled`, `color`, `opacity`), `layout`
  (`min_height=100svh`, `content_width`, `z_index=2`, `alignment` = `left|center`),
  `animation` (`enabled`, `disable_below`, `overlap_enabled=true`, `overlap_breakpoint=1024`).
  Normalizador: `okip_normalize_video_w_title_data()`.
- Contenido actual: title "Facilitando la **toma de decisiones** en tiempo real" (highlight
  "toma de decisiones"), subtitle "Monitoreo, gestión e inteligencia operativa", sin eyebrow,
  descripción ni CTA. Alignment `center`.
- **Migración del orden del admin (opción B):** `okip_get_page_block_order()` remapea el
  `instance_id` viejo `home-parallax-monitor` → `home-video-w-title` (vía
  `okip_page_block_order_remap()`, filtrable) para que un orden guardado antiguo conserve la
  posición en lugar de anexar el bloque nuevo al final.

### Industry Carousel (`industry-carousel`) — instancia `home-industry-carousel` · ref `bloque 3.png`
Sección con **fondo claro** (blanco/gris muy claro) — opuesto al Bloque 2. Estructura visual:
texto centrado arriba + cinta de imágenes a ancho completo abajo.

**Layout (ref `bloque 3.png`):**
- `heading_main` en uppercase bold centrado ("ECOSISTEMAS DE SEGURIDAD")
- `heading_sub` debajo en peso normal ("físicos y virtuales a la medida")
- Texto naranja en su **propia línea centrada grande** (NO inline en el heading); cambia con el ítem activo
- Botón CTA pequeño bajo el naranja ("SABER MÁS")
- Cinta de imágenes full-width al fondo; sin texto debajo de cada tarjeta

**Tarjetas:**
- Activa: a color, escala mayor (`scale(1.08)`), centrada en viewport
- Inactivas: escala de grises (`filter: grayscale(0.85)`), escala menor (`scale(0.92)`)
- Proporción landscape ancha (4/3 aprox.), sin borde card body

**Scroll-driven (desktop, GSAP):**
- **Un solo ScrollTrigger** (`icId-pin`): `pin:true`, `pinSpacing:true`, `scrub:1`
- `start: 'top top'`, `end` calculado por la distancia real para centrar del primer al último ítem:
  `end = Math.abs(firstItemCenterX - lastItemCenterX)` (via `invalidateOnRefresh`)
- Índice activo: `Math.round(progress * (itemCount - 1))` (no `Math.floor`)
- Track empieza con el primer ítem centrado; termina con el último ítem centrado
- **SIN** ST separado de overlay (causaba conflicto con el pin)

**Fallback / móvil ≤1024px:** modo `is-static`, scroll horizontal nativo, IO actualiza activo.

**Config:** `config/blocks/industry-carousel.php`. Grupos: `content` (`heading_main`, `heading_sub`,
`cta_label`, `cta_url`, `eyebrow`), `items` (lista; cada ítem: `title`, `orange_text`,
`description`, `image`, `alt`, `video`), `animation` (`enabled`, `pin_enabled`,
`scroll_distance_vh`, `disable_below`, `scrub`).

---

## 10. Estado y roadmap

**Hecho (Fase 1A + 1B + pulido Hero/Bloque 2):**
- Motor de bloques, config por página, carga condicional de assets.
- **GSAP 3.15.0 instalado local** (`assets/vendor/gsap/`), con fallback vanilla intacto.
- Navbar: full-bleed estilo `navbar.png`, oculto-en-Hero por progreso de scroll (con guard
  `offsetHeight<=0`), hamburguesa solo ≤1024px, estilos por `.okip-navbar__menu a`
  (funciona con `wp_nav_menu`), subrayado activo.
- Hero media-driven con escena dual-video (intro→crossfade→loop); **tarjetas con placeholder**
  (sin media) y reproducción solo por hover/tap via `play_mode` (sin autoplay).
- **Bloque 2 — `video-w-title`** (sustituye al antiguo `parallax-monitor`, eliminado): escena
  de video de fondo + overlay + texto centrado (título con highlight negrita + subtítulo). Sin
  parallax/pin/cover: el Hero sticky (z1) lo cubre por flujo, Industry Carousel (z3) cubre
  después. Migración del orden del admin por remap de `instance_id` (opción B). El diseño
  cinematográfico previo (monitor/glow/parallax) vive en el historial de git.
- Páginas placeholder: `config/pages/{contacto,sala-de-prensa,fabrica-de-tecnologias}.php`
  (devuelven `[]` → fallback `the_content()`).

> **Pendiente de verificación VISUAL en navegador** (no se puede automatizar): suavidad
> de la transición con GSAP, navbar oculto en Hero / visible en Bloque 2, hamburguesa por
> breakpoint, tarjetas sin autoplay. Recargar con Ctrl+Shift+R por caché de CSS/JS.

**En progreso:**
- Bloque 3 `industry-carousel` → `home-industry-carousel`: implementado, **pendiente verificación visual**.

**Pendiente (NO implementado aún):**
- Bloque 4 Storytelling productos (`bloque 4.png` + video): scroll narrativo.
- Bloque 5 Mensaje institucional (`bloque 5.png`): frase centrada, fondo oscuro.
- Bloque 6 Noticias (`bloque 6.png`): WP_Query por categoría + fallback dummy; ruta a CPT.
- Panel admin (`inc/admin/*` hoy stubs): editar/reordenar instancias por página,
  guardar overrides en `wp_options` (filtro `okip_page_blocks`), selector wp.media.
- Página de contacto funcional.

**`config/pages/home.php` actual (orden):**
1. `hero` → `home-hero-main`
2. `video-w-title` → `home-video-w-title`
3. `industry-carousel` → `home-industry-carousel`
4. `product-story` → `home-product-story`
5. `mission-statement` → `home-mission-statement`
6. `news` → `home-news`

---

## 11. Trampas conocidas (no repetir)

- En `config/blocks/{type}.php`, declarar funciones **antes** del `return` y con
  `function_exists()` (si no, el `return` corta la inclusión y no se definen / o redeclaran).
- No animar `.okip-hero__card` (lleva `translate(-50%,-50%)` para el centrado x/y):
  anima `.okip-hero__card-media` (interior).
- `okip_merge_defaults` reemplaza listas completas; no esperes merge índice a índice.
- Hay un menú asignado a `primary` en el WP actual → se usa `wp_nav_menu` (no el fallback).
  Los `<a>` de `wp_nav_menu` no llevan clases de tema por defecto: por eso `inc/nav.php`
  añade `okip-navbar__link` con `nav_menu_link_attributes` y el CSS targetea `.okip-navbar__menu a`.
- **Imágenes/video en `assets/`:** siguen vacías → fondos en fallback neutro y tarjetas/
  monitor en placeholder; es lo esperado, no un bug.
- **Bloque 2 (`parallax-monitor`) fue ELIMINADO y sustituido por `video-w-title`.** El overlap
  de traspaso B2→B3 **SE CONSERVA** (HOLD-PIN: `video-w-title` se auto-pinea `pin:true,
  pinSpacing:false` durante `+=offsetHeight` y el Industry Carousel z3 sube encima). Lo que
  desapareció con el bloque viejo es solo su coreografía interna: separación reveal/parallax por
  nodo exterior/interior (`.okip-pm__*`), drift por capa, cover determinista, el empuje por
  `margin-top` inline sobre B3 (innecesario aquí: sin depth-entry, el pin dura justo la altura
  propia), el sync `okip:pm-cover` del navbar y el `scroll_3d` del Hero apagado. Para esos detalles
  ver el historial de git de `template-parts/blocks/parallax-monitor/`.
- **`video-w-title` — el overlap necesita z-index del bloque siguiente MAYOR (opaco):** Industry
  Carousel es z3 / fondo blanco opaco, `video-w-title` z2 → el carrusel pinta encima del bloque
  fijo. Si el bloque siguiente fuera transparente o z menor, se vería el pin a través. El pin
  requiere `nextElementSibling`; sin bloque siguiente no se pinea.
- **`video-w-title` — el reveal es por CSS gated por `.okip-js` + `.is-revealed`:** sin JS el
  texto queda visible (no hay `.okip-js`); con JS nace en `opacity:0` y el `script.js` añade
  `.is-revealed` por IntersectionObserver. Si el texto quedara invisible, revisar que el IO
  o el fallback (sin IO / reduce-motion / `data-anim=0`) esté disparando la clase.
- **Bloque 3 — el contenido se revela TARDE (`start: 'top 15%'`, no `'top 80%'`):** debe aparecer
  solo cuando el panel blanco ya cubre ≈85% del viewport, no al asomar el bloque.
- **Lint "short array syntax" (PHP7103):** es solo un *hint*; el tema usa `array()` por
  convención. No "corregir" a `[]`.
- **Bloque 3 — no usar dos ScrollTriggers simultáneos sobre el mismo nodo:** el ST de
  overlay (`y: 60vh → 0`) y el ST de pin (`pin:true`) sobre la misma sección se pelean.
  Usar solo uno: el pin. La entrada visual se consigue con CSS initial state + la secuencia
  natural del scroll.
- **Bloque 3 — `end` del pin debe venir de medidas reales:** no usar `scrollDistVh * vh`.
  Calcular `firstItemCenterX - lastItemCenterX` con `invalidateOnRefresh:true` para que
  se recalcule en resize. Si se usa distancia arbitraria, el carrusel puede terminar antes
  de mostrar todos los ítems o crear scroll extra.
- **Bloque 3 — índice activo con `Math.round`, no `Math.floor`:** `Math.floor(p * N)`
  llega al último ítem a p=0.75 con N=4, dejando el 25% final sin cambio visual.
  `Math.round(p * (N-1))` distribuye uniformemente.
- **Bloque 3 — `overflow:hidden` en el bloque raíz interfiere con pin:** GSAP pin necesita
  que el bloque no corte su contenido. Usar `overflow:clip` solo en el track-outer o
  quitar el overflow del raíz en desktop.
- **Bloque 3 — fondo claro, no oscuro:** la referencia `bloque 3.png` usa fondo blanco/
  claro. El texto naranja y el heading son oscuros. Es el bloque de mayor contraste con
  el Bloque 2 (que es oscuro).
