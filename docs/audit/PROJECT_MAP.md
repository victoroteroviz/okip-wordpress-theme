# OKIP Theme — Mapa Navegable del Proyecto

## 1. Punto de Entrada

**Usuario visita:** `http://localhost:8080/`
**Template:** `front-page.php:17` → `okip_render_page(okip_get_page_blocks('home'))`
**Ver también:** [FLOW.md](#flujo-completo)

---

## 2. Estructura Física

```
okip-theme/
│
├── 📄 functions.php [BOOTSTRAP]
│   └─ Carga todos los módulos inc/
│
├── 📄 front-page.php [HOME]
│   ├─ Carga header.php
│   ├─ Renderiza bloques
│   └─ Carga footer.php
│
├── 📄 page.php [PÁGINAS GENÉRICAS]
│   ├─ Busca config/pages/{slug}.php
│   └─ Fallback: the_content()
│
├── 📄 header.php [SHELL]
│   ├─ Inyecta CSS global + GSAP
│   ├─ Inyecta JS global
│   └─ Renderiza navbar
│
├── 📄 footer.php [CIERRE]
│   ├─ Cierra main
│   ├─ Inyecta JS de bloques
│   └─ wp_footer()
│
├── 📁 inc/ [LÓGICA]
│   ├── 📄 setup.php
│   │   └─ add_theme_support(), register_nav_menus()
│   │   └─ [PUNTO] Theme supports, menús
│   │
│   ├── 📄 enqueue.php [CLAVE]
│   │   ├─ okip_has_gsap() - Verifica GSAP existe
│   │   ├─ okip_enqueue_assets() - Enqueue CSS/JS global + condicional
│   │   └─ okip_localize_script() - OKIP_ENV flags
│   │   └─ [PUNTO] Ver dependencies.md§1
│   │
│   ├── 📄 blocks.php [MOTOR]
│   │   ├─ okip_allowed_blocks() - Whitelist
│   │   ├─ okip_normalize_block_data() - Normalización
│   │   ├─ okip_render_block() - Renderiza uno
│   │   ├─ okip_render_page() - Renderiza todos
│   │   └─ okip_used_block_types() - Para enqueue
│   │   └─ [PUNTO] Ver architecture.md§3
│   │
│   ├── 📄 data.php [DATOS]
│   │   ├─ okip_get_page_blocks() - Lee config + aplica filtros
│   │   ├─ okip_apply_page_block_order() - Reordena (admin)
│   │   ├─ okip_apply_page_block_overrides() - Mezcla overrides
│   │   └─ okip_current_page_slug() - Detecta página
│   │   └─ [PUNTO] Ver architecture.md§4
│   │
│   ├── 📄 block-loader.php
│   │   └─ okip_enqueue_block_assets() - Assets condicionales
│   │   └─ [PUNTO] Llamado por enqueue.php
│   │
│   ├── 📄 sanitize.php [HELPERS]
│   │   ├─ okip_merge_defaults() - Merge recursivo
│   │   ├─ okip_normalize_transition() - Sistema hybrid
│   │   └─ 10+ utilidades más
│   │   └─ [PUNTO] Base de validación
│   │
│   ├── 📄 design-controls.php
│   │   ├─ okip_normalize_typography() - Tipografía
│   │   ├─ okip_collect_page_google_fonts() - Fuentes dinámicas
│   │   └─ okip_typography_css_vars() - Variables CSS
│   │   └─ [PUNTO] Ver architecture.md§7
│   │
│   ├── 📄 animation-controls.php
│   │   ├─ okip_motion_defaults() - Defaults motion
│   │   ├─ okip_normalize_motion() - Normaliza motion
│   │   └─ okip_admin_motion_*() - Controles admin
│   │   └─ [PUNTO] Ver animation_system.md
│   │
│   ├── 📄 media.php
│   │   ├─ okip_media_url() - ID/URL/ruta → URL
│   │   ├─ okip_media_exists() - Verifica existencia
│   │   └─ okip_media_alt() - Alt de attachment
│   │   └─ [PUNTO] Media-driven fallback
│   │
│   ├── 📄 nav.php
│   │   ├─ okip_navbar_config() - Config navbar
│   │   └─ okip_nav_menu() - Renderiza menú
│   │
│   ├── 📁 admin/ [FUTURO]
│   │   ├── 📄 admin-pages.php - Panel admin
│   │   ├── 📄 fields.php - Generadores HTML
│   │   ├── 📄 save-handlers.php - AJAX guardar
│   │   ├── 📄 sanitizers.php - Saneo post
│   │   ├── 📄 repositories.php - CRUD
│   │   ├── 📁 editors/ - Paneles por bloque
│   │   └── 📁 partials/ - Repeaters
│   │   └─ [PUNTO] Ver admin_system.md
│   │
│   ├── 📄 layout-settings.php
│   │   └─ Helpers layout (layout zones, containers)
│   │
│   └── 📄 animation-controls.php
│       └─ Helpers animación (motion, motion_stage, etc.)
│
├── 📁 config/ [ESQUEMAS + DEFAULTS]
│   ├── 📁 blocks/ [DEFAULTS POR TIPO]
│   │   ├── 📄 hero.php
│   │   │   ├─ `okip_hero_card_defaults()`
│   │   │   ├─ `okip_normalize_hero_data()`
│   │   │   └─ return [ defaults ] [1K líneas]
│   │   │   └─ [PUNTO] Ver architecture.md§1 (bloques)
│   │   │
│   │   ├── 📄 video-w-title.php
│   │   │   ├─ `okip_vwt_text_box_defaults()`
│   │   │   ├─ `okip_normalize_video_w_title_data()`
│   │   │   └─ return [ defaults ]
│   │   │   └─ [PUNTO] Sticky-cover transition
│   │   │
│   │   ├── 📄 industry-carousel.php
│   │   │   └─ Defaults carousel
│   │   │   └─ [PUNTO] ScrollTrigger pin
│   │   │
│   │   ├── 📄 product-story.php
│   │   │   └─ Defaults producto
│   │   │
│   │   ├── 📄 mission-statement.php
│   │   │   └─ Defaults misión
│   │   │
│   │   ├── 📄 news.php
│   │   │   └─ Defaults noticias
│   │   │
│   │   ├── 📄 navbar.php
│   │   │   └─ Logo, menú, reveal config
│   │   │   └─ [PUNTO] okip_navbar_config()
│   │   │
│   │   └── 📄 footer.php
│   │       └─ Footer defaults (futuro)
│   │
│   └── 📁 pages/ [CONTENIDO POR PÁGINA]
│       ├── 📄 home.php [CRÍTICO]
│       │   ├─ Array ordenado de 6 bloques
│       │   ├─ Bloque 1: Hero (home-hero-main)
│       │   ├─ Bloque 2: Video (home-video-w-title)
│       │   ├─ Bloque 3: Carousel (home-industry-carousel)
│       │   ├─ Bloque 4: Producto (home-product-story)
│       │   ├─ Bloque 5: Misión (home-mission-statement)
│       │   └─ Bloque 6: News (home-news)
│       │   └─ [PUNTO] Ver flow.md§5 (renderización)
│       │
│       ├── 📄 contacto.php - Empty (fallback the_content())
│       ├── 📄 sala-de-prensa.php
│       └── 📄 fabrica-de-tecnologias.php
│
├── 📁 template-parts/ [PRESENTACIÓN]
│   ├── 📁 layout/
│   │   ├── 📄 navbar.php [CRÍTICO]
│   │   │   ├─ Renderiza header.okip-navbar
│   │   │   ├─ Logo, menú, toggle
│   │   │   ├─ Data-attributes para JS
│   │   │   └─ [PUNTO] Ver js_architecture.md§5
│   │   │
│   │   └── 📄 footer-site.php
│   │       └─ Footer HTML
│   │
│   └── 📁 blocks/ [POR BLOQUE]
│       ├── 📁 hero/
│       │   ├── 📄 block.php [1K líneas]
│       │   │   ├─ Capas: bg, overlay, cards, content
│       │   │   ├─ Media-driven fallback
│       │   │   ├─ JSON motion config
│       │   │   └─ Data-attributes para JS
│       │   │   └─ [PUNTO] Ver architecture.md§8.1
│       │   │
│       │   ├── 📄 style.css [500 líneas]
│       │   │   └─ .okip-hero-{bg,overlay,cards,content}
│       │   │   └─ Position sticky, z-index, layers
│       │   │
│       │   └── 📄 script.js [500 líneas]
│       │       ├─ initHero() - Punto entrada
│       │       ├─ Secuencia intro → crossfade → loop
│       │       ├─ setupCards() - Interactividad
│       │       ├─ setupCardsAutoplay() - Autoplay
│       │       ├─ setupCoverPause() - Pausa al cubrir
│       │       └─ [PUNTO] Ver js_architecture.md§6
│       │
│       ├── 📁 video-w-title/
│       │   ├── 📄 block.php
│       │   │   └─ Video bg + overlay + texto
│       │   │   └─ Sticky-cover structure
│       │   │
│       │   ├── 📄 style.css
│       │   │   └─ .okip-vwt-{bg,overlay,stage,text}
│       │   │
│       │   └── 📄 script.js
│       │       ├─ Reveal entrada (IO 15%)
│       │       └─ Sticky-cover CSS (sin ScrollTrigger)
│       │
│       ├── 📁 industry-carousel/
│       │   ├── 📄 block.php
│       │   ├── 📄 style.css
│       │   └── 📄 script.js
│       │       └─ ScrollTrigger pin + scrub
│       │
│       ├── 📁 product-story/
│       ├── 📁 mission-statement/
│       └── 📁 news/
│
├── 📁 assets/ [RECURSOS GLOBALES]
│   ├── 📁 css/
│   │   ├── 📄 tokens.css [CRÍTICO]
│   │   │   └─ --okip-* variables (colores, spacing, fonts, z)
│   │   │   └─ [PUNTO] Ver css_architecture.md§2
│   │   │
│   │   ├── 📄 base.css
│   │   │   └─ Reset, accesibilidad
│   │   │   └─ [PUNTO] Depende tokens.css
│   │   │
│   │   ├── 📄 layout.css
│   │   │   └─ Grid, containers, responsive
│   │   │
│   │   ├── 📄 components.css
│   │   │   └─ Navbar, botones, UI
│   │   │
│   │   ├── 📄 transitions.css
│   │   │   └─ Sticky-cover, pin CSS
│   │   │
│   │   └── 📄 animations.css
│   │       └─ Keyframes GSAP
│   │
│   ├── 📁 js/
│   │   ├── 📄 app.js [CRÍTICO]
│   │   │   ├─ window.OKIP.* utilities
│   │   │   └─ [PUNTO] Ver js_architecture.md§2
│   │   │
│   │   ├── 📄 gsap-init.js
│   │   │   ├─ Registra ScrollTrigger
│   │   │   ├─ Expone okipGsap.ready
│   │   │   └─ [PUNTO] Condicional GSAP
│   │   │
│   │   ├── 📄 animations.js [CRÍTICO]
│   │   │   ├─ OKIPAnimations.create()
│   │   │   ├─ 3 fases: entry, playback, exit
│   │   │   ├─ GSAP + CSS fallback
│   │   │   └─ [PUNTO] Ver animation_system.md
│   │   │
│   │   ├── 📄 navbar.js
│   │   │   ├─ Visibilidad navbar
│   │   │   ├─ Hamburguesa accesible
│   │   │   └─ [PUNTO] Ver js_architecture.md§5
│   │   │
│   │   └── 📄 admin-blocks.js (futuro)
│   │       └─ JavaScript panel admin
│   │
│   ├── 📁 vendor/gsap/ [OPCIONAL]
│   │   ├── 📄 gsap.min.js
│   │   └── 📄 ScrollTrigger.min.js
│   │   └─ [PUNTO] Si no existe → CSS fallback
│   │
│   ├── 📁 img/ (vacío)
│   ├── 📁 video/ (vacío)
│   ├── 📁 svg/ (vacío)
│   └── 📁 gif/ (vacío)
│       └─ [PUNTO] Media cae a fallback media-driven
│
└── 📁 referencias/ [VISUALES]
    ├── 📄 navbar.png - Ref navbar
    ├── 📄 bloque 1.png - Hero
    └── ... (mockups)
```

---

## 3. Flujo de Decisión (Debugging)

### "¿Cómo se renderiza un bloque?"

```
front-page.php:17
    ↓
okip_get_page_blocks('home')
    ├─ Lee config/pages/home.php
    └─ Aplica filtros okip_page_blocks
        → ¿Orden guardado en admin?
            ├─ SÍ: Reordena
            └─ NO: Usa orden config
    ↓
okip_render_page($blocks)
    ├─ foreach bloque:
    │   okip_render_block(type, instance_id, data, order)
    │       ├─ ¿Type en whitelist?
    │       │   ├─ NO: skip (return)
    │       │   └─ SÍ: continúa
    │       ├─ ¿Template existe?
    │       │   ├─ NO: skip (return)
    │       │   └─ SÍ: continúa
    │       ├─ normalize_block_data()
    │       │   └─ Merge + okip_normalize_{type}_data()
    │       ├─ Enqueue assets (si primera vez por tipo)
    │       │   └─ okip_enqueue_block_assets()
    │       └─ get_template_part('blocks/{type}/block')
    │           └─ Renderiza block.php con $args
    │
    └─ HTML + data-attrs + JSON config emitido
```

---

## 4. Cómo Agregar un Bloque Nuevo

**Paso 1:** `inc/blocks.php:24` - Añade tipo a `okip_allowed_blocks()`

**Paso 2:** Crea `config/blocks/nuevo-tipo.php`
```php
// Retorna array de defaults
// Declara okip_normalize_nuevo_tipo_data() si necesario
```

**Paso 3:** Crea `template-parts/blocks/nuevo-tipo/block.php`
```php
// Recibe $args['data'] ya normalizado
// Renderiza HTML con data-attributes
```

**Paso 4 (Opcional):** Crea `template-parts/blocks/nuevo-tipo/style.css`

**Paso 5 (Opcional):** Crea `template-parts/blocks/nuevo-tipo/script.js`

**Paso 6:** Añade instancia a `config/pages/home.php`
```php
array(
    'type' => 'nuevo-tipo',
    'instance_id' => 'home-nuevo-tipo-main',
    'data' => [ ... ]
)
```

**¡Listo!** Motor, enqueue, admin se reutilizan automáticamente.

---

## 5. Cómo Encontrar una Cosa

**"Renderización de bloques"**
→ Ver: `inc/blocks.php` (función principal)
→ Ejemplo: `template-parts/blocks/hero/block.php`

**"Sistema de animaciones"**
→ Ver: `assets/js/animations.js` (motor)
→ Config: `config/blocks/{type}.php` → `motion` group
→ Docs: `animation_system.md`

**"Tipografía"**
→ Normalize: `inc/design-controls.php` → `okip_normalize_typography()`
→ Variables CSS: `assets/css/tokens.css` + generadas en block.php

**"Navbar visibility"**
→ Logic: `assets/js/navbar.js` (reveal_mode)
→ Template: `template-parts/layout/navbar.php`
→ Config: `config/blocks/navbar.php`

**"Media-driven fallback"**
→ Check: `inc/media.php` → `okip_media_exists()`
→ Render: `template-parts/blocks/{type}/block.php` (inline logic)

**"Z-index order"**
→ Calculation: `template-parts/blocks/{type}/block.php` (style=--okip-z)
→ Logic: `$z = $order + 1`

**"Admin system"**
→ Stubs: `inc/admin/` (sin funcionalidad aún)
→ Data flow: `inc/data.php` → filtros okip_page_blocks
→ Storage: `wp_options[okip_page_blocks_order_*]` + `okip_page_blocks_overrides_*`

---

## 6. Responsabilidades por Archivo

| Archivo | Responsabilidad |
|---------|-----------------|
| functions.php | Bootstrap, carga módulos |
| front-page.php | Punto entrada home, render |
| inc/blocks.php | Motor de bloques, whitelist, render |
| inc/data.php | Obtención bloques, filtros admin |
| inc/enqueue.php | Enqueue CSS/JS global + condicional |
| inc/sanitize.php | Helpers validación (merge, clamp, etc.) |
| inc/design-controls.php | Tipografía, Google Fonts |
| inc/animation-controls.php | Motion config, presets |
| config/blocks/{type}.php | Defaults + normalizador por tipo |
| config/pages/{slug}.php | Contenido + orden de página |
| template-parts/blocks/{type}/block.php | Render HTML + data-attrs |
| template-parts/blocks/{type}/style.css | Estilos del bloque |
| template-parts/blocks/{type}/script.js | Interactividad del bloque |
| assets/css/tokens.css | Variables CSS globales |
| assets/css/base.css | Reset + estilos base |
| assets/css/layout.css | Grid, containers |
| assets/css/components.css | UI navbar, botones |
| assets/css/transitions.css | Sticky-cover, pin CSS |
| assets/css/animations.css | Keyframes |
| assets/js/app.js | Utilidades globales OKIP.* |
| assets/js/gsap-init.js | Estado GSAP/ScrollTrigger |
| assets/js/animations.js | Motor de animaciones |
| assets/js/navbar.js | Lógica navbar |

---

## 7. Archivos Críticos

⭐ **CRÍTICOS (toca = probablemente revienta algo)**
- `functions.php` - Carga de módulos
- `inc/blocks.php` - Motor de bloques
- `inc/data.php` - Filtros, overrides
- `inc/enqueue.php` - Carga de assets
- `config/pages/home.php` - Orden de bloques
- `assets/js/app.js` - Base JS
- `assets/js/animations.js` - Motion engine
- `assets/css/tokens.css` - Variables CSS
- `template-parts/layout/navbar.php` - Navbar global

⚠️ **Importantes (cambios → ttest)**
- `inc/sanitize.php` - Validación
- `config/blocks/{type}.php` - Defaults
- `template-parts/blocks/{type}/block.php` - Render
- `template-parts/blocks/{type}/script.js` - Interactividad

✅ **Flexibles (cambios seguros)**
- `template-parts/blocks/{type}/style.css` - Estilos
- `config/pages/{slug}.php` - Contenido (si no toca motor)
- `assets/css/{layout,components,transitions,animations}.css` - Estilos

---

## 8. Debugging Tips

```javascript
// Ver config motion de un bloque
const hero = document.querySelector('[data-okip-hero]');
const config = JSON.parse(hero.querySelector('[data-okip-motion-config]').textContent);
console.log(config);

// Ver si GSAP está disponible
console.log(okipGsap.ready);
console.log(okipGsap.hasScrollTrigger);

// Ver si reduce-motion activo
console.log(OKIP.reduceMotion);

// Ver clases de estado
hero.classList.contains('is-motion-entered-background'); // ¿Entrada completada?
hero.classList.contains('okip-motion-playback--variant-motion'); // ¿Preset?
```

---

## 9. Checklist: "Antes de Cambios"

- ☐ Leer `ARCHITECTURE.md` (¿qué parte toco?)
- ☐ Leer `DEPENDENCIES.md` (¿qué depende de esto?)
- ☐ Leer documentación específica (FLOW, JS_ARCH, etc.)
- ☐ Localizar función crítica (grep/search)
- ☐ Verificar donde se llama
- ☐ Cambio → test local
- ☐ Ctrl+Shift+R (cache CSS/JS)
- ☐ Inspeccionar elemento (data-attrs, clases)

