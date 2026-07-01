# OKIP Theme — Arquitectura Completa del Sistema

## 1. Visión General

**OKIP Theme** es un tema WordPress clásico completamente personalizado que implementa un **motor de bloques modular** sin dependencia de Gutenberg, ACF o page builders.

### Características Principales
- ✅ Motor de bloques propio con whitelist de seguridad
- ✅ Configuración por archivo (PHP) + overrides por admin (futuro)
- ✅ Sistema de animaciones unificado (GSAP + fallback CSS)
- ✅ Tipografía dinámica (Google Fonts condicional)
- ✅ Carga de assets condicional (solo tipos usados)
- ✅ Media-driven con fallbacks neutros
- ✅ Multipágina escalable (home + contacto + sala-de-prensa + etc.)

### Modelo de Datos
```
Una página = lista ORDENADA de instancias de bloque
Una instancia = { type, instance_id, data }
Cada instancia se renderiza independientemente (scope por id)
```

---

## 2. Capas de la Arquitectura

### Capa 1: Enrutamiento (WordPress Native)
```
HTTP Request
    ↓
WordPress Routing
    ├─ front-page.php (home)
    ├─ page.php (páginas genéricas)
    └─ single.php, 404.php (posts, errores)
```

### Capa 2: Shell (HTML + Meta)
```
header.php
├─ <!DOCTYPE html>
├─ <head> + wp_head()
│   ├─ Meta charset, viewport
│   ├─ Favicon, canonical
│   └─ CSS global + GSAP condicional
├─ <body> + wp_body_open()
├─ Skip link accesible
└─ Navbar global
```

### Capa 3: Contenido (Motor de Bloques)
```
<main id="okip-content">
    okip_render_page(okip_get_page_blocks($slug))
        ↓
    foreach (bloque como instancia):
        okip_render_block(type, instance_id, data, order)
            ↓
        get_template_part('template-parts/blocks/{type}/block')
            ↓
        Renderiza HTML + data-attrs + JSON config
</main>
```

### Capa 4: Footer
```
footer.php
├─ footer-site.php (footer temático)
├─ wp_footer()
│   └─ Scripts: app.js → gsap-init.js → animations.js → navbar.js → hero/script.js
└─ </body></html>
```

---

## 3. Sistema de Bloques (Motor Central)

### 3.1 Definición de Bloque

Un **bloque** es una unidad independiente de contenido + presentación + interactividad.

```php
// config/pages/home.php
array(
    'type'        => 'hero',                // Tipo permitido en whitelist
    'instance_id' => 'home-hero-main',      // ID legible, único por página
    'data'        => [                       // Data específica de esta instancia
        'content' => [ ... ],
        'background' => [ ... ],
        'cards' => [ ... ],
        'animation' => [ ... ],
    ]
)
```

### 3.2 Whitelist de Seguridad

**inc/blocks.php → okip_allowed_blocks()**
```
✅ Permitidos: hero, video-w-title, industry-carousel, product-story, 
              mission-statement, news
❌ Todo lo demás: rechazado silenciosamente
```

Solo bloques whitelistados se pueden renderizar. **Ningún PHP arbitrario.**

### 3.3 Flujo de Normalización

```
Raw data (parcial/ruidosa)
    ↓
okip_merge_defaults(data, schema_defaults)
    ├─ Mapas: fusiona recursivo clave a clave
    └─ Listas: data reemplaza defaults (no índice a índice)
    ↓
okip_normalize_{type}_data() [si existe]
    ├─ Whitelist de valores enum
    ├─ Clamp de rangos numéricos
    ├─ Conversión de tipos
    └─ Validación lógica
    ↓
Data limpia y tipificada
    ↓
okip_render_block() → get_template_part(..., $args)
    (block.php recibe data normalizada en $args['data'])
```

### 3.4 Scope por Instancia

Cada bloque tiene **scope aislado**:

```html
<section id="home-hero-main" data-block-instance="home-hero-main" ...>
    <!-- CSS: .okip-hero { ... } + scope por #id -->
    <!-- JS: querySelector('[data-block-instance="home-hero-main"]') -->
    <!-- Data-attrs: data-okip-hero, data-motion-enabled, data-cards-autoplay, etc. -->
</section>
```

**Convenciones:**
- `id="{instance_id}"` → ancla HTML (#home-hero-main) + scope CSS
- `data-block-instance="{instance_id}"` → selector JS único
- `data-okip-{type}` → flag para selector tipo
- `data-*` → config renderizable desde PHP a JS (no inline styles)

### 3.5 Z-index por Orden de Render

```php
// inc/blocks.php → okip_render_block()
$z = (isset($args['order']) ? (int) $args['order'] : 0) + 1;
// En template: style="--okip-hero-z: {$z};"
```

**Beneficio:** Reordenar en admin reordena automáticamente z sin conflicts.

---

## 4. Capa de Datos

### 4.1 Flujo de Origen de Datos

```
config/pages/home.php
    (defaults del tema, siempre leíble)
    ↓
okip_get_page_blocks('home')
    ├─ Lee config/pages/{slug}.php
    └─ Aplica filtro: apply_filters('okip_page_blocks', $blocks, $slug)
        ├─ [Prio 10] okip_apply_page_block_order()
        │   └─ Reordena según wp_options (okip_page_blocks_order_home)
        │   └─ Soporta remapeo: old_id → new_id (ej: parallax-monitor → video-w-title)
        │
        └─ [Prio 20] okip_apply_page_block_overrides()
            └─ Mezcla diffs guardados en wp_options (okip_page_blocks_overrides_home)
    ↓
Data lista para renderizar
```

### 4.2 Admin Panel (Futuro)

El panel guardará:
- **Orden:** Array de instance_ids en nuevo orden → `wp_options[okip_page_blocks_order_home]`
- **Overrides:** Diffs por instancia → `wp_options[okip_page_blocks_overrides_home][instance_id]`

Al renderizar:
1. Carga config/ defaults
2. Aplica orden guardada
3. Mezcla overrides
4. Renderiza resultado

**Ventaja:** Cambios en config/ del tema no sobrescriben customizaciones del admin.

### 4.3 Helpers de Datos

**okip_sanitize.php:**
- `okip_merge_defaults($data, $defaults)` - Merge recursivo
- `okip_array_diff_recursive($new, $base)` - Diff para guardar
- `okip_one_of($value, $allowed, $default)` - Whitelist
- `okip_clamp_int/float($value, $min, $max)` - Rango
- `okip_bool($value)` - Conversión flexible
- `okip_is_list($arr)` - Detecta lista vs mapa

**okip_media.php:**
- `okip_media_url($value)` - ID attachment → URL
- `okip_media_exists($value)` - Verifica existencia
- `okip_media_alt($value)` - Alt de attachment

**okip_design_controls.php:**
- `okip_normalize_typography($data, $preset)` - Tipografía
- `okip_collect_page_google_fonts($blocks)` - Recolecta fuentes

**okip_animation_controls.php:**
- `okip_normalize_motion($motion, $targets)` - Motion config
- `okip_motion_config_json()` - Serializa JSON

---

## 5. Sistema de Assets (Enqueue Condicional)

### 5.1 CSS Global (Siempre)

```
Orden (cascada de dependencias):
1. Google Fonts (colectadas dinámicamente del contenido)
2. tokens.css (variables CSS)
3. base.css (reset + utilidades)
4. layout.css (grid, containers)
5. components.css (botones, cajas, etc.)
6. transitions.css (sticky-cover, animations)
7. animations.css (keyframes GSAP)
```

### 5.2 GSAP (Condicional)

```php
if (file_exists(OKIP_DIR . '/assets/vendor/gsap/gsap.min.js')) {
    wp_enqueue_script('gsap', ...);
    if (file_exists(OKIP_DIR . '/assets/vendor/gsap/ScrollTrigger.min.js')) {
        wp_enqueue_script('gsap-scrolltrigger', ...);
    }
}
wp_localize_script('okip-gsap-init', 'OKIP_ENV', [
    'hasGsap' => true,
    'hasScrollTrigger' => true,
]);
```

**Sin CDN.** Si archivos no existen, fallback a CSS vanilla.

### 5.3 JS Global (Siempre)

```
Orden (dependencias):
1. app.js (bootstrap: OKIP.ready(), OKIP.reduceMotion, etc.)
2. gsap-init.js (deps: app + GSAP si existe, expone okipGsap.ready)
3. animations.js (deps: gsap-init, controller de motion)
4. navbar.js (deps: animations, lógica navbar)
```

### 5.4 Assets por Bloque (Condicional)

```php
okip_enqueue_block_assets(okip_used_block_types($blocks))
    // Para cada tipo usado en la página actual:
    // - template-parts/blocks/{type}/style.css (si existe, deps: components)
    // - template-parts/blocks/{type}/script.js (si existe, deps: animations)
    // Handle: okip-block-{type}
    // Versión: filemtime() del archivo
```

**Beneficio:** Si la home no usa news, no se cargan assets de news.

### 5.5 Clase `okip-js` (Flash Mitigation)

```php
// inc/enqueue.php → okip_html_js_class()
echo "<script>document.documentElement.classList.add('okip-js');</script>";
// En wp_head prioridad 1 (ANTES de </head>)
```

**Uso:**
```css
html:not(.okip-js) .okip-hero__content { display: block; }
html.okip-js .okip-hero__content { display: none; } /* inicialmente oculto por motion */
```

Evita flash de contenido si JS no carga.

---

## 6. Sistema de Animaciones

### 6.1 Concepto Unificado

**Motion** = Animaciones estructuradas en 3 fases: **entry, playback, exit**

```javascript
{
    motion: {
        enabled: true,
        replay_mode: 'once',              // once | replay
        exit_trigger: 'viewport_leave',   // viewport_leave | none
        background: {
            entry: { preset, duration_ms, delay_ms, stagger_ms, ease, opacity_from/to, y_from/to, scale_from/to, blur_from/to },
            playback: { ... + intensity, speed, direction, yoyo },
            exit: { ... }
        },
        text: { entry, exit },
        cards: { entry, playback, exit }
    },
    selectors: {
        background: '[data-okip-motion-target="background"]',
        text: '[data-okip-motion-target="text"]',
        cards: '[data-okip-motion-target="cards"]'
    }
}
```

### 6.2 Fases Explicadas

**Entry:** Reveal al entrar en viewport
- `duration_ms`: Duración total (default 700ms)
- `delay_ms`: Espera antes de iniciar (default 0)
- `stagger_ms`: Delay entre items (default 0)
- Transforms: opacity_from, x/y_from, scale_from, blur_from
- Ease: easing function (power3.out default)

**Playback:** Movimiento continuo mientras está en pantalla
- `duration_ms`: Ciclo completo (default 4200ms)
- `intensity`: Magnitud del movimiento (0..1)
- `speed`: Multiplicador de duración (default 1)
- `direction`: alternate | normal | reverse
- `yoyo`: Invierte dirección en ciclos (default true)

**Exit:** Fade-out al salir del viewport
- Similar a entry, pero reverso

### 6.3 Sistema Híbrido (GSAP + CSS)

```
Opción 1: GSAP disponible
    okipGsap.ready = true
    ↓
    animateCss() crea timeline GSAP con transforms
    (suave, responsive, con fallback de mobile)

Opción 2: GSAP no disponible
    okipGsap.ready = false
    ↓
    animateCss() aplica CSS transitions + JavaScript puro
    (menos smooth, pero funcional)

Opción 3: prefers-reduced-motion
    Aplica cambios de estado SIN animación (entrada instant)
    Mantiene interactividad
```

### 6.4 Presets

Presets pre-configurados por target × phase:

**Background:**
- entry: soft-arrive (blur in), fade-blur, rise-depth
- playback: variant-motion (liquido), slow-drift, pulse-field
- exit: fade-depth, soft-blur-out

**Text:**
- entry: stagger-fade-up (sube + aparece), soft-mask-up, fade-only
- exit: fade-up, fade-down

**Cards:**
- entry: stagger-float-up (flota hacia arriba), scale-fade, slide-depth
- playback: float-soft, glow-pulse
- exit: fade-scale, float-down

---

## 7. Sistema de Tipografía

### 7.1 Google Fonts Dinámicas

```
config/pages/home.php
    ├─ typography: { font_family: 'Montserrat', font_weight: 300, ... }
    └─ typography: { font_family: 'Inter', font_weight: 400, ... }
    ↓
okip_enqueue_assets()
    ├─ okip_collect_page_google_fonts(bloques)
    │   └─ Recorre data, extrae todas las fuentes
    ├─ okip_google_fonts_url(fonts)
    │   └─ Genera URL única: https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@300;600...
    └─ wp_enqueue_style('okip-google-fonts', url, ...)
```

**Beneficio:** Solo se cargan los pesos realmente usados.

### 7.2 Presets

Presets de tipografía por uso:
- `body` - Texto base
- `hero_title` - Montserrat, 300, fluid (36px..62px), blanco
- `hero_description` - Inter, 400, fluid (16px..22px), azul claro
- `kicker` - Pequeño, uppercase, letterspaced

### 7.3 Fluid Typography

```css
font-size: clamp(36px, 4.2vw, 62px);
```

Entre 36px (viewport chico) y 62px (viewport grande), escala fluidamente.

### 7.4 Variables CSS

```php
okip_typography_css_vars('okip-hero-title', $typo)
// Genera:
// --okip-hero-title-font-family: "Montserrat", var(--okip-font-base);
// --okip-hero-title-font-size: clamp(36px, 4.2vw, 62px);
// --okip-hero-title-font-weight: 300;
// --okip-hero-title-line-height: 1.08;
// --okip-hero-title-letter-spacing: 0px;
// --okip-hero-title-color: #ffffff;
```

---

## 8. Bloques Implementados

### 8.1 Hero

**Instancia:** `home-hero-main`

**Estructura:**
- Capas: Fondo media/CSS → overlay → tarjetas → texto
- Escena dual-video: intro (una vez) → crossfade → loop (bucle)
- Tarjetas flotantes con reproducción interactiva
- Autoplay automático de tarjetas (opcional)

**Motion:** Background, text, cards (3 fases)

**Conocido por:**
- Sticky position (no Pin)
- Z-index dinámico por orden
- Media-driven (fallback CSS color)

### 8.2 Video with Title

**Instancia:** `home-video-w-title`

**Estructura:**
- Video de fondo full-screen
- Overlay oscuro
- Texto centrado (título con highlight negrita + subtítulo + descripción)

**Transition:** Sticky-cover (CSS, no Pin)

**Conocido por:**
- Reemplaza antiguo parallax-monitor
- Hold_vh reserva scroll extra
- Reveal de entrada (IO 15% superior)

### 8.3 Industry Carousel

**Instancia:** `home-industry-carousel`

**Estructura:**
- Botones tipo tabs (blanco, monospace)
- Relleno animado en botón activo
- Track horizontal de tarjetas grandes

**Desktop (≥1025px):** ScrollTrigger pin + scrub horizontal
**Móvil (≤1024px):** Static, scroll nativo

**Conocido por:**
- ScrollTrigger = horizontal-pin transition mode
- Progreso segmentado Math.round(progress × (N-1))

### 8.4 Product Story, Mission Statement, News

*Configurados pero no analizados en profundidad en este documento.*

---

## 9. Navbar Global

### 9.1 Configuración

**config/blocks/navbar.php:**
- Logo (texto + imagen)
- Menú (desde WordPress 'primary' o fallback)
- Appearance (colores, blur)
- Reveal (after_hero | always | manual)

### 9.2 Comportamiento

En Home con Hero:
1. Navbar nace oculto (`okip-navbar--start-hidden` class server-side)
2. JS detecta cuando bloque siguiente tapa ~85% del Hero
3. Navbar aparece (transición suave)
4. Al volver arriba, se oculta

### 9.3 Interactividad

- Hamburguesa en móvil (aria-expanded, Escape key)
- Clase `is-scrolled` cuando scrollY > 8px
- Menú móvil colapsable (accesible)

---

## 10. Decisiones Arquitectónicas Deliberadas

| Decisión | Por qué |
|----------|--------|
| Motor propio + whitelist | Seguridad (no PHP arbitrario) |
| config/ + filtros | Evolución sin tocar motor |
| Instance_id manual | Anclas HTML, scope CSS, estabilidad |
| Z-index dinámico | Reordenamiento seguro |
| GSAP condicional | Fallback vanilla, sin CDN |
| Media-driven | Nunca media roto |
| Motion system | Reutilizable, modular |
| Merge (listas reemplazan) | Coherente con edit admin |
| Transition.mode | CSS para suavidad, pin JS para coreografía |

---

## 11. Puntos de Acoplamiento

1. **Hero ↔ Navbar:** Hero indica cuándo está cubierto (geometría del bloque siguiente)
2. **Block order ↔ Z-index:** Orden render = Z escala automática
3. **Motion JSON ↔ JS:** Config JSON en template → JS interpreta
4. **Transition.mode ↔ CSS/JS:** sticky-cover es CSS puro, pin es ScrollTrigger
5. **Typography ↔ Google Fonts:** Tipografía en data → URL dinámica generada

---

## 12. Escalabilidad: Añadir un Bloque Nuevo

1. **inc/blocks.php:** Añade tipo a `okip_allowed_blocks()`
2. **config/blocks/{type}.php:** Retorna defaults + normalizador
3. **template-parts/blocks/{type}/block.php:** Renderiza HTML
4. **template-parts/blocks/{type}/style.css:** Estilos (opcional)
5. **template-parts/blocks/{type}/script.js:** Interactividad (opcional)
6. **config/pages/home.php:** Añade instancia

**Eso es todo.** Motor, enqueue, admin se reutilizan automáticamente.

