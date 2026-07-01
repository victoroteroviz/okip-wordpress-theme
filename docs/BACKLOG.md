# OKIP Theme — Backlog Técnico

> Inventario de mejoras detectadas en la auditoría y en el contraste con el código.
> Prioridad: **Crítica · Alta · Media · Baja**. Cada ítem enlaza con su etapa en
> `docs/ROADMAP.md`. Este backlog no reemplaza al roadmap: el roadmap ordena, el backlog
> justifica y prioriza.

Campos por ítem: **Descripción · Motivo · Impacto esperado · Complejidad · Dependencias ·
Etapa recomendada.**

Escala de complejidad: **XS** (minutos) · **S** (una edición aislada) · **M** (varios
archivos, una sesión) · **L** (varias sesiones / alto riesgo).

---

## 🔴 CRÍTICA

### C1 — Documentación de arquitectura solo vivía en scratchpad efímero
- **Descripción:** las 8 auditorías y todo el conocimiento arquitectónico existían únicamente
  en un directorio temporal de sesión, no en el repo.
- **Motivo:** sin persistencia, cualquier conversación futura tendría que re-auditar desde cero,
  contradiciendo el objetivo de ejecutar etapas "sin reinterpretar la arquitectura".
- **Impacto esperado:** conocimiento versionado y estable; onboarding de sesiones futuras en
  minutos.
- **Complejidad:** XS.
- **Dependencias:** ninguna.
- **Etapa:** 0.1 (✅ resuelto en esta sesión — `docs/audit/*` + plan).

### C2 — Contrato de bloque no garantizado ⇒ el reorden dinámico puede romperse
- **Descripción:** el desacople del orden depende de que **todo** bloque emita
  `data-block-instance` y tenga `layout.z_index` default `0` (auto por orden). No está
  auditado en los 6 bloques.
- **Motivo:** el navbar y la pausa del Hero derivan el "coverBlock" por `nextElementSibling`
  con `data-block-instance`; un `z_index>0` rompe la escalera de cobertura sticky. Es el
  pilar de la regla "el orden es dinámico".
- **Impacto esperado:** reordenar desde el admin nunca rompe apilado ni navbar.
- **Complejidad:** M.
- **Dependencias:** ninguna (base para Fase 4).
- **Etapa:** 1.3.

---

## 🟠 ALTA

### A1 — Posibles desviaciones del árbol de decisión de animaciones
- **Descripción:** no está verificado que cada efecto use la tecnología correcta
  (GSAP/ScrollTrigger/sticky/IO/scroll) según `TARGET_ARCHITECTURE §4`.
- **Motivo:** las trampas ya documentadas (dos ST por nodo, pin con `end` en `vh`, reveal por
  scroll listener) causan glitches en scroll rápido; unificar las reglas los previene.
- **Impacto esperado:** transiciones suaves y predecibles a cualquier velocidad; menos deuda.
- **Complejidad:** M (auditar) + S por cada corrección aislada.
- **Dependencias:** C2 (contrato) recomendable antes.
- **Etapa:** 2.1 (auditar) → 2.2 (corregir).

### A2 — Admin de overrides incompleto / no verificado end-to-end
- **Descripción:** existe implementación real de admin (orden, editores, save-handlers,
  sanitizers, `admin-blocks.js`), pero no está confirmado qué bloques editan y guardan
  overrides correctamente contra `wp_options`.
- **Motivo:** la decisión cerrada #7 del proyecto es que el contenido editable viva en
  `wp_options` vía `okip_page_blocks`, nunca en archivos del tema. Es funcionalidad central.
- **Impacto esperado:** edición de contenido sin tocar código; `config/` como esquema puro.
- **Complejidad:** L (varias sesiones, un bloque por etapa).
- **Dependencias:** C2, y saneo alineado con `okip_normalize_{type}_data`.
- **Etapa:** 4.1 (inventario) → 4.2 (por bloque) → 4.3 (orden).

### A3 — Saneo del admin debe reflejar exactamente las whitelists/clamps del normalizador
- **Descripción:** `okip_admin_sanitize_{type}_data()` debe aplicar las mismas whitelists,
  clamps y booleans que `okip_normalize_{type}_data()`.
- **Motivo:** un saneo más laxo que la normalización deja pasar datos que luego rompen el render
  o divergen entre admin y front.
- **Impacto esperado:** integridad de datos; imposible guardar un estado que rompa el bloque.
- **Complejidad:** M.
- **Dependencias:** A2.
- **Etapa:** 4.2 (junto a cada bloque).

---

## 🟡 MEDIA

### M1 — Números mágicos de z-index dispersos
- **Descripción:** valores `999`, `1000`, etc. fuera de tokens.
- **Motivo:** dificultan razonar sobre el apilado y son fuente de bugs al añadir capas.
- **Impacto esperado:** escala nombrada única; apilado auditable.
- **Complejidad:** S.
- **Dependencias:** ninguna.
- **Etapa:** 1.1.

### M2 — `CLAUDE.md` desactualizado respecto al código
- **Descripción:** dice que el admin son "stubs" y no menciona el sistema de motion ni de
  tipografía/Google Fonts, ya presentes. La ruta histórica y otros detalles quedaron atrás.
- **Motivo:** documento de contexto que se carga cada sesión; si miente, induce errores.
- **Impacto esperado:** contexto fiable de arranque para cada conversación.
- **Complejidad:** S.
- **Dependencias:** ninguna.
- **Etapa:** 1.5.

### M3 — Falta README por bloque
- **Descripción:** los bloques no documentan su contrato (config, animación, data-attrs, vecino).
- **Motivo:** obliga a releer todo el código para tocar un bloque; frena la escalabilidad.
- **Impacto esperado:** intervención segura por conversación futura sin releer el núcleo.
- **Complejidad:** M.
- **Dependencias:** ninguna.
- **Etapa:** 1.4 (existentes) + 3.3/5.2 (nuevos).

### M4 — Duplicación de animaciones entre bloques (sin presets compartidos)
- **Descripción:** efectos equivalentes reimplementados por bloque en vez de vivir como preset
  del núcleo.
- **Motivo:** viola "reutilizar animaciones sin duplicar código"; cambios deben tocar N sitios.
- **Impacto esperado:** menos código, coherencia, un solo punto de ajuste.
- **Complejidad:** M.
- **Dependencias:** A1 (auditoría de animación).
- **Etapa:** 2.3.

### M5 — Valores mágicos y reglas muertas en CSS
- **Descripción:** literales de color/espacio/radio fuera de tokens; posibles reglas sin uso.
- **Motivo:** deriva de diseño y peso innecesario; dificulta el theming.
- **Impacto esperado:** CSS 100% tokenizado y limpio.
- **Complejidad:** M.
- **Dependencias:** M1 (z), 1.2 (duraciones) recomendable antes.
- **Etapa:** 3.1.

### M6 — Cobertura de `prefers-reduced-motion` no auditada globalmente
- **Descripción:** no está verificado que **todo** movimiento continuo se pause y todo reveal
  degrade a instantáneo con `reduce-motion`.
- **Motivo:** accesibilidad y regla invariante de animación (`TARGET_ARCHITECTURE §4.4`).
- **Impacto esperado:** experiencia accesible garantizada; sin animación intrusiva.
- **Complejidad:** S–M.
- **Dependencias:** A1.
- **Etapa:** 2.2 (incluir en cada corrección) o ítem propio dentro de 2.1.

### M7 — Auditoría de accesibilidad (aria, teclado, foco)
- **Descripción:** revisar `aria-expanded/controls` de la hamburguesa, navegación por teclado,
  `:focus-visible`, skip link y orden de foco tras reveals.
- **Motivo:** decisión de proyecto sobre accesibilidad; riesgo de regresión al animar.
- **Impacto esperado:** navegable por teclado y lectores; cumple lo prometido en `CLAUDE.md §6`.
- **Complejidad:** M.
- **Dependencias:** ninguna.
- **Etapa:** transversal; recomendable como sub-check en 1.3 y 5.1.

### M8 — `news`: `WP_Query` por categoría con fallback dummy
- **Descripción:** el bloque de noticias debe consultar posts por categoría
  (`noticias`/`sala-de-prensa`) y degradar a contenido dummy si no hay posts.
- **Motivo:** decisión #10 del proyecto; ruta futura a CPT.
- **Impacto esperado:** noticias reales sin romper si no hay contenido.
- **Complejidad:** M.
- **Dependencias:** contrato de bloque (C2).
- **Etapa:** 5.1.

### M9 — Scaffold de bloque nuevo + guía
- **Descripción:** no hay plantilla canónica para crear bloques.
- **Motivo:** hacer la creación mecánica y consistente; evitar drift de convenciones.
- **Impacto esperado:** bloques nuevos correctos "por defecto".
- **Complejidad:** M.
- **Dependencias:** M3 (README), reglas de `TARGET_ARCHITECTURE`.
- **Etapa:** 5.2.

### M10 — Páginas internas vacías
- **Descripción:** `contacto`, `sala-de-prensa`, `fabrica-de-tecnologias` devuelven `[]`.
- **Motivo:** validar el desacople reutilizando bloques fuera de la home (sin Hero).
- **Impacto esperado:** multipágina real; prueba de portabilidad de bloques.
- **Complejidad:** M.
- **Dependencias:** scope por instancia verificado; C2.
- **Etapa:** 5.3.

### M11 — Baseline visual inexistente
- **Descripción:** no hay referencia registrada del comportamiento visual actual.
- **Motivo:** sin baseline no se puede afirmar "sin cambio visual" con rigor.
- **Impacto esperado:** criterio objetivo de no-regresión (aunque la captura sea manual).
- **Complejidad:** S (plantilla) + captura manual del usuario.
- **Dependencias:** ninguna.
- **Etapa:** 0.2.

---

## 🟢 BAJA

### B1 — Imprecisiones en los documentos de auditoría
- **Descripción:** `docs/audit/FLOW.md` sitúa el JS en `<head>` (real: footer). Ejemplos CSS con
  sintaxis anidada (real: CSS plano). `docs/audit/JS_ARCHITECTURE.md §5` usa
  `data-okip-nav-toggle` mientras el resto usa otros nombres — verificar el real.
- **Motivo:** confiar ciegamente en la auditoría induce errores puntuales.
- **Impacto esperado:** documentación consistente.
- **Complejidad:** XS.
- **Dependencias:** ninguna. **Nota:** las correcciones ya viven en `TARGET_ARCHITECTURE §0`;
  no editar los `docs/audit/*` (son registro histórico).
- **Etapa:** cubierto por `TARGET_ARCHITECTURE §0`; opcional nota al pie en cada audit.

### B2 — (Opcional) Reorganizar `assets/js/` por rol
- **Descripción:** agrupar núcleo JS en `core/animation/ui/admin/`.
- **Motivo:** legibilidad si el volumen crece; hoy no es necesario.
- **Impacto esperado:** navegación más clara; **cero** cambio funcional.
- **Complejidad:** M (riesgo en deps de enqueue).
- **Dependencias:** solo si un archivo supera ~450 líneas o hay lógica compartida por 3+.
- **Etapa:** 3.2 (saltar si no aplica).

### B3 — Google Fonts servido desde CDN externo
- **Descripción:** la tipografía dinámica se carga desde `fonts.googleapis.com`.
- **Motivo:** la filosofía del proyecto es "sin CDNs"; además privacidad/rendimiento/offline.
  Auto-hospedar los pesos usados sería más coherente.
- **Impacto esperado:** coherencia con la decisión de assets locales; menos dependencia externa.
- **Complejidad:** M–L (pipeline de auto-host de los pesos recolectados).
- **Dependencias:** `design-controls` (recolección de fuentes) ya existe.
- **Etapa:** candidato a etapa propia en Fase 3+ (evaluar coste/beneficio; puede quedar fuera
  de alcance si se acepta la excepción para fuentes).

### B4 — Sin regresión visual automatizada
- **Descripción:** la verificación visual es 100% manual.
- **Motivo:** el proyecto evita toolchain/build; una suite de screenshots requeriría infra.
- **Impacto esperado:** detección temprana de regresiones.
- **Complejidad:** L.
- **Dependencias:** decisión del usuario sobre introducir tooling (choca con "sin toolchain").
- **Etapa:** fuera de alcance por ahora; mitigado por 0.2 (baseline manual). Reevaluar si el
  número de bloques crece mucho.

---

## Vista resumen (prioridad → etapa)

| ID | Prioridad | Título | Etapa | Complej. |
|----|-----------|--------|-------|----------|
| C1 | Crítica | Persistir documentación | 0.1 ✅ | XS |
| C2 | Crítica | Garantizar contrato de bloque | 1.3 | M |
| A1 | Alta | Desviaciones de animación | 2.1→2.2 | M |
| A2 | Alta | Admin overrides end-to-end | 4.1→4.3 | L |
| A3 | Alta | Saneo = normalizador | 4.2 | M |
| M1 | Media | Escala z-index en tokens | 1.1 | S |
| M2 | Media | Actualizar `CLAUDE.md` | 1.5 | S |
| M3 | Media | READMEs por bloque | 1.4 | M |
| M4 | Media | Presets compartidos | 2.3 | M |
| M5 | Media | Limpieza CSS | 3.1 | M |
| M6 | Media | Auditar reduce-motion | 2.x | S–M |
| M7 | Media | Auditar accesibilidad | 1.3/5.1 | M |
| M8 | Media | `news` WP_Query + fallback | 5.1 | M |
| M9 | Media | Scaffold de bloque | 5.2 | M |
| M10 | Media | Páginas internas | 5.3 | M |
| M11 | Media | Baseline visual | 0.2 | S |
| B1 | Baja | Corregir docs auditoría | §0 | XS |
| B2 | Baja | Reorg `assets/js/` | 3.2 opc | M |
| B3 | Baja | Auto-host de fuentes | Fase 3+ | M–L |
| B4 | Baja | Regresión visual auto | fuera | L |
