
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

- Ruta del tema: `www/wp-content/themes/okip-wordpress-theme`
- Referencias visuales (PNG, NO código): `/home/littlekid/Documentos/Sistemas/okip_landing/referencias`
  - `navbar.png`, `bloque 1.png` (Hero) … `bloque 6.png`, `idea panel 1/2.png` (panel admin futuro)
- **Ignora** las carpetas hermanas `themes/okip` y `themes/okip-theme` (intentos previos;
  el tema activo es `okip-wordpress-theme`).

---

## 2. Entorno (Docker)

WordPress corre en contenedor; el host **no** tiene PHP/WP-CLI.

| Cosa | Valor |
|---|---|
| Contenedor WP | `okip_landing_wordpress` (PHP 8.x) |
| Sitio | http://localhost:8080/ |
| WP version | 7.0 · tema activo: `okip-wordpress-theme` |
| Host tools | `node`, `python3`, `jq`, `docker` |

Comandos de verificación:
```bash
# Lint PHP (en el contenedor)
docker exec okip_landing_wordpress sh -c 'cd /var/www/html/wp-content/themes/okip-wordpress-theme && for f in $(find . -name "*.php" -not -path "./.git/*"); do php -l "$f"; done'

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
okip-wordpress-theme/
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
Escena oscura **full-screen** (`min-height:100svh`) que ENTRA SOBRE el Hero por
**progreso de scroll** (no desde el primer pintado). Texto izquierda, monitor derecha,
glow azul tras el monitor.

- **3 capas reales** con z-index 1/2/3 y `data-okip-pm-layer="background|computer|text"`.
  **Separación reveal/parallax (CLAVE — arregla "letras atascadas"):**
  - PARALLAX = `transform` inline (GSAP o rAF) en el nodo **EXTERIOR**
    (`.okip-pm__bg`, `.okip-pm__monitor`, `.okip-pm__text`) → solo drift.
  - REVEAL = opacidad/translate por **CLASE latcheada** (`is-bg-revealed`,
    `is-computer-revealed`, `is-text-revealed`) en el nodo **INTERIOR**
    (`.okip-pm__computer-reveal`, `.okip-pm__text-reveal`, y opacidad del fondo),
    con transición CSS. NUNCA el mismo nodo recibe ambos.
- **Transición Hero→Bloque 2 (`template-parts/blocks/parallax-monitor/script.js`):**
  - Con **GSAP+ScrollTrigger**: 2 timelines `scrub:0.6` (suave). (1) overlap del bloque
    (`y: overlapPx→0`) + hundimiento del Hero (`y/scale/opacity`), `start ≈ top+85%·heroH`,
    `end +15vh`. (2) parallax drift por capa. Reveal latcheado desde `onUpdate(progress)`
    (+`onLeave`→final, `onLeaveBack`→inicial). `ScrollTrigger.refresh()` en resize.
  - **Fallback vanilla** (sin GSAP): rAF + `lerp` (damping), mismo sistema de clases;
    IO solo activa/desactiva el bucle; al pausar fija estado coherente (inicial/final).
  - El bloque controla el hundimiento del Hero (por eso el `scroll_3d` del Hero está off).
  - Desactivado (modo `is-static`, reveal simple por IO) en móvil (≤768px) y reduce-motion.
- **Overlap sin layout jump:** margin negativo CONSTANTE (solo desktop/no-reduce) +
  estado inicial counter-transformado hacia abajo + opacidad 0 → no tapa el Hero al cargar.
- **Monitor media-driven:** `computer.type` (`video|image|svg|placeholder`) + `computer.media`;
  sin media → **placeholder geométrico** + marco mínimo (no mockup). `autoplay_on_enter`:
  el video de pantalla SÍ puede arrancar al entrar su capa (es parte de la escena).
- **Título con resaltado:** `highlighted_text` envuelto en `.okip-pm__highlight` (naranja), escapado.
- Config: `config/blocks/parallax-monitor.php`. Grupos: `content`, `layout`
  (`min_height`, `overlap_previous`, `overlap_start≈0.85`, `overlap_amount`, `z_index`),
  `background`, `computer`, `cta`, `overlay`, `glow` (`enabled`,`intensity`),
  `animation` (`use_gsap`, `use_vanilla_fallback`, `parallax_enabled`,
  `overlap_transition_enabled`, `start_progress`, `{background,computer,text}_speed`,
  `{background,computer,text}_enter_range`, `pin_enabled=false`, `text_reveal`).
- Contenido actual: eyebrow "Tecnología OKIP", title "Inteligencia visual para
  proteger lo que importa" (highlight "proteger"), CTA "Conocer tecnología" → `/fabrica-de-tecnologias`.

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
- **Bloque 2:** escena full-screen, transición Hero→Bloque 2 con GSAP+ScrollTrigger
  (scrub) + fallback vanilla; 3 capas con reveal latcheado SEPARADO del parallax
  (sin "letras atascadas").
- Páginas placeholder: `config/pages/{contacto,sala-de-prensa,fabrica-de-tecnologias}.php`
  (devuelven `[]` → fallback `the_content()`).

> **Pendiente de verificación VISUAL en navegador** (no se puede automatizar): suavidad
> de la transición con GSAP, navbar oculto en Hero / visible en Bloque 2, hamburguesa por
> breakpoint, tarjetas sin autoplay. Recargar con Ctrl+Shift+R por caché de CSS/JS.

**Pendiente (NO implementado aún):**
- Bloque 3 Carrusel de industrias (`bloque 3.png`): palabra naranja cambia con la imagen
  activa; botones ahora, dejar `interaction_mode: buttons|scroll` para el futuro.
- Bloque 4 Storytelling productos (`bloque 4.png` + video): scroll narrativo.
- Bloque 5 Mensaje institucional (`bloque 5.png`): frase centrada, fondo oscuro.
- Bloque 6 Noticias (`bloque 6.png`): WP_Query por categoría + fallback dummy; ruta a CPT.
- Panel admin (`inc/admin/*` hoy stubs): editar/reordenar instancias por página,
  guardar overrides en `wp_options` (filtro `okip_page_blocks`), selector wp.media.
- Página de contacto funcional.

**`config/pages/home.php` actual (orden):**
1. `hero` → `home-hero-main`
2. `parallax-monitor` → `home-parallax-monitor`

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
  va en el nodo EXTERIOR; reveal (clase latcheada) en el INTERIOR. Si los juntas, vuelve el
  bug de "letras atascadas" al pausar el rAF/ScrollTrigger.
- **No animar el Hero desde dos sitios:** su `scroll_3d` está OFF en Home porque el Bloque 2
  ya transforma el Hero (`hero.style.transform/opacity`). Si reactivas `scroll_3d` en Home,
  habrá doble transform.
- **GSAP `start/end` de la transición usan `hero.offsetHeight`** (layout, no afectado por
  transform) con `invalidateOnRefresh` → estables en resize. No uses `getBoundingClientRect`
  para los límites del ScrollTrigger (el Hero se escala y daría feedback).
- **Lint "short array syntax" (PHP7103):** es solo un *hint*; el tema usa `array()` por
  convención. No "corregir" a `[]`.
