
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
5. `instance_id` **manual, legible y estable** (ej. `home-hero-main`, `home-parallax-monitor`).
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
│       └── parallax-monitor/
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

- **Whitelist:** `okip_allowed_blocks()` en `inc/blocks.php`. Hoy: `hero`, `parallax-monitor`.
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
  el navbar nace oculto (`okip-navbar--start-hidden` server-side, gated por `.okip-js`)
  y se controla por **PROGRESO de scroll** (no solo IO, porque con el overlap del Bloque 2
  el Hero puede seguir intersectando): aparece cuando el progreso de transición supera
  ~0.15 (pasado el ~85% del Hero) y se oculta al volver. **Guard:** si `hero.offsetHeight<=0`
  → `hide()` (nunca mostrar por medida inválida). Al ocultarse cierra el menú móvil
  (`aria-expanded=false`). `is-scrolled` (scrollY>8) solo cambia el fondo, no la visibilidad.
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

### Parallax Monitor (`parallax-monitor`) — instancia `home-parallax-monitor` · ref `bloque 2.png`
Escena oscura **cinematográfica full-screen** (`min-height:100svh`). Texto grande a la
izquierda, **monitor protagonista** a la derecha, glow azul tras el monitor, **piso/reflejo
azul** en la base, fondo negro→azul profundo (sin grid/patrón). **El Hero se mantiene limpio
y protagonista**: la transición Hero→B2 NO empieza desde el primer pintado, solo al SALIR del
Hero (≈80% de su scroll).

- **3 capas reales** con z-index 1/2/3 y `data-okip-pm-layer="background|computer|text"`.
  **Separación estricta reveal/parallax (regla crítica — nunca el mismo nodo):**
  - PARALLAX = `transform` inline (GSAP o rAF) SOLO en el nodo **EXTERIOR**
    (`.okip-pm__bg`, `.okip-pm__monitor`, `.okip-pm__text`) → solo drift.
  - REVEAL = opacidad/translate por **CLASE** (`is-bg-revealed`, `is-computer-revealed`,
    `is-text-revealed`) en el nodo **INTERIOR** (`.okip-pm__bg-inner`,
    `.okip-pm__computer-reveal`, `.okip-pm__text-reveal`), con transición CSS.
  - **El fondo está dividido en exterior (`.okip-pm__bg`, parallax + headroom `inset:-8% 0`)
    e interior (`.okip-pm__bg-inner`, reveal/opacidad/media/gradiente).** El gradiente del
    estado sin media va en `.okip-pm__bg-inner--missing/--gradient`.
  - **El ROOT `.okip-pm` NUNCA recibe transform de masa** (no se mueve "de golpe").
- **Transición Hero→Bloque 2 (`script.js`) — 3 conceptos SEPARADOS:**
  1. **Hero recede** (scrub, `trigger: hero`): el Hero se hunde (`y/scale/opacity`) SOLO en
     su último ~20% (`start ≈ top+80%·heroH`, `end ≈ top+100%·heroH`). El bloque controla
     el hundimiento del Hero (por eso el `scroll_3d` del Hero está off).
  2. **Reveal one-shot** (`once`, `trigger: hero`, `start ≈ top+82%·heroH`): añade las 3
     clases de reveal A LA VEZ; el **escalonado fondo→texto→monitor lo da el CSS**
     (`transition-delay`) → suave, nunca "de golpe" ni atascado. (No usa `onUpdate`.)
  3. **Parallax drift** (scrub, `trigger: section`): `fromTo(y)` por capa en los nodos
     exteriores con `data-speed`.
  - **Fallback vanilla** (sin GSAP): rAF para drift + recede; reveal por IO one-shot
    (threshold 0.45). `heroProgress` arranca al 80%. Sin pin (B2→B3 degrada a apilado).
  - Desactivado (modo `is-static`, reveal inmediato) en móvil/tablet (**≤1024px**) y reduce-motion.
- **Transición Bloque 2 → Bloque 3 (overlap real):** B2 se **auto-pinea como fondo**
  (`{id}-bgpin`: `pin:true, pinSpacing:false`, dura `background_pin_vh` vh ≈ 90) → queda
  FIJO (position:fixed, **sin transform**) mientras el Bloque 3 (z-index mayor) **sube por
  scroll ENCIMA**. B3 NUNCA escribe transforms sobre `.okip-pm` ni sus capas. Solo desktop+GSAP.
- **Monitor media-driven:** `computer.type` (`video|image|svg|placeholder`) + `computer.media`;
  sin media → **placeholder esquemático tipo dashboard** (barra+dots, panel "mapa", tarjetas
  laterales) + marco mínimo. `autoplay_on_enter`: el video de pantalla puede arrancar al revelarse.
- **Título con resaltado:** `highlighted_text` envuelto en `.okip-pm__highlight` = **negrita
  blanca** (NO color naranja; ref `bloque 2.png`), escapado. `subtitle` = kicker uppercase
  letterspaced bajo el título.
- **Cover Hero→B2 DETERMINISTA:** `.okip-pm__cover` (capa `fixed`) NO usa tween por
  tiempo; su opacidad se deriva de `self.progress` del ScrollTrigger del cover en
  `setCoverProgress()` (`gsap.set`, instantáneo) → nunca queda "a medias" en scroll
  rápido. `cover_start_vh` = vh antes del top donde empieza; `cover_ramp` (0..1) =
  fracción de la ventana hasta opacidad total (**ATAR** con `computer_enter_range` para
  que el cover cierre ANTES del reveal del monitor; ver comentario en script.js).
- **Navbar sincronizado:** B2 expone el estado en `<html>`: `is-pm-sync-ready`,
  `is-pm-covering` (rampa, hook CSS reservado), `is-pm-covered` (opaco) + evento
  `okip:pm-cover`. `navbar.js` sigue ESE estado (no `getBoundingClientRect`) → sin
  franja. El estado también se emite en modo estático/vanilla (`initCoverSyncFallback`).
- Config: `config/blocks/parallax-monitor.php`. Grupos: `content` (`eyebrow`, `title`,
  `highlighted_text`, `subtitle`, `description`), `layout` (`min_height`, `content_width`,
  `z_index`), `background`, `computer`, `cta`, `overlay`, `glow`, `animation` (`use_gsap`,
  `use_vanilla_fallback`, `parallax_enabled`, `overlap_breakpoint=1024` ≤ → estático sin
  pin/cover, `background_pin`, `background_pin_vh=100`, `entry_scroll_vh=155`,
  `cover_delay_vh=50`, `cover_start_vh=8`, `cover_ramp=0.45`, `parallax_drift_px=180`,
  `{background,computer,text}_speed` = **0.45 / 0.78 / 0.95**, `{background,computer,text}_enter_range`).
  **Knobs eliminados** (estaban muertos): `overlap_previous`, `overlap_start`,
  `overlap_amount`, `overlap_transition_enabled`, `pin_enabled`, `text_reveal`,
  `start_progress`, `disable_parallax_below` (→ `overlap_breakpoint`).
- Contenido actual: title "Facilitando la **toma de decisiones** en tiempo real" (highlight
  "toma de decisiones"), subtitle "MONITOREO, GESTIÓN E INTELIGENCIA OPERATIVA", **sin CTA**
  (la referencia no lo muestra), sin eyebrow ni descripción.

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
- **Bloque 2 (Rev.2):** rediseño visual hacia `bloque 2.png` (fondo premium, monitor
  protagonista con mockup dashboard, glow + piso/reflejo, título con highlight negrita +
  subtítulo). Hero protagonista: transición Hero→B2 solo al salir del Hero (recede 80% +
  reveal one-shot 82%, root sin transform). Fondo dividido exterior(parallax)/interior(reveal);
  drift visible en las 3 capas (0.30/0.85/0.08). **Transición B2→B3:** B2 auto-pin como fondo
  (`pinSpacing:false`) y B3 sube encima por z-index, sin tocar `.okip-pm`.
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
2. `parallax-monitor` → `home-parallax-monitor`
3. `industry-carousel` → `home-industry-carousel`

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
- **Bloque 2 — no mezclar reveal y parallax en el mismo nodo:** parallax (transform inline)
  va en el nodo EXTERIOR; reveal (clase/opacidad) en el INTERIOR. Aplica también al FONDO:
  `.okip-pm__bg` (exterior) solo transform; `.okip-pm__bg-inner` (interior) solo opacidad/
  media/gradiente. Si los juntas, el contenido se congela en estado intermedio.
- **Bloque 2 — el ROOT `.okip-pm` no debe recibir transform de masa:** mueve "todo de golpe"
  y rompe la sensación de capas. El overlap Hero→B2 por margin negativo ("lip") fue ELIMINADO
  porque hacía que B2 invadiera al Hero desde el load. El Hero queda limpio; B2 entra solo por
  recede del Hero + reveal escalonado.
- **Bloque 2 — disparar la transición tarde (al SALIR del Hero):** recede `start ≈ top+80%·heroH`,
  reveal one-shot `start ≈ top+82%·heroH`, ambos `trigger: hero`. Disparar temprano (50% o
  `section top 78%`) le quita protagonismo al Hero.
- **Bloque 2 — reveal one-shot (no `onUpdate`):** se añaden las 3 clases a la vez y el CSS las
  escalona con `transition-delay`. Latchear por `onUpdate(progress)` en un rango corto hace que
  todo "suba de golpe".
- **Bloque 2 — drift del fondo debe ser visible:** con `speed` muy bajo (≈0.16) parece que solo
  se mueve el monitor. Valores actuales 0.30/0.85/0.08; el exterior del fondo necesita headroom
  (`inset:-8% 0`) para que el drift no descubra bordes.
- **Bloque 2 → Bloque 3 (overlap):** B2 se auto-pinea (`pin:true, pinSpacing:false`) como fondo
  fijo (position:fixed, sin transform) y B3 sube encima por z-index. B3 **no** debe escribir
  transforms sobre `.okip-pm` ni sus capas. El handoff con el pin del carrusel de B3 es secuencial.
- **Bloque 2 → Bloque 3 — NO retrasar a B3 con un selector adyacente:** el empuje que evita que
  B3 cubra a B2 antes de tiempo (sobrante del depth-entry + hold estático) debe aplicarse como
  `margin-top` **inline** sobre B3 desde el JS de B2. Una regla `.okip-pm.is-gsap + .okip-ic`
  se ROMPE porque ScrollTrigger envuelve a B2 en un `.pin-spacer` al pinearlo → la adyacencia
  deja de cumplirse → `margin:0` → B3 cubre a B2 mientras el texto aún se revela. (Era el bug.)
- **Bloque 2 → Bloque 3 — la duración del pin se calcula con `offsetHeight`, no `offsetTop`:**
  `holdPinDistance = section.offsetHeight + margin(B3)` (≡ `B3.offsetTop − B2.offsetTop`). Tras el
  `.pin-spacer`, los `offsetTop` cambian de offsetParent y son poco fiables; `offsetHeight` es estable.
- **Bloque 2 → Bloque 3 — el hold estático es `cover_delay_vh` (≈50vh = medio viewport):** se suma
  al sobrante del depth-entry para que B2 quede revelado y quieto un rato antes de que B3 cubra.
- **Bloque 2 — móvil/tablet (≤1024px) sin pin/overlap:** el JS gatea por `isSmallViewport()`
  (`canAnimate && !isSmall`) → entra en `is-static` (reveal inmediato) y NO empuja a B3; flujo
  vertical normal. El resize a ≤1024px desmonta el pin (`bgPinST.kill()`) y limpia el `margin-top`.
- **Bloque 3 — el contenido se revela TARDE (`start: 'top 15%'`, no `'top 80%'`):** debe aparecer
  solo cuando el panel blanco ya cubre ≈85% del viewport, no al asomar el bloque.
- **No animar el Hero desde dos sitios:** su `scroll_3d` está OFF en Home porque el Bloque 2
  ya transforma el Hero (`hero.style.transform/opacity`). Si reactivas `scroll_3d` en Home,
  habrá doble transform.
- **GSAP `start/end` de la transición usan `hero.offsetHeight`** (layout, no afectado por
  transform) con `invalidateOnRefresh` → estables en resize. No uses `getBoundingClientRect`
  para los límites del ScrollTrigger (el Hero se escala y daría feedback).
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
