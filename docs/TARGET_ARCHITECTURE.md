# OKIP Theme — Arquitectura Objetivo (Plan Maestro de Evolución)

> **Estado:** Documento de decisiones de ingeniería. No es código, no describe el estado
> actual, describe **hacia dónde evoluciona el tema**.
> **Fuente de conocimiento del estado actual:** `docs/audit/*` (auditoría previa).
> **Ejecución:** `docs/ROADMAP.md` (etapas) + `docs/BACKLOG.md` (mejoras priorizadas).

---

## 0. Cómo leer este documento

Este documento define el **destino**. El roadmap define el **camino**. El backlog define
el **inventario de trabajo**. Ninguna etapa del roadmap puede contradecir las reglas de aquí.

Regla de oro de toda la evolución (no negociable):

> **Ninguna etapa puede dejar la landing rota, ni cambiar el comportamiento visual salvo
> corrección de un bug real. Cada cambio es pequeño, aislado, reversible y verificable.**

### Correcciones de la auditoría (leer antes de confiar ciegamente en `docs/audit/`)

La auditoría es la fuente de verdad conceptual, pero tiene imprecisiones detectadas al
contrastarla con el código real. Prevalecen estas correcciones:

1. **Orden de carga JS:** el CSS se encola en `<head>`; **el JS global se encola en el
   footer** (`wp_enqueue_script(..., true)` en `inc/enqueue.php`). El diagrama de
   `docs/audit/FLOW.md §3` que sitúa los scripts en `<head>` es incorrecto.
2. **Estado del admin:** NO son stubs vacíos. Existe implementación real: panel de orden
   (`inc/admin/admin-pages.php`), editores por bloque (`inc/admin/editors/*`),
   `save-handlers.php`, `sanitizers.php` (458 líneas) y `assets/js/admin-blocks.js`
   (668 líneas). `CLAUDE.md` (que dice "stubs") está desactualizado en este punto.
3. **CSS de auditoría usa sintaxis anidada (SCSS-like)** en ejemplos; el CSS real del tema
   es CSS plano. Los ejemplos anidados son ilustrativos, no literales.

Cuando `CLAUDE.md`, `docs/audit/*` y el código difieran: **manda el código**, luego este
documento, luego la auditoría, y por último `CLAUDE.md`.

---

## 1. Visión a 1–2 años

OKIP debe convertirse en una **plataforma de composición de páginas por bloques** para un
tema WordPress clásico, donde:

- **El contenido lo define un editor** (panel admin) guardando *overrides* en `wp_options`,
  nunca tocando archivos del tema. `config/` queda como **esquema + defaults**, no como
  contenido editable.
- **Añadir un bloque** es una operación mecánica de 6 pasos (ya lo es) que **no requiere
  tocar el núcleo** (motor, enqueue, animaciones, admin genérico).
- **Las animaciones son declarativas**: un bloque describe *qué* quiere (motion/transition
  en config) y el núcleo decide *cómo* ejecutarlo (GSAP, sticky CSS, IO o fallback vanilla).
  Ningún bloque reimplementa la orquestación de scroll desde cero.
- **El orden es 100% dinámico**: ningún archivo CSS/JS asume qué bloque va antes o después.
  Todo acoplamiento entre vecinos se resuelve por geometría/DOM en runtime.
- **La calidad es verificable**: existe un checklist de aceptación por bloque, lint PHP/JS
  en verde, y un protocolo de verificación visual documentado (lo único no automatizable).

El "norte" no es reescribir: es **consolidar patrones ya presentes** y **eliminar
duplicación** para que crecer sea barato y seguro.

---

## 2. Arquitectura de carpetas — responsabilidades objetivo

El árbol actual es correcto. La evolución **afina responsabilidades**, no reorganiza por
reorganizar. Regla: cada carpeta tiene UNA razón para cambiar.

| Carpeta | Responsabilidad única | Qué NO debe contener |
|---|---|---|
| `functions.php` | Bootstrap: constantes + `require` de `inc/`. | Lógica de negocio, HTML, enqueue. |
| `inc/` | **Núcleo**: motor, datos, enqueue, helpers, sistemas transversales. | Presentación de bloques, contenido. |
| `inc/admin/` | **Núcleo de edición**: panel, campos, guardado, saneo. Genérico y reutilizable. | Reglas específicas de un bloque salvo su editor/sanitizer. |
| `config/blocks/{type}.php` | **Esquema + defaults + normalizador** de un tipo. | Contenido real de una página. |
| `config/pages/{slug}.php` | **Lista ordenada de instancias** + su data de arranque. | Lógica; solo estructura de datos. |
| `template-parts/blocks/{type}/` | **Presentación autocontenida** (`block.php` + `style.css` + `script.js`). | Lógica de negocio del núcleo, defaults. |
| `template-parts/layout/` | Shell repetible (navbar, footer). | Bloques de contenido. |
| `assets/css/` | **CSS global**: tokens, base, layout, componentes, transiciones, keyframes. | Estilos específicos de un bloque. |
| `assets/js/` | **Núcleo JS**: bootstrap, gsap-init, motor de animaciones, navbar, admin. | Lógica de un bloque concreto. |
| `assets/vendor/` | Dependencias locales (GSAP). | Código propio. |
| `docs/` | Conocimiento del proyecto (auditoría + plan). | — |

**Cambio estructural aceptado (opcional, etapa avanzada):** dividir `assets/js/` en
subcarpetas por rol (`core/`, `animation/`) SOLO si el volumen lo justifica. Ver §5.

---

## 3. Sistema de bloques — contrato objetivo

### 3.1 Responsabilidad de un bloque

Un bloque es responsable de **su presentación y su interactividad local**. Nada más.

Un bloque **PUEDE**:
- Renderizar su HTML a partir de `data` ya normalizada (`$args['data']`).
- Declarar su `motion` y su `transition` en config (declarativo).
- Emitir `data-*` para que su `script.js` lo lea.
- Tener CSS/JS propios, cargados solo si el tipo se usa en la página.
- Resolver su media con fallback neutro (`okip_media_exists`).

Un bloque **NO PUEDE**:
- Asumir qué bloque tiene al lado (ni por tipo, ni por posición). Si necesita al vecino,
  lo deriva por DOM (`nextElementSibling` con `data-block-instance`).
- Fijar su `z-index` con un número mágico: lo recibe por orden (`$order + 1`).
- Registrar su propio ScrollTrigger "a mano" reinventando pin/scrub cuando el modo de
  transición del núcleo ya lo cubre.
- Leer variables globales de otro bloque, ni escribir fuera de su scope `#{instance_id}`.
- Escribir contenido editable en archivos del tema.

### 3.2 Contrato obligatorio de un bloque nuevo

Estructura mínima y qué es obligatorio vs. opcional:

```
config/blocks/{type}.php            [OBLIGATORIO]  defaults + okip_normalize_{type}_data()
template-parts/blocks/{type}/
    block.php                       [OBLIGATORIO]  render escapado, scope por instancia
    style.css                       [OPCIONAL]     estilos del bloque (BEM okip-{type}__*)
    script.js                       [OPCIONAL]     interactividad (guard __okip{Type}Init)
    README.md                       [OBLIGATORIO en bloques nuevos]  ver §3.3
inc/admin/editors/{type}.php        [OBLIGATORIO cuando sea editable]  panel admin
inc/admin/sanitizers → okip_admin_sanitize_{type}_data()  [OBLIGATORIO si editable]
```

Además, todo bloque nuevo debe registrarse en `okip_allowed_blocks()` (whitelist) y añadir
su instancia a un `config/pages/{slug}.php`.

### 3.3 `README.md` por bloque (nueva convención obligatoria)

Cada bloque nuevo (y progresivamente los existentes) incluye un `README.md` con:

1. **Propósito** y referencia visual (`referencias/*.png`).
2. **Grupos de config** (`content`, `background`, `layout`, `motion`, `transition`, …) con
   whitelists, rangos y defaults.
3. **Contrato de animación**: qué modo de `transition` usa y por qué; qué targets de motion
   expone; qué pasa sin GSAP y con `reduce-motion`.
4. **Data-attrs y eventos** que emite/escucha.
5. **Dependencias del vecino** (si las hay) y cómo se resuelven por DOM.
6. **Checklist de verificación visual** específico.

El README es el "contrato" que permite a una conversación futura tocar el bloque sin releer
todo el código.

### 3.4 Responsabilidad del núcleo (`inc/` + `assets/js` + `assets/css` globales)

El núcleo es responsable de todo lo **transversal y reutilizable**:

- **Motor** (`inc/blocks.php`): whitelist, normalización, render, z por orden.
- **Datos** (`inc/data.php`): carga de config, filtros de orden y overrides (admin).
- **Enqueue** (`inc/enqueue.php` + `inc/block-loader.php`): carga condicional por tipo.
- **Helpers** (`inc/sanitize.php`, `inc/media.php`): saneo, merge, media-driven.
- **Sistemas declarativos** (`inc/animation-controls.php`, `inc/design-controls.php`):
  motion y tipografía normalizados a JSON/CSS-vars.
- **Motor de animación cliente** (`assets/js/animations.js`): ejecuta motion (entry/
  playback/exit) con GSAP o fallback.
- **Transiciones** (`assets/css/transitions.css` + helpers de `inc/sanitize.php`): sistema
  híbrido `transition.mode`.
- **Navbar** (`assets/js/navbar.js`): visibilidad desacoplada por geometría.

Regla de frontera: **si dos bloques lo necesitan, sube al núcleo; si solo uno lo necesita,
vive en el bloque.**

---

## 4. Sistema de animaciones unificado

"Unificado" = **mismas reglas de decisión**, no mismas animaciones. Un bloque nunca elige
tecnología por gusto; la elige por la **categoría de efecto** que necesita.

### 4.1 Árbol de decisión canónico (memorizar)

```
¿Qué necesito?
│
├─ Revelar contenido al entrar en viewport (una vez o repetible)
│     → IntersectionObserver "línea de disparo" (rootMargin -15%/-85%)
│       para AÑADIR clases de estado; la animación la ejecuta el motor
│       (motion entry) vía GSAP o CSS. NUNCA scroll listener para esto.
│
├─ Movimiento continuo mientras el bloque es visible (drift/float/pulse)
│     → motion.playback (motor animations.js). GSAP timeline con yoyo,
│       o fallback rAF/CSS. Se pausa con reduce-motion y pestaña oculta.
│
├─ Que un bloque "cubra" al anterior al hacer scroll (traspaso simple 1:1)
│     → transition.mode = "sticky-cover" (CSS puro). El OUTER es sticky,
│       el contenido va en .okip-cover-stage. SIN ScrollTrigger.
│
├─ Coreografía atada al progreso del scroll (scrub) o pin real
│  (carrusel horizontal, secuencia narrativa, timeline por scroll)
│     → transition.mode = "scrolltrigger-pin" / "horizontal-pin".
│       UN solo ScrollTrigger por nodo. end por medidas reales
│       (invalidateOnRefresh). Fallback estático en ≤1024px.
│
└─ Nada de lo anterior
      → transition.mode = "none".
```

### 4.2 Reglas por tecnología

**GSAP — cuándo:**
- Siempre que exista (`okipGsap.ready`) para ejecutar `motion` (entry/playback/exit) porque
  da suavidad GPU y timelines coordinadas.
- Obligatoriamente para cualquier coreografía **scrub/pin** (ScrollTrigger).
- **Nunca** como dependencia dura: todo camino GSAP tiene fallback vanilla equivalente en
  comportamiento (no necesariamente en suavidad).

**ScrollTrigger — cuándo:**
- Solo para efectos **atados al progreso del scroll**: pin real, scrub, horizontal.
- **Un ScrollTrigger por nodo.** Prohibido dos ST peleando por el mismo elemento
  (ver trampa conocida del `industry-carousel`).
- `end` y distancias **siempre** de medidas reales del DOM con `invalidateOnRefresh:true`,
  nunca `vh` arbitrarios.

**Sticky CSS (`sticky-cover`) — cuándo:**
- Traspaso de cobertura simple entre un bloque y el siguiente (Hero→Bloque2, Bloque2→Bloque3).
- Preferido sobre pin de ScrollTrigger para cobertura 1:1 porque es suave a cualquier
  velocidad de scroll (los pins se saltan start/end en scroll rápido).
- El `position:sticky` va en el **OUTER** (su padre es `<main>`), el contenido en
  `.okip-cover-stage`. (Ver `CLAUDE.md §5` y la trampa documentada.)

**IntersectionObserver — cuándo:**
- Disparar reveals (añadir `.is-revealed` / clases de estado) una vez.
- Detectar entrada/salida de viewport para lanzar motion `exit` (`watchExit`).
- Actualizar el ítem activo en modos estáticos (carrusel móvil).
- **Nunca** para animar frame a frame (eso es scrub → ScrollTrigger).

**Scroll listeners — cuándo:**
- Solo para navbar (visibilidad) y pausa por cobertura, y **siempre** con
  `OKIP.rafThrottle` y **una** lectura de layout por frame (ver perf del navbar en `CLAUDE.md §8`).
- Preferir IO sobre scroll listener siempre que se pueda.

### 4.3 ¿La animación es del bloque o del núcleo?

| Pertenece al NÚCLEO | Pertenece al BLOQUE |
|---|---|
| El motor `motion` (entry/playback/exit) y sus presets genéricos. | La *elección* de preset/params en su `config`. |
| Los modos de `transition` (sticky-cover, pin, horizontal). | *Qué* modo usa y sus valores (`hold_vh`, etc.). |
| Keyframes reutilizables (`animations.css`). | Coreografía única e irrepetible (p. ej. la lógica de segmentos del carrusel). |
| Fallbacks y respeto a `reduce-motion`. | Interacciones locales (hover de tarjetas del Hero). |

Regla: **si un tercer bloque podría querer el mismo efecto, va al núcleo como preset/modo.**
Si es irrepetiblemente específico, vive en el `script.js` del bloque **pero respetando las
reglas de tecnología de §4.2**.

### 4.4 Invariantes de animación (toda etapa las respeta)

1. Sin JS o sin GSAP → el contenido queda **visible** y funcional (nunca oculto para siempre).
2. `prefers-reduced-motion` → sin movimiento continuo; reveals instantáneos; interactividad
   intacta.
3. El estado inicial oculto lo **arma el JS** (no `.okip-js` a secas) cuando su ausencia
   dejaría texto invisible (patrón `is-anim-armed` de `video-w-title`).
4. Reordenar bloques no rompe el apilado ni las transiciones (z por orden, vecino por DOM).

---

## 5. Arquitectura JavaScript objetivo

### 5.1 Principio

Mantener el modelo actual **por capas de dependencia explícita** (`wp_enqueue_script` deps),
que ya funciona y es depurable. **No** introducir bundler/módulos ES ni build step: rompería
la simplicidad "sin CDN, sin toolchain" del proyecto y el versionado por `filemtime`.

### 5.2 Estructura

**Fase actual (mantener):** archivos planos en `assets/js/`:

```
assets/js/
  app.js          núcleo: OKIP.{ready, reduceMotion, clamp, readInt/Float, rafThrottle, toArray}
  gsap-init.js    estado GSAP → okipGsap.{ready, hasScrollTrigger}
  animations.js   motor motion → OKIPAnimations.{create, parseConfig}
  navbar.js       visibilidad navbar (desacoplada)
  admin-blocks.js JS del panel admin (solo en admin)
```

**Fase avanzada (opcional, SOLO si el núcleo JS crece mucho — ver BACKLOG):** agrupar por rol
sin cambiar el modelo de carga (siguen siendo scripts encolados, no módulos):

```
assets/js/core/        app.js, gsap-init.js
assets/js/animation/   animations.js  (+ presets si se extraen)
assets/js/ui/          navbar.js
assets/js/admin/       admin-blocks.js
```

Justificación de NO adoptar `helpers/ transition/ scroll/ observer/` como carpetas separadas
ahora: crearía muchos archivos diminutos y más handles de enqueue para poca ganancia. La
separación **conceptual** (helpers, transición, scroll, observer) ya existe y basta con
mantenerla **dentro** de `app.js`/`animations.js` como secciones. Se promueve a carpeta solo
cuando un archivo supere ~400–500 líneas o se comparta lógica entre 3+ consumidores.

### 5.3 Reglas JS (todas obligatorias)

- Todo bloque arranca con `OKIP.ready(fn)` y un **guard de idempotencia** (`__okip{Type}Init`).
- Scope estricto: selectores **dentro** del root del bloque, nunca `document.querySelectorAll`
  global para elementos que otros bloques puedan tener (ver `docs/audit/JS_ARCHITECTURE.md §8`).
- Scroll/resize: `OKIP.rafThrottle`, una lectura de layout por frame, geometría cacheada y
  recomputada en `resize`, no por frame.
- Comunicación PHP→JS: `data-*` (kebab) + `<script type="application/json">` para config
  compleja. Nunca variables globales sueltas (usar `OKIP_ENV` vía `wp_localize_script`).
- Comunicación entre piezas: **CustomEvents namespaced** `okip:*` (ver §7), no acoplamiento
  directo.
- Manejo de errores defensivo: `try/catch` al parsear JSON de config; degradar a visible.

---

## 6. Arquitectura CSS objetivo

### 6.1 Qué es global y qué es local

**Global (`assets/css/`, siempre en cascada):**
- `tokens.css` — **única fuente de verdad** de variables de diseño (colores, espacios, radios,
  z-index base, easings, tipografía base). Nada de valores mágicos fuera de aquí.
- `base.css` — reset + accesibilidad (`:focus-visible`, `sr-only`, skip link, `reduce-motion`).
- `layout.css` — grid de `<main>`, containers, breakpoints.
- `components.css` — UI **reutilizable entre bloques** (navbar, botones, chips).
- `transitions.css` — sistema `transition.mode` (sticky-cover, pin).
- `animations.css` — keyframes y clases de estado del motor de motion.

**Local (`template-parts/blocks/{type}/style.css`):**
- Todo lo específico del bloque, en BEM `okip-{type}__elemento--modificador`.
- Consume tokens y variables generadas (`--okip-{type}-*`), no redefine paleta.

### 6.2 Tokens (lo que debe existir)

- **Color:** fondo/elevado/texto/muted/acento/línea + superficies claras. Semánticos, no por
  nombre de color (`--okip-color-accent`, no `--okip-orange`).
- **Espaciado:** escala modular (`--okip-space-1..6`).
- **Layout:** `--okip-container`, `--okip-container-pad`, `--okip-radius`, `--okip-z-navbar`.
- **Tipografía:** stacks base/mono + variables generadas por `design-controls`.
- **Movimiento:** `--okip-ease`, duraciones estándar (a introducir: `--okip-dur-fast/base/slow`).
- **Z-index:** una **escala nombrada** (a introducir, ver BACKLOG) para evitar números mágicos
  (`999`, `1000`) dispersos: `--okip-z-base/content/overlay/navbar`.

### 6.3 Utilidades (mínimas, con prefijo)

Mantener utilidades escasas y con prefijo `okip-`: estados (`is-hidden`, `is-visible`,
`is-scrolled`), visibilidad responsiva (`okip-desktop-only`), aspect-ratios. **No** adoptar un
framework de utilidades (Tailwind-like): choca con la filosofía sin toolchain.

### 6.4 Convenciones CSS

- BEM estricto `okip-<bloque>__<el>--<mod>`; estado con `is-` (aplicado por JS).
- Sin CSS inline salvo variables seguras y clampadas (`--okip-card-x`, `object-position`).
- Nada de `!important` salvo utilidades de estado documentadas.
- Breakpoints canónicos: `768px` (móvil), `1024/1025px` (tablet/desktop; frontera de
  hamburguesa y de ScrollTrigger).
- Escapar/serializar variables desde PHP con helpers (`okip_css_vars`), no concatenación cruda.

---

## 7. Convenciones oficiales del proyecto

Estas reglas aplican a **todo bloque y toda etapa futura**. Son ley.

### 7.1 Nombres

| Elemento | Convención | Ejemplo |
|---|---|---|
| Funciones PHP | `okip_` + snake_case | `okip_render_block()` |
| Funciones admin | `okip_admin_` | `okip_admin_sanitize_hero_data()` |
| Normalizador de tipo | `okip_normalize_{type}_data()` | `okip_normalize_hero_data()` |
| Handles WP | `okip-` | `okip-navbar`, `okip-block-hero` |
| Clases CSS | BEM `okip-<b>__<e>--<m>` | `okip-hero__title-line--primary` |
| Clases de estado (JS) | `is-` | `is-revealed`, `is-hero-paused` |
| Data-attrs flag | `data-okip-{type}` | `data-okip-hero` |
| Data-attrs config | `data-{grupo}-{clave}` (kebab) | `data-cards-autoplay-min` |
| Instance id | `{page}-{type}[-{variante}]` legible | `home-video-w-title` |
| Variables CSS | `--okip-[{scope}-]{prop}` | `--okip-hero-title-color` |
| CustomEvents | `okip:{dominio}-{evento}` | `okip:pm-cover`, `okip:nav-reveal` |
| Options (admin) | `okip_page_blocks_{order|overrides}_{slug}` | `okip_page_blocks_order_home` |

### 7.2 GSAP / ScrollTrigger

- Registrar plugins **solo** en `gsap-init.js`. Los bloques leen `okipGsap.ready`.
- Un ScrollTrigger por nodo; `invalidateOnRefresh:true`; `end` por medida real.
- Toda animación GSAP tiene rama de fallback y rama `reduce-motion`.
- `will-change`/contexto 3D solo en el elemento que se anima (no en el que centra).

### 7.3 PHP / seguridad

- Escapar toda salida: `esc_html`, `esc_url`, `esc_attr`, `wp_kses_post`.
- Nunca renderizar un tipo fuera de la whitelist.
- En `config/blocks/{type}.php`: funciones **antes** del `return` y con `function_exists()`.
- Nunca escribir contenido editable en archivos del tema (va a `wp_options`).
- Estilo `array()` por convención; el hint PHP7103 (short array) se ignora.

### 7.4 Datos

- `okip_merge_defaults`: los mapas se fusionan; **las listas se reemplazan enteras**.
- Overrides del admin = **diff** contra defaults (`okip_array_diff_recursive`); borrar el
  option restaura defaults.

---

## 8. Riesgos — mapa de zonas delicadas

### 8.1 Archivos críticos (tocar = alto riesgo de romper la landing)

| Archivo | Por qué es crítico | Mitigación |
|---|---|---|
| `functions.php` | Bootstrap: un `require` roto tumba el tema. | Lint PHP obligatorio; cambiar de a uno. |
| `inc/blocks.php` | Motor: whitelist, normalize, render, z por orden. | No cambiar firmas; test con `var_dump(okip_get_page_blocks())`. |
| `inc/data.php` | Filtros de orden/overrides; remapeo de ids. | Verificar orden guardado en admin tras cambios. |
| `inc/enqueue.php` | Orden y deps de todos los assets. | Revisar cascada CSS y deps JS; smoke HTTP. |
| `config/pages/home.php` | Orden y data de arranque de la home. | Cambios de contenido, no de estructura, salvo etapa dedicada. |
| `assets/js/app.js` | Base de todo el JS (`OKIP.*`). | No romper API pública; es dependencia de todo. |
| `assets/js/animations.js` | Motor de motion de todos los bloques. | Cambios detrás de fallback; verificar Hero + un bloque más. |
| `assets/css/tokens.css` | Variables que consume todo el CSS. | Añadir tokens, no renombrar los existentes sin barrido. |
| `template-parts/layout/navbar.php` + `navbar.js` | Visibilidad global desacoplada. | Probar Home (oculto en Hero) y páginas internas. |
| `assets/css/transitions.css` | Sistema sticky-cover de toda transición. | No mover el sticky del outer al stage (trampa). |

### 8.2 Dependencias que pueden romper la landing

1. **Orden de la cascada CSS** (`enqueue.php`): si cambia, componentes pierden tokens/base.
2. **Deps de `wp_enqueue_script`**: `animations.js` depende de `gsap-init` que depende de
   `app`. Romper la cadena rompe todo el motion.
3. **Z por orden**: cualquier `layout.z_index > 0` en config rompe la escalera de apilado
   (sticky-cover necesita que el siguiente tenga z mayor). Default debe ser `0` (auto).
4. **Vecino por DOM**: navbar y pausa del Hero derivan el "coverBlock" del `nextElementSibling`
   con `data-block-instance`. Si un bloque nuevo no emite ese atributo, se rompe el reanclaje.
5. **Contrato motion JSON ↔ JS**: si el schema del JSON de config cambia sin actualizar
   `animations.js`, el bloque no anima (o rompe el `JSON.parse`).
6. **Media-driven**: `okip_media_exists` gobierna fallback; un cambio ahí afecta a todos los
   fondos y tarjetas.
7. **Remapeo de instance_id** (`okip_page_block_order_remap`): al renombrar un bloque, sin
   remapeo el orden guardado en admin se desincroniza.

### 8.3 Archivos que requieren verificación visual extra (no automatizable)

Cualquier cambio que roce estos debe pasar por el **protocolo de verificación visual**
(navegador, `Ctrl+Shift+R`):

- Hero: escena dual-video, tarjetas (hover/autoplay), pausa al cubrir.
- `video-w-title`: reveal armado por JS, sticky-cover de salida.
- `industry-carousel`: pin horizontal, relleno de botones, fallback móvil.
- Navbar: oculto en Hero, aparece al cubrir ~85%, hamburguesa ≤1024px.
- Transiciones entre bloques a distintas velocidades de scroll (rápido incluido).

### 8.4 Deuda/known-traps a no repetir (resumen; detalle en `CLAUDE.md §11`)

- No dos ScrollTriggers sobre el mismo nodo.
- `end` de pin por medida real, no `vh` arbitrario.
- Índice activo con `Math.round`, relleno con `Math.floor`+local (carrusel).
- Sticky en el OUTER, no en el stage.
- Reveal de `video-w-title` armado por JS (`is-anim-armed`), no por `.okip-js`.
- No animar `.okip-hero__card` (centra con translate); animar `.okip-hero__card-media`.

---

## 9. Definición de "listo" para cualquier etapa

Una etapa del roadmap está terminada cuando:

1. ✅ Lint PHP en verde (contenedor) y `node --check` de todo JS tocado.
2. ✅ Smoke HTTP 200 de la home y de cada página afectada.
3. ✅ Comportamiento visual **idéntico** al previo (o el bug corregido, documentado).
4. ✅ Verificación visual manual completada según §8.3 cuando aplique.
5. ✅ Sin `z-index` mágicos nuevos, sin scroll listeners sin throttle, sin selectores globales.
6. ✅ Docs actualizadas (README del bloque y/o `CLAUDE.md` si cambió una regla).
7. ✅ Cambio pequeño, revisado, con estrategia de reversión clara (git revert del commit).

Este documento + `ROADMAP.md` + `BACKLOG.md` son suficientes para que una conversación futura
ejecute **una** etapa sin reinterpretar la arquitectura.
