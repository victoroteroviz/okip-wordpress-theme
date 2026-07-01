# OKIP Theme — Principios de Ingeniería (Constitución del Proyecto)

> **Estado:** Autoridad máxima del proyecto. No describe el estado actual ni el roadmap:
> describe las **reglas que nunca deben romperse**.
> **Naturaleza:** documento vivo pero **estable**. Cambia solo mediante un ADR aprobado
> (ver §9 y `docs/ADR/`), nunca de forma implícita dentro de una etapa de trabajo.

---

## 0. Estatus y orden de precedencia

Este documento gobierna **todas** las decisiones técnicas futuras del proyecto. Cuando
cualquier otro documento, comentario, issue o instrucción de una conversación entre en
conflicto con lo escrito aquí, **manda este documento** y la implementación se detiene hasta
resolver el conflicto (ver §10 y §11).

Orden de precedencia oficial para **decisiones de ingeniería** (qué está permitido hacer):

```
1. ENGINEERING_PRINCIPLES.md   ← este documento (autoridad máxima)
2. docs/ADR/*                  ← decisiones arquitectónicas registradas
3. TARGET_ARCHITECTURE.md      ← arquitectura objetivo
4. ROADMAP.md                  ← etapas de ejecución
5. BACKLOG.md                  ← inventario priorizado
6. docs/audit/*                ← estado actual (descriptivo)
7. CLAUDE.md                   ← contexto rápido de sesión
```

**Matiz obligatorio — el código real y esta jerarquía viven en ejes distintos:**

- **El código real** es la única fuente de verdad sobre *qué existe hoy* (hechos: qué función
  hay, qué valor tiene un token, dónde se encola un script). Ningún documento puede "ganarle"
  a un hecho verificable del código: si el código y un documento discrepan sobre un hecho,
  **el código describe la realidad** y el documento está desactualizado (hay que corregirlo).
- **Esta jerarquía** decide *qué está permitido hacer* (prescripción). Aquí manda
  `ENGINEERING_PRINCIPLES.md`, aunque el código actual ya viole una regla: en ese caso el
  código es **deuda a corregir**, no un permiso para seguir violándola.

Regla práctica: *para saber cómo funciona algo hoy* → lee el código. *Para saber si un cambio
está permitido* → aplica esta jerarquía empezando por este documento.

---

## 1. Filosofía del proyecto

### 1.1 Misión técnica

OKIP es un tema **clásico de WordPress** con un **motor propio de bloques modulares**. Su
misión de ingeniería es sostener una experiencia visual cinematográfica y una plataforma de
composición por bloques **que crezca de forma barata y segura durante años**, sin reescrituras
y sin toolchain de build.

El objetivo no es "tener el código más elegante posible", sino que **cada cambio futuro sea
pequeño, aislado, reversible y verificable**, y que la landing **nunca se rompa ni cambie su
comportamiento visual** salvo la corrección explícita de un bug.

### 1.2 Los cinco valores rectores (en orden de prioridad)

Cuando dos valores entren en tensión, gana el de **número más bajo**.

1. **Integridad visual y funcional.** La landing nunca queda rota; el comportamiento visual no
   cambia salvo bug documentado. Este valor está por encima de todos los demás, incluida la
   elegancia del código.
2. **Reversibilidad.** Todo cambio debe poder deshacerse con un `git revert` limpio. Se
   prefiere un cambio pequeño y reversible a uno grande y "mejor".
3. **Mantenibilidad.** El proyecto debe ser comprensible y modificable por una conversación
   futura que solo lea la documentación. Se evita la deuda técnica de forma activa.
4. **Simplicidad.** Sin build step, sin CDNs, sin abstracción prematura. La solución más simple
   que respeta los valores anteriores es la correcta.
5. **Rendimiento.** Fluidez a cualquier velocidad de scroll, GPU-friendly, sin reflows
   evitables — pero **medido**, nunca optimizado por intuición (§7).

### 1.3 Cómo evoluciona OKIP

- **Evolución gradual, nunca revolución.** Se consolidan patrones ya presentes y se elimina
  duplicación; no se reescribe lo que funciona.
- **Una etapa por conversación** (`ROADMAP.md`). Ninguna etapa depende de una futura.
- **El norte es reducir el coste de crecer**, no perseguir pureza arquitectónica.

---

## 2. Principios no negociables

Reglas absolutas. Violarlas requiere un ADR aprobado que las modifique (§9); ninguna etapa,
bug fix ni "mejora rápida" puede saltárselas.

1. **No romper el comportamiento visual.** Ningún cambio altera lo que el usuario ve o siente
   al navegar, salvo que corrija un bug **real y documentado**. Ante la duda, se conserva el
   comportamiento actual.
2. **La landing nunca queda rota.** Cada etapa termina con la home y las páginas afectadas
   funcionando (HTTP 200, sin errores PHP/JS). No se hace commit de un estado roto "para
   seguir mañana".
3. **Todo cambio es incremental y aislado.** Se toca **un** bloque, **un** archivo o **una**
   preocupación por commit. Prohibido modificar varios bloques a la vez.
4. **Todo cambio es reversible.** Existe una estrategia de reversión clara (habitualmente
   `git revert` del commit). No se acoplan cambios independientes en un mismo commit.
5. **Todo cambio se valida.** Lint PHP/JS en verde, smoke HTTP, y **verificación visual manual**
   cuando toca zonas sensibles (`TARGET_ARCHITECTURE §8.3`). Lo no verificado no está terminado.
6. **Nunca se introduce deuda técnica para resolver un bug.** Un bug se corrige en su causa
   respetando las reglas; no se "parchea" con un hack que viole otra norma. Si la corrección
   correcta excede el alcance, se documenta y se difiere, no se hackea.
7. **El orden de los bloques es 100% dinámico.** Ningún CSS/JS/PHP asume qué bloque va antes o
   después. El apilado se resuelve por `$order+1` (z-index por orden) y el acoplamiento con el
   vecino por DOM (`nextElementSibling` con `data-block-instance`), nunca por tipo ni posición
   fija.
8. **El contenido editable nunca vive en archivos del tema.** `config/` es esquema + defaults;
   el contenido editable va a `wp_options` como *diff* vía el filtro `okip_page_blocks`.
9. **Sin toolchain de build y sin CDNs en runtime.** Nada de bundlers, módulos ES, transpilado,
   ni dependencias servidas desde CDN. Los assets (incl. GSAP) son **locales**. (La excepción
   histórica de Google Fonts está registrada como deuda; ver §10.)
10. **GSAP es opcional, nunca una dependencia dura.** Todo camino GSAP tiene un fallback vanilla
    equivalente en comportamiento. Si GSAP no carga, el sitio **no se rompe**.
11. **Sin JS, el contenido queda visible.** El estado inicial oculto lo **arma el JS**; si el JS
    falla, nada queda invisible para siempre. Se respeta `prefers-reduced-motion` siempre.
12. **Nunca se optimiza sin medir ni se abstrae antes de tiempo.** Ninguna optimización de
    rendimiento sin una medición que la justifique; ninguna abstracción hasta que el mismo
    patrón se repita (regla de tres, §8).
13. **Toda salida se escapa y todo tipo se valida.** `esc_html/esc_url/esc_attr/wp_kses_post`
    en PHP; solo se renderizan tipos de la whitelist (`okip_allowed_blocks()`).
14. **Las convenciones oficiales son ley.** Nombres, BEM, data-attrs, handles y tokens siguen
    `TARGET_ARCHITECTURE §7`. No se introducen convenciones nuevas ad-hoc.

---

## 3. Filosofía para CSS

Principio raíz: **una sola fuente de verdad para el diseño (tokens), presentación local por
bloque, cero valores mágicos.**

### 3.1 Cuándo crear un TOKEN

- **Siempre** que un valor de diseño (color, espacio, radio, z-index base, duración, easing,
  breakpoint conceptual, tipografía base) se use, o **pueda** usarse, en más de un sitio.
- Todo color/espacio/radio/timing vive en `assets/css/tokens.css`. **Ningún literal de diseño
  fuera de tokens.** Si escribes `#ff5a14` o `1000` en cualquier otro archivo, es un bug.
- Los tokens son **semánticos**, no por nombre de valor: `--okip-color-accent`, no
  `--okip-orange`; `--okip-z-navbar`, no un `1000` suelto.

### 3.2 Cuándo crear un COMPONENTE global (`components.css`)

- Solo cuando una pieza de UI se **reutiliza entre bloques** (navbar, botones, chips).
- **Regla de frontera:** si dos o más bloques lo necesitan → sube a `components.css`. Si solo
  uno lo necesita → vive en el `style.css` de ese bloque.

### 3.3 Cuándo crear ESTILO LOCAL (`template-parts/blocks/{type}/style.css`)

- Para todo lo específico de un bloque. Es el **default**: ante la duda, el estilo es local.
- Nombrado BEM estricto: `okip-{type}__elemento--modificador`.
- Consume tokens y variables generadas (`--okip-{type}-*`); **nunca** redefine la paleta ni
  duplica valores de tokens.

### 3.4 Cuándo crear una UTILIDAD

- Rara vez. Utilidades **mínimas y con prefijo `okip-`**: estados (`is-hidden`, `is-visible`,
  `is-scrolled`), visibilidad responsiva (`okip-desktop-only`), aspect-ratios.
- **Prohibido** adoptar un framework de utilidades tipo Tailwind: choca con "sin toolchain".

### 3.5 Especificidad y cascada

- **Evita la especificidad alta.** BEM de una clase; no anides selectores para "ganar peso".
- `!important` **solo** en utilidades de estado documentadas. En cualquier otro sitio es señal
  de un problema de arquitectura, no la solución.
- Respeta el **orden de cascada** (`tokens → base → layout → components → transitions →
  animations → block`). Un bloque nunca depende de cargarse antes que un global.
- Sin CSS inline salvo variables seguras y clampadas (`--okip-card-x`, `object-position`,
  opacidades ya validadas). Serializa desde PHP con helpers (`okip_css_vars`), no concatenando.

### 3.6 Reutilización

- Antes de escribir CSS nuevo, busca un token o componente que ya lo resuelva.
- Duplicar un valor de diseño = crear (o usar) un token. Duplicar un patrón de UI entre bloques
  = promover a componente. **La regla de tres (§8) aplica también al CSS.**

---

## 4. Filosofía para JavaScript

Principio raíz: **capas de dependencia explícita, scope estricto por bloque, núcleo compartido
solo cuando ≥2 consumidores lo necesitan.** Sin módulos ES, sin bundler.

### 4.1 ¿Un helper pertenece al NÚCLEO o al BLOQUE?

- **Núcleo** (`assets/js/app.js`, `animations.js`, etc.) → lógica **transversal y reutilizable**:
  utilidades (`OKIP.clamp/readInt/rafThrottle/ready`), el motor de motion (`OKIPAnimations`),
  el estado de GSAP (`okipGsap`), la visibilidad del navbar.
- **Bloque** (`template-parts/blocks/{type}/script.js`) → **interactividad local e irrepetible**
  de ese bloque (hover de tarjetas del Hero, coreografía de segmentos del carrusel).
- **Regla de frontera (idéntica a CSS):** si dos bloques lo necesitan, sube al núcleo; si solo
  uno lo necesita, vive en el bloque. **No promuevas al núcleo "por si acaso".**

### 4.2 Cuándo crear una UTILIDAD nueva en el núcleo

- Solo cuando el mismo helper lo consumen **3+ piezas** (regla de tres) o cuando encapsula una
  primitiva peligrosa que debe existir una sola vez (throttle de scroll, parseo seguro).
- La API pública (`window.OKIP.*`, `window.OKIPAnimations.*`, `window.okipGsap.*`) es un
  **contrato**: no se rompe una firma sin un ADR, porque de ella dependen todos los bloques.

### 4.3 Cuándo dividir un archivo

- **No por gusto.** Un archivo se promueve a subcarpeta/archivo propio **solo** cuando supera
  ~**450–500 líneas** o su lógica la comparten **3+ consumidores** (ver `TARGET_ARCHITECTURE
  §5.2`). Por debajo de ese umbral, la separación es **conceptual** (secciones dentro del
  archivo), no física. Crear muchos archivos diminutos es deuda, no orden.

### 4.4 Reglas obligatorias de todo JS de bloque

- Arranca con `OKIP.ready(fn)` + **guard de idempotencia** (`__okip{Type}Init`) para no
  duplicar listeners.
- **Scope estricto:** selecciona **dentro** del root del bloque
  (`root.querySelectorAll(...)`), nunca `document.querySelectorAll` global para elementos que
  otro bloque pudiera tener (evita capturar el `<video>` del vecino).
- Scroll/resize: `OKIP.rafThrottle`, **una** lectura de layout por frame, geometría cacheada y
  recomputada en `resize`, no por frame.
- PHP→JS por `data-*` (kebab) y `<script type="application/json">` para config compleja;
  **nunca** variables globales sueltas (usa `OKIP_ENV` vía `wp_localize_script`).
- Comunicación entre piezas por **CustomEvents namespaced** `okip:*`, no por acoplamiento
  directo ni variables compartidas entre bloques.
- Parseo defensivo: `try/catch` alrededor de `JSON.parse`; ante error, **degradar a visible**.

### 4.5 Reutilización

- Antes de escribir una función, comprueba si `OKIP.*` o `OKIPAnimations.*` ya la ofrece.
- No copies una función entre dos bloques: si ambos la necesitan, es señal de que pertenece al
  núcleo (§4.1) — pero espera a la regla de tres antes de generalizarla (§8).

---

## 5. Filosofía para GSAP y el movimiento

Esta es la sección más delicada del proyecto. **Un bloque nunca elige tecnología de animación
por gusto: la elige por la categoría de efecto que necesita.** El árbol de abajo es la ley;
`TARGET_ARCHITECTURE §4` lo detalla.

### 5.1 Árbol de decisión oficial (memorizar)

```
¿Qué efecto necesito?
│
├─ (A) Revelar contenido al entrar en viewport (una vez o repetible)
│      → IntersectionObserver como "línea de disparo" (rootMargin -15%/-85%)
│        que AÑADE clases de estado. La animación la ejecuta el motor de motion
│        (entry) vía GSAP o CSS. NUNCA un scroll listener para revelar.
│
├─ (B) Movimiento continuo mientras el bloque es visible (drift/float/pulse)
│      → motion.playback (motor animations.js): timeline GSAP con yoyo,
│        o fallback rAF/CSS. Se pausa con reduce-motion y pestaña oculta.
│
├─ (C) Que un bloque "cubra" al anterior al hacer scroll (traspaso 1:1)
│      → transition.mode = "sticky-cover" (CSS puro). El OUTER es position:sticky
│        (su padre es <main>); el contenido va en .okip-cover-stage. SIN ScrollTrigger.
│
├─ (D) Coreografía atada al progreso del scroll (scrub) o pin real
│      (carrusel horizontal, secuencia narrativa, timeline por scroll)
│      → transition.mode = "scrolltrigger-pin" / "horizontal-pin".
│        UN solo ScrollTrigger por nodo. `end` por medidas reales del DOM
│        (invalidateOnRefresh:true). Fallback estático en ≤1024px.
│
└─ (E) Nada de lo anterior
       → transition.mode = "none".
```

### 5.2 Cuándo usar cada tecnología

| Tecnología | Úsala para… | **Nunca** la uses para… |
|---|---|---|
| **GSAP (core)** | Ejecutar `motion` (entry/playback/exit) siempre que `okipGsap.ready`; da suavidad GPU y timelines. | Como dependencia dura: siempre hay fallback vanilla equivalente. |
| **ScrollTrigger** | Efectos **atados al progreso del scroll**: pin real, scrub, horizontal (caso D). | Reveals simples (caso A) o coberturas 1:1 (caso C). Dos ST sobre el mismo nodo. `end` en `vh` arbitrarios. |
| **Pin (ScrollTrigger)** | Fijar un nodo mientras dura una coreografía por scroll (carrusel, narrativa). | Un simple "quedarse pegado y ser cubierto": eso es Sticky CSS. |
| **Sticky CSS (`sticky-cover`)** | Traspaso de cobertura simple entre un bloque y el siguiente (caso C). Es suave a cualquier velocidad. | Coreografías scrub/pin. Poner el sticky en el stage en vez del OUTER. |
| **IntersectionObserver** | Disparar reveals una vez (caso A), lanzar `exit` al salir del viewport, actualizar el activo en modos estáticos (carrusel móvil). | Animar frame a frame (eso es scrub → ScrollTrigger). |
| **Scroll listeners** | Solo navbar (visibilidad) y pausa por cobertura, **siempre** con `rafThrottle` y una lectura de layout por frame. | Cualquier cosa que un IO pueda hacer mejor. Reveals. |
| **CSS Animation / keyframes** | Estados y micro-transiciones reutilizables (`animations.css`), fallbacks sin GSAP. | Coreografías complejas coordinadas: eso es el motor de motion. |

### 5.3 Reglas duras de GSAP/ScrollTrigger

- Los plugins se registran **solo** en `gsap-init.js`; los bloques leen `okipGsap.ready` /
  `hasScrollTrigger`.
- **Un ScrollTrigger por nodo.** Prohibido dos ST peleando por el mismo elemento.
- `end` y distancias **siempre** desde medidas reales del DOM con `invalidateOnRefresh:true`;
  **nunca** `vh` arbitrarios.
- Toda animación GSAP tiene rama de **fallback** y rama **`reduce-motion`**.
- `will-change` / contexto 3D solo en el elemento que **se anima**, nunca en el que centra
  (no animar `.okip-hero__card`, que lleva `translate(-50%,-50%)`; animar `.okip-hero__card-media`).

### 5.4 ¿La animación es del bloque o del núcleo?

- **Núcleo:** el motor `motion` (entry/playback/exit) y sus presets genéricos; los modos de
  `transition`; keyframes reutilizables; los fallbacks y el respeto a `reduce-motion`.
- **Bloque:** *qué* preset/modo usa y con qué parámetros; su coreografía única e irrepetible;
  sus interacciones locales.
- **Regla:** si un tercer bloque podría querer el mismo efecto, va al núcleo como preset/modo.
  Si es irrepetiblemente específico, vive en el `script.js` del bloque **respetando §5.1–5.3**.

### 5.5 Invariantes de animación (toda etapa las respeta)

1. Sin JS o sin GSAP → el contenido queda **visible** y funcional.
2. `prefers-reduced-motion` → sin movimiento continuo; reveals instantáneos; interactividad
   intacta.
3. El estado inicial oculto lo **arma el JS** (patrón `is-anim-armed`), no `.okip-js` a secas.
4. Reordenar bloques no rompe el apilado ni las transiciones (z por orden, vecino por DOM).

---

## 6. Filosofía para nuevos bloques (contrato)

Todo bloque nuevo cumple este contrato. Es la extensión operativa de `TARGET_ARCHITECTURE §3`.

### 6.1 Responsabilidad de un bloque

Un bloque es responsable de **su presentación y su interactividad local. Nada más.**

- **PUEDE:** renderizar su HTML desde `data` normalizada; declarar su `motion`/`transition` en
  config; emitir `data-*` para su `script.js`; tener CSS/JS propios cargados solo si su tipo se
  usa; resolver su media con fallback neutro (`okip_media_exists`).
- **NO PUEDE:** asumir a su vecino (por tipo o posición) — lo deriva por DOM; fijar su `z-index`
  con un número mágico — lo recibe por `$order+1`; reinventar pin/scrub cuando el modo de
  transición del núcleo ya lo cubre; leer/escribir el scope de otro bloque; escribir contenido
  editable en archivos del tema.

### 6.2 Estructura mínima obligatoria

```
config/blocks/{type}.php            [OBLIGATORIO]  defaults + okip_normalize_{type}_data()
template-parts/blocks/{type}/
    block.php                       [OBLIGATORIO]  render escapado, scope por instancia
    style.css                       [OPCIONAL]     BEM okip-{type}__*
    script.js                       [OPCIONAL]     guard __okip{Type}Init
    README.md                       [OBLIGATORIO]  contrato del bloque (§6.4)
inc/admin/editors/{type}.php        [OBLIGATORIO cuando sea editable]
inc/admin/sanitizers → okip_admin_sanitize_{type}_data()  [OBLIGATORIO si editable]
```

Además: registrarlo en `okip_allowed_blocks()` (whitelist) y añadir su instancia a un
`config/pages/{slug}.php`. **No se toca el motor, el enqueue ni el admin genérico.**

### 6.3 Convenciones que todo bloque respeta

- Raíz con `id="{instance_id}"` + `data-block-instance="{instance_id}"` + `data-okip-{type}`.
- `layout.z_index` default **`0`** (= auto por orden). Un valor `>0` es override avanzado y rompe
  la escalera de cobertura si se usa mal.
- Data-attrs de config en kebab (`data-{grupo}-{clave}`); config compleja como JSON en
  `<script type="application/json">`.
- En `config/blocks/{type}.php`: funciones **antes** del `return` y con `function_exists()`.
- Media-driven: sin media real → **fallback neutro** (color sólido), nunca un diseño decorativo
  falso.
- El saneo del admin (`okip_admin_sanitize_{type}_data`) aplica **exactamente** las mismas
  whitelists/clamps/booleans que el normalizador (`okip_normalize_{type}_data`).

### 6.4 Documentación obligatoria (`README.md` por bloque)

Cada bloque documenta su contrato para que una conversación futura lo modifique **sin releer
todo el código**:

1. Propósito y referencia visual (`referencias/*.png`).
2. Grupos de config con whitelists, rangos y defaults.
3. Contrato de animación: qué `transition.mode` usa y por qué; qué targets de motion expone;
   qué pasa sin GSAP y con `reduce-motion`.
4. Data-attrs y CustomEvents que emite/escucha.
5. Dependencias del vecino (si las hay) y cómo se resuelven por DOM.
6. Checklist de verificación visual específico.

### 6.5 Definición de "listo" de un bloque

El bloque cumple `TARGET_ARCHITECTURE §9`: lint en verde, HTTP 200, comportamiento visual sin
regresión, verificación visual hecha, sin z-index mágicos ni scroll listeners sin throttle ni
selectores globales, README presente y coherente, y reversión clara.

---

## 7. Filosofía de rendimiento

Principio raíz: **fluidez sí, pero medida. Nunca optimices por intuición.**

### 7.1 Cuándo optimizar

- **Solo** cuando una medición demuestra un problema real (jank visible, reflow forzado en un
  scroll, caída de FPS). "Creo que esto sería más rápido" **no** es motivo para cambiar nada.
- El orden es: **medir → identificar la causa → cambio mínimo → medir de nuevo**. Sin la última
  medición, la optimización no está terminada.

### 7.2 Cuándo usar (y evitar) `will-change` y contexto 3D

- **Úsalo** solo en el elemento que realmente se anima y solo mientras se anima (o de forma
  estable en un elemento que anima de continuo, como `.okip-hero__card-media`).
- **Evítalo** como "mejora preventiva" repartida por el CSS: `will-change` permanente en muchos
  nodos consume memoria de GPU y puede **empeorar** el rendimiento. Nunca en el nodo que centra
  con `translate`.

### 7.3 Reglas de rendimiento siempre vigentes (no requieren medición)

Estas ya son ley por diseño; no son "optimizaciones", son higiene:

- Scroll/resize con `rafThrottle`; **una** lectura de layout por frame; geometría cacheada y
  recomputada en `resize`, no por frame (evita reflows forzados).
- Animar por transform/opacity (GPU), no por propiedades que disparan layout.
- Carga de assets **condicional** por tipo de bloque usado; versionado por `filemtime`.
- Preferir IO sobre scroll listeners; un solo ScrollTrigger por nodo.
- No cargar media que no existe (media-driven); sin autoplay innecesario.

### 7.4 Cuándo eliminar código

- Se elimina **CSS/JS muerto** confirmado por inspección (regla sin uso, listener sin efecto).
- La eliminación es un cambio como cualquier otro: aislada, un archivo por commit, verificada
  visualmente, reversible. Ante la duda de si algo se usa, **no** se borra sin confirmarlo.

---

## 8. Filosofía de mantenibilidad

Principio raíz: **el proyecto debe poder evolucionar durante años siendo modificado por
conversaciones que solo leen la documentación.**

### 8.1 La regla de tres (contra la abstracción prematura)

- La **primera** vez que aparece un patrón: escríbelo local.
- La **segunda** vez: cópialo, pero anota la duplicación como candidata.
- La **tercera** vez: **entonces** abstrae (token, componente, helper de núcleo, preset).

Abstraer con uno o dos usos crea acoplamiento y coste sin beneficio comprobado. Aplica a CSS
(§3.6), JS (§4.5) y animaciones (§5.4).

### 8.2 Frontera núcleo/bloque, siempre

Repetida a propósito porque es el corazón de la escalabilidad: **si ≥2 bloques lo necesitan,
sube al núcleo; si solo uno, vive en el bloque.** No al revés, no "por si acaso".

### 8.3 Documentación como parte del cambio

- Un cambio que altera una regla actualiza la doc correspondiente en el **mismo** commit
  (README del bloque, y `CLAUDE.md`/`TARGET_ARCHITECTURE` si cambió una convención).
- Una decisión arquitectónica se registra como **ADR** (§9) antes o junto a su implementación.
- La documentación que miente es peor que la ausente: si detectas un desfase doc↔código,
  corregir la doc es trabajo legítimo y prioritario (§0, matiz del código real).

### 8.4 Estabilidad de contratos

- Las APIs públicas (`OKIP.*`, `OKIPAnimations.*`, filtros `okip_page_blocks`, schema del JSON
  de motion, nombres de option `okip_page_blocks_*`) son **contratos**. Cambiarlas requiere un
  ADR y una migración (p. ej. remapeo de `instance_id`), nunca un cambio silencioso.
- Los `instance_id` son manuales, legibles y **estables**: sirven de ancla, scope y clave de
  guardado. Renombrar uno exige remapeo (`okip_page_block_order_remap`).

### 8.5 Higiene continua

- Un archivo por commit; commits pequeños con mensaje claro.
- Sin números mágicos nuevos, sin selectores globales, sin listeners sin throttle.
- Convenciones oficiales por encima del gusto personal: el código nuevo **se lee como el que lo
  rodea** (misma densidad de comentarios, nombres e idioma: código en inglés, contenido en
  español).

---

## 9. Sistema de decisiones arquitectónicas (ADR)

Toda decisión arquitectónica significativa se registra como un **Architecture Decision Record**
en `docs/ADR/`. El diseño completo del sistema (formato, numeración, ciclo de vida, aprobación y
relación con el roadmap) vive en **`docs/ADR/README.md`**; la plantilla en
**`docs/ADR/TEMPLATE.md`**.

Resumen operativo:

- **Se crea un ADR** cuando una decisión: (a) fija o cambia una regla de este documento o de
  `TARGET_ARCHITECTURE`; (b) elige entre alternativas técnicas con consecuencias duraderas
  (una librería, un patrón, una frontera); o (c) introduce/acepta una excepción a un principio.
- **Un ADR no se edita retroactivamente:** los aprobados son inmutables; para revertir una
  decisión se crea un ADR nuevo que **supersede** al anterior (se enlazan entre sí).
- **Precedencia:** un ADR aprobado está por encima de `TARGET_ARCHITECTURE`, el `ROADMAP` y el
  `BACKLOG`, pero **por debajo** de este documento (§0). Un ADR que contradiga un principio no
  negociable (§2) es inválido salvo que **modifique explícitamente** este documento.

---

## 10. Conflictos detectados

Revisión de `ROADMAP.md`, `TARGET_ARCHITECTURE.md` y `BACKLOG.md` (y documentos enlazados)
frente a esta Constitución. **No se corrige nada aquí**; se registra para decisión del usuario.

### CD-1 — Cadena de precedencia divergente (código real vs. jerarquía nueva)

- **Documentos:** `docs/README.md` (§"Precedencia ante conflictos", línea ~28) y
  `TARGET_ARCHITECTURE.md §0` (líneas ~35–36).
- **Sección en conflicto:** ambos declaran `Código real > TARGET_ARCHITECTURE.md > docs/audit/*
  > CLAUDE.md`. La nueva jerarquía (§0 de este documento) antepone
  `ENGINEERING_PRINCIPLES.md` y `docs/ADR/*`, e intercala `ROADMAP` y `BACKLOG`.
- **Motivo del conflicto:** (1) las cadenas antiguas **no mencionan** este documento ni los ADR,
  así que quedan incompletas y podrían inducir a ignorar la Constitución; (2) las antiguas ponen
  "Código real" en la cima **sin distinguir eje descriptivo vs. prescriptivo**, mientras que la
  nueva §0 separa ambos ejes. No es una contradicción irreconciliable, es una **imprecisión**.
- **Recomendación:** actualizar `docs/README.md` y `TARGET_ARCHITECTURE §0` para reflejar la
  jerarquía de §0 y su matiz "código real = fuente de hechos; principios = fuente de permisos".
  Hacerlo en una etapa de documentación dedicada (candidata a nueva etapa de Fase 1 o extensión
  de la 1.5), **con un ADR** que fije la jerarquía. Hasta entonces, prevalece §0 de este
  documento.

### CD-2 — Google Fonts servido desde CDN externo contradice "sin CDNs"

- **Documentos:** `BACKLOG.md` (B3) y `docs/audit/*` (FLOW/DEPENDENCIES/ARCHITECTURE) que
  describen la URL `fonts.googleapis.com`.
- **Sección en conflicto:** el principio no negociable §2.9 ("sin CDNs en runtime") vs. la
  implementación real de tipografía dinámica vía CDN de Google.
- **Motivo del conflicto:** hay una dependencia de runtime a un CDN externo, justo lo que la
  filosofía prohíbe (coherencia, privacidad, offline). `BACKLOG B3` ya lo reconoce como deuda,
  pero ningún documento lo declara **excepción aprobada**.
- **Recomendación:** decidir vía **ADR** entre (a) auto-hospedar los pesos usados (elimina la
  deuda; alinea con §2.9) o (b) aceptar formalmente la excepción para fuentes y registrarla como
  tal. No corregir de forma implícita: es una decisión arquitectónica con coste (pipeline de
  auto-host). Etapa candidata: Fase 3+, como ya sugiere B3.

### CD-3 — La auditoría describe el admin como "stubs" (desfase doc↔código)

- **Documentos:** `docs/audit/ADMIN_SYSTEM.md §1` ("Stubs - Sin Funcionalidad Real") y
  `CLAUDE.md` (que llama "stubs" a `inc/admin/*`).
- **Sección en conflicto:** contradicen el hecho, ya corregido en `TARGET_ARCHITECTURE §0.2` y
  `BACKLOG M2/B1`, de que existe **implementación real** de admin (panel de orden, editores,
  save-handlers, sanitizers, `admin-blocks.js`).
- **Motivo del conflicto:** es un desfase **descriptivo** (hechos), no una violación de un
  principio. Choca con §8.3 ("la documentación que miente es peor que la ausente"): una
  conversación futura que confíe en la auditoría o en `CLAUDE.md` planificará mal la Fase 4.
- **Recomendación:** **no** editar `docs/audit/*` (son registro histórico; así lo fija `BACKLOG
  B1`), pero sí ejecutar `ROADMAP 1.5` para sincronizar `CLAUDE.md`, y considerar una nota al pie
  en `ADMIN_SYSTEM.md` que remita a `TARGET_ARCHITECTURE §0`. Prioridad media; sin ADR (es un
  hecho, no una decisión).

### CD-4 — (Menor) Reorganización de `assets/js/` y auto-host: tensión con "simplicidad", ya gestionada

- **Documentos:** `ROADMAP 3.2` + `BACKLOG B2` (reorganizar `assets/js/` por rol) y `BACKLOG
  B3/B4`.
- **Motivo:** rozan §2.9 (sin toolchain) y §12 (no abstraer antes de tiempo). **No es conflicto
  activo:** ambos están **gateados** por precondiciones explícitas (>450 líneas / lógica en 3+
  consumidores / decisión del usuario) que coinciden con la regla de tres (§8.1).
- **Recomendación:** ninguna acción; se registra para dejar constancia de que la tensión ya está
  correctamente contenida por el propio roadmap/backlog. Mantener las precondiciones al pie de la
  letra.

**Conclusión de la validación:** no hay contradicciones que bloqueen la adopción de esta
Constitución. CD-1 y CD-3 son desfases de documentación a alinear (uno con ADR, otro por hecho);
CD-2 es la única decisión arquitectónica pendiente real (excepción de fuentes). Ninguna se
corrige en esta etapa.

---

## 11. Instrucción permanente para futuras conversaciones

A partir de ahora, en toda conversación sobre este proyecto:

1. **Trata este documento como la autoridad máxima** y aplica la precedencia de §0.
2. **Antes de implementar**, verifica que la tarea no contradice ningún principio no negociable
   (§2) ni el árbol de decisión de animaciones (§5). Si lo hace, **detén la implementación** y
   explica el conflicto antes de escribir una línea de código.
3. **Si la tarea exige cambiar una regla de esta Constitución o de `TARGET_ARCHITECTURE`**,
   propón un **ADR** (`docs/ADR/`) y espera aprobación; no cambies la regla de forma implícita.
4. **Ejecuta una sola etapa del roadmap por conversación**, cumpliendo la "Definición de listo"
   (`TARGET_ARCHITECTURE §9`) y los principios §2.
5. **Ante cualquier duda entre "hacer lo pedido" y "respetar los principios", ganan los
   principios**, y se consulta al usuario.

---

## Apéndice — Un desarrollador nuevo puede responder, solo con la documentación:

| Pregunta | Dónde se responde |
|---|---|
| ¿Cómo debe evolucionar este proyecto? | §1, §8 |
| ¿Qué decisiones están permitidas / prohibidas? | §2 (no negociables), §0 (precedencia) |
| ¿Cómo debe diseñarse un nuevo bloque? | §6 + `TARGET_ARCHITECTURE §3` |
| ¿Cuándo usar GSAP / ScrollTrigger / Pin / Sticky? | §5.1 (árbol) + §5.2 (tabla) |
| ¿Cuándo usar IntersectionObserver / scroll listener / CSS animation? | §5.2 |
| ¿Cuándo crear una abstracción / reutilizar código? | §8.1 (regla de tres), §3.6, §4.5, §5.4 |
| ¿Cuándo crear token / componente / utilidad / estilo local? | §3 |
| ¿Un helper es del núcleo o del bloque? | §4.1, §8.2 |
| ¿Cuándo usar `will-change` y cuándo optimizar? | §7 |
| ¿Cómo registrar una decisión arquitectónica? | §9 + `docs/ADR/README.md` |
| ¿Cómo ejecutar una etapa sin romper el proyecto? | §2, §11 + `TARGET_ARCHITECTURE §9` + `ROADMAP` |
</content>
</invoke>
