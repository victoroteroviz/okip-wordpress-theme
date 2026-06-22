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

- Ruta del tema: `www/wp-content/themes/okip-theme`
- Referencias visuales (PNG, NO código): `/home/littlekid/Documentos/Sistemas/okip_landing/referencias`
  - `navbar.png`, `bloque 1.png` (Hero) … `bloque 6.png`, `idea panel 1/2.png` (panel admin futuro)
- **Ignora** la carpeta hermana `themes/okip` (intento previo; no se usa).

---

## 2. Entorno (Docker)

WordPress corre en contenedor; el host **no** tiene PHP/WP-CLI.

| Cosa | Valor |
|---|---|
| Contenedor WP | `okip_landing_wordpress` (PHP 8.x) |
| Sitio | http://localhost:8080/ |
| WP version | 7.0 · tema activo: `okip-theme` |
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
    └── vendor/gsap/          # gsap.min.js + ScrollTrigger.min.js (HOY ausentes; ver README)
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

## 7. GSAP (local, condicional)

- Archivos reales van en `assets/vendor/gsap/{gsap.min.js,ScrollTrigger.min.js}`
  (ver `assets/vendor/gsap/README.md`). **Hoy ausentes** → se usa fallback.
- `inc/enqueue.php` comprueba `file_exists()` y solo encola GSAP/ScrollTrigger si existen.
  Pasa el estado al front por `wp_localize_script` → `window.OKIP_ENV.{hasGsap,hasScrollTrigger}`.
- `assets/js/gsap-init.js` registra ScrollTrigger si está y expone
  `window.okipGsap.{ready, hasScrollTrigger}`.
- `assets/js/app.js` expone `window.OKIP.{ready(fn), reduceMotion}`.
- **Regla en cada bloque:** si `okipGsap.ready` → animar con GSAP; si no → fallback
  con clases CSS / IntersectionObserver. Siempre respetar `reduceMotion`.

---

## 8. Comportamiento del Navbar

Config en `config/blocks/navbar.php` (grupo `reveal`): `reveal_mode`
(`after_hero|always|manual`), `reveal_offset`, `hide_on_hero`, `use_intersection_observer`.
También define el **menú de respaldo** (si no hay menú `primary` asignado en WP) y el logo.

- `template-parts/layout/navbar.php` expone la config como `data-*` y, en **Home con Hero**,
  añade `okip-navbar--start-hidden` desde el servidor (anti-parpadeo). Sin JS, el navbar es visible.
- `assets/js/navbar.js`:
  - `after_hero` + `hide_on_hero` + hay Hero → navbar **oculto dentro del Hero**;
    aparece al salir (IntersectionObserver sobre `[data-okip-hero]`, ratio ≥ 0.5;
    fallback por `scrollY`). Vuelve a ocultarse al regresar al Hero.
  - Al ocultarse, **cierra el menú móvil** y pone `aria-expanded="false"`.
  - Otras páginas o `always`/`manual` → visible desde el inicio.
- `inc/nav.php`: `okip_nav_menu()` usa `wp_nav_menu('primary')` o el fallback de config.
  > Nota: en el WP actual ya hay un menú asignado a `primary`, así que se ve `wp_nav_menu`,
  > no el fallback. Para ver el fallback, desasigna el menú en Apariencia → Menús.

---

## 9. Bloques implementados

### Hero (`hero`) — instancia `home-hero-main` · ref `bloque 1.png`
Capas: **1) background media limpio (video|image|svg)** → **2) overlay opcional** →
**3) tarjetas flotantes** → **4) texto central**.

- **Media-driven:** fondo solo si el media existe (`okip_media_exists`); si no →
  `okip-hero__bg--missing` = color sólido `#05080f` (sin gradiente/glow falso).
- **Tarjetas:** solo se renderizan con `active` + `type` válido + media real.
  Sin media válido → no se pinta nada (no hay placeholder fake).
- **Secuencia de entrada (JS):** fondo → tarjetas → texto, según `reveal.strategy`:
  - `video_end` (default): revela al terminar el video.
  - `canplay`: revela cuando el video puede reproducirse (loop).
  - `delay`: revela tras `image_reveal_delay`.
  - image/svg/missing → revela tras `image_reveal_delay` (default 1500 ms).
  - Si el video falla o no arranca antes de `video_fail_timeout` (2000 ms) →
    fallback: `is-bg-failed` (neutro + atenuado), pausa video, revela contenido.
- **Reentrada:** `replay_on_enter` → al volver al Hero (IntersectionObserver, fallback
  scrollY) se reinicia: video a `currentTime=0`, tarjetas/texto se ocultan y re-animan.
- **Scroll 3D** (`animation.scroll_3d`) con ScrollTrigger; desactivado en reduce-motion.
- Config: `config/blocks/hero.php`. Grupos: `content`, `background`, `overlay`,
  `reveal`, `cards` (lista; defaults en `okip_hero_card_defaults()`), `animation`.
- Texto actual: "Inteligencia mexicana" / "al servicio de la humanidad".

### Parallax Monitor (`parallax-monitor`) — instancia `home-parallax-monitor` · ref `bloque 2.png`
Sección oscura: **texto a la izquierda, monitor a la derecha**, glow azul tras el monitor.
Capas: fondo (media-driven, fallback neutro) → overlay opcional → monitor → texto.

- **Monitor media-driven:** usa `screen_video`/`screen_image`/`image` (device) si existen;
  si no → **marco geométrico mínimo** por CSS + pantalla neutra (no mockup falso).
- **Título con resaltado:** `highlighted_text` se parte y envuelve en
  `.okip-pm__highlight` (naranja), todo escapado.
- **Parallax (JS):** fondo y monitor a distinto `data-speed` con GSAP+ScrollTrigger.
  Sin GSAP → solo revelado de entrada (IntersectionObserver + CSS). Desactivado en
  móvil (≤880px) y reduce-motion.
- Config: `config/blocks/parallax-monitor.php`. Grupos: `content` (eyebrow, title,
  highlighted_text, description), `background`, `monitor`, `cta`, `overlay`, `animation`.
- Contenido actual: eyebrow "Tecnología OKIP", title "Inteligencia visual para
  proteger lo que importa" (highlight "proteger"), CTA "Conocer tecnología" → `/fabrica-de-tecnologias`.

---

## 10. Estado y roadmap

**Hecho (Fase 1A + 1B):**
- Motor de bloques, config por página, carga condicional de assets, GSAP local condicional.
- Navbar responsive con ocultar-en-Hero y fallback de menú.
- Hero media-driven con secuencia/reentrada.
- Parallax Monitor (bloque 2).
- Páginas placeholder: `config/pages/{contacto,sala-de-prensa,fabrica-de-tecnologias}.php`
  (devuelven `[]` → fallback `the_content()`).

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
- Hay un menú asignado a `primary` en el WP actual → el fallback de navbar no se ve
  salvo que se desasigne.
- No hay media en `assets/` → todo se ve en **fallback neutro**; es lo esperado, no un bug.
