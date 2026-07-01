# OKIP Theme — ROADMAP de Evolución

> Guía oficial de ejecución. Cada etapa es **independiente, pequeña y reversible**.
> Reglas que ninguna etapa puede violar: ver `docs/TARGET_ARCHITECTURE.md §0` y `§9`.
> Prioridad absoluta: **no cambiar el comportamiento visual** salvo bug real, y **nunca**
> dejar la landing rota.

## Cómo usar este roadmap

- Ejecuta **una** etapa por conversación. No mezcles etapas.
- Antes de empezar: lee `TARGET_ARCHITECTURE.md`, la ficha de la etapa aquí, y el/los
  archivos "involucrados".
- Al terminar: cumple la "Definición de listo" (`TARGET_ARCHITECTURE.md §9`) y marca la etapa.
- El orden sugerido respeta dependencias, pero las etapas de Fase 1 son mayormente
  independientes entre sí.

## Convención de cada ficha

**Objetivo · Alcance · Archivos · Riesgos · Criterios de aceptación · Pruebas manuales ·
Reversión.**

## Protocolo estándar de pruebas (referenciado como "PP")

```bash
# PP-1 Lint PHP (contenedor)
docker exec okip_landing_wordpress sh -c 'cd /var/www/html/wp-content/themes/okip-theme && \
  for f in $(find . -name "*.php" -not -path "./.git/*"); do php -l "$f"; done'
# PP-2 Sintaxis JS (host)
node --check assets/js/<archivo>.js
# PP-3 Smoke HTTP
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/
# PP-4 Datos con WP cargado
docker exec okip_landing_wordpress php -r 'define("WP_USE_THEMES",false);require"/var/www/html/wp-load.php";var_dump(okip_get_page_blocks("home"));'
# PP-5 Verificación visual: navegador + Ctrl+Shift+R, recorrer checklist de TARGET_ARCHITECTURE §8.3
```

---

# FASE 0 — Cimientos de plan y verificación

## Etapa 0.1 — Persistir documentación y plan  ✅ (hecha en esta sesión)
- **Objetivo:** que auditoría + plan vivan en el repo, no en scratchpad efímero.
- **Alcance:** copiar `docs/audit/*`; crear `TARGET_ARCHITECTURE.md`, `ROADMAP.md`, `BACKLOG.md`.
- **Archivos:** `docs/**` (solo `.md`, ningún archivo de código).
- **Riesgos:** ninguno (no toca runtime).
- **Aceptación:** los 3 documentos + 8 auditorías existen en `docs/`.
- **Pruebas:** revisión de índice `docs/README.md`.
- **Reversión:** `git rm -r docs/`.

## Etapa 0.2 — Baseline de comportamiento visual
- **Objetivo:** capturar el estado visual actual como referencia para comparar en cada etapa.
- **Alcance:** documentar (con capturas/video del navegador realizadas por el usuario) el
  comportamiento de Hero, `video-w-title`, `industry-carousel`, navbar y transiciones a
  velocidad normal y rápida. Guardar notas en `docs/BASELINE.md`.
- **Archivos:** `docs/BASELINE.md` (nuevo). Sin cambios de código.
- **Riesgos:** ninguno.
- **Aceptación:** existe un checklist visual con el "estado esperado" de cada bloque.
- **Pruebas:** PP-5 como referencia inicial.
- **Reversión:** borrar `docs/BASELINE.md`.
- **Nota:** la captura visual la ejecuta el usuario (no automatizable); esta etapa produce la
  plantilla y recoge sus observaciones.

---

# FASE 1 — Consolidación del núcleo (cero cambio visual)

## Etapa 1.1 — Escala nombrada de z-index en tokens
- **Objetivo:** eliminar números mágicos de z-index (`999`, `1000`, etc.) sin alterar el
  apilado visible.
- **Alcance:** añadir a `tokens.css` una escala (`--okip-z-base/content/overlay/navbar`) y
  **sustituir usos existentes por las variables con el MISMO valor efectivo**. No cambiar el
  z por orden de los bloques (`$order+1`), solo los fijos globales (navbar, overlays).
- **Archivos:** `assets/css/tokens.css`, `assets/css/components.css`,
  `assets/css/transitions.css` (y cualquier `blocks/*/style.css` con z fijo).
- **Riesgos:** cambiar un valor efectivo rompe cobertura sticky o tapa el navbar.
- **Aceptación:** grep no encuentra z-index numéricos fuera de tokens; apilado idéntico.
- **Pruebas:** PP-3, PP-5 (navbar sobre todo, transiciones de cobertura).
- **Reversión:** revertir el commit.

## Etapa 1.2 — Tokens de duración/easing de movimiento
- **Objetivo:** centralizar duraciones (`--okip-dur-fast/base/slow`) y easings usados en CSS.
- **Alcance:** definir tokens con los valores ya en uso y reemplazar literales en CSS global.
  NO tocar duraciones de motion que viven en config/JSON (esas las controla el admin).
- **Archivos:** `assets/css/tokens.css`, `base.css`, `components.css`, `animations.css`.
- **Riesgos:** cambiar un timing altera la percepción de una transición.
- **Aceptación:** valores efectivos idénticos; solo se reemplazan literales por tokens.
- **Pruebas:** PP-5 (timings de navbar y reveals).
- **Reversión:** revertir el commit.

## Etapa 1.3 — Garantizar contrato de bloque en todos los bloques
- **Objetivo:** que TODO bloque emita `id="{instance_id}"` + `data-block-instance` y tenga
  `layout.z_index` default `0` (auto por orden).
- **Alcance:** auditar los 6 bloques; corregir los que falten. Verificar que el reanclaje del
  navbar (vecino por DOM) funciona con cada bloque como "coverBlock".
- **Archivos:** `template-parts/blocks/*/block.php`, `config/blocks/*.php`.
- **Riesgos:** un default `z_index>0` rompe la escalera de cobertura.
- **Aceptación:** los 6 bloques cumplen el contrato; reordenar no rompe apilado ni navbar.
- **Pruebas:** PP-4, PP-5, prueba de reordenar en admin.
- **Reversión:** revertir el commit.

## Etapa 1.4 — READMEs de bloques existentes
- **Objetivo:** cada bloque documenta su contrato (config, animación, data-attrs, vecino).
- **Alcance:** crear `template-parts/blocks/{type}/README.md` para los 6 tipos según la
  plantilla de `TARGET_ARCHITECTURE §3.3`. Solo documentación.
- **Archivos:** `template-parts/blocks/*/README.md` (nuevos).
- **Riesgos:** ninguno (docs).
- **Aceptación:** 6 READMEs completos y coherentes con el código.
- **Pruebas:** lectura cruzada con `config/blocks/*` y `block.php`.
- **Reversión:** borrar los README.

## Etapa 1.5 — Sincronizar `CLAUDE.md` con la realidad
- **Objetivo:** corregir desfases de `CLAUDE.md` (admin "stubs" → implementado; motion y
  tipografía presentes; JS en footer).
- **Alcance:** editar `CLAUDE.md` para reflejar el estado real y apuntar a `docs/`.
- **Archivos:** `CLAUDE.md`.
- **Riesgos:** introducir una afirmación falsa; verificar contra el código.
- **Aceptación:** `CLAUDE.md` no contradice el código ni `TARGET_ARCHITECTURE.md`.
- **Pruebas:** revisión.
- **Reversión:** revertir el commit.

---

# FASE 2 — Unificación de animaciones

## Etapa 2.1 — Auditar cada bloque contra el árbol de decisión
- **Objetivo:** confirmar que cada efecto usa la tecnología correcta (`TARGET_ARCHITECTURE §4.1`).
- **Alcance:** inventario por bloque: qué usa (GSAP/ST/sticky/IO/scroll) y si es lo correcto.
  Producir `docs/ANIMATION_AUDIT.md` con hallazgos y desviaciones (sin corregir aún).
- **Archivos:** `docs/ANIMATION_AUDIT.md` (nuevo); lectura de `blocks/*/script.js`, `animations.js`.
- **Riesgos:** ninguno (solo análisis).
- **Aceptación:** tabla bloque × tecnología × ¿correcto? con acciones propuestas.
- **Pruebas:** N/A (análisis).
- **Reversión:** borrar el doc.

## Etapa 2.2 — Corregir desviaciones detectadas (una por commit)
- **Objetivo:** migrar cada efecto fuera de las reglas a la tecnología correcta, sin cambio visual.
- **Alcance:** SOLO las desviaciones listadas en 2.1, **una a la vez**. Ej.: un reveal por
  scroll listener → IO línea de disparo; dos ST en un nodo → uno solo.
- **Archivos:** el `script.js`/`style.css`/`block.php` del bloque afectado.
- **Riesgos:** alto: tocar animación puede alterar timing/comportamiento. Verificación visual
  obligatoria a varias velocidades.
- **Aceptación:** comportamiento visual idéntico; tecnología conforme a §4.2.
- **Pruebas:** PP-2, PP-5 (scroll normal y rápido).
- **Reversión:** revertir el commit de esa desviación (aisladas por diseño).

## Etapa 2.3 — Extraer presets de animación duplicados al núcleo
- **Objetivo:** que efectos repetidos vivan como preset del motor, no copiados por bloque.
- **Alcance:** identificar animaciones equivalentes en ≥2 bloques y promoverlas a preset en
  `animation-controls.php` / `animations.js` / `animations.css`. Los bloques pasan a
  referenciarlas por nombre en config.
- **Archivos:** `inc/animation-controls.php`, `assets/js/animations.js`,
  `assets/css/animations.css`, config de los bloques afectados.
- **Riesgos:** un preset compartido mal parametrizado cambia varios bloques a la vez.
- **Aceptación:** menos duplicación; comportamiento idéntico en cada bloque consumidor.
- **Pruebas:** PP-1/2, PP-5 en todos los bloques que usen el preset.
- **Reversión:** revertir el commit.

---

# FASE 3 — Consolidación CSS / JS

## Etapa 3.1 — Barrido de duplicación y valores mágicos en CSS
- **Objetivo:** que todo color/espacio/radio venga de tokens; eliminar reglas muertas.
- **Alcance:** grep de literales; reemplazo por tokens con valor idéntico; borrar CSS no usado
  (confirmado por inspección). Un archivo por commit.
- **Archivos:** `assets/css/*.css`, `blocks/*/style.css`.
- **Riesgos:** borrar una regla en uso; renombrar un token que otro archivo consume.
- **Aceptación:** sin literales de diseño fuera de tokens; render idéntico.
- **Pruebas:** PP-5 amplio.
- **Reversión:** revertir el commit del archivo.

## Etapa 3.2 — (Opcional) Reorganizar `assets/js/` por rol
- **Objetivo:** agrupar JS núcleo por rol SOLO si el volumen lo justifica (`TARGET_ARCHITECTURE §5.2`).
- **Alcance:** mover a `core/`, `animation/`, `ui/`, `admin/` **actualizando handles y rutas de
  enqueue**. Sin cambiar lógica ni el modelo de carga (siguen scripts encolados).
- **Archivos:** `assets/js/**`, `inc/enqueue.php`, `inc/block-loader.php`.
- **Riesgos:** romper deps de `wp_enqueue_script` → se cae todo el JS. Alto.
- **Aceptación:** mismos handles efectivos y orden de carga; todo funciona igual.
- **Pruebas:** PP-2 (todos), PP-3, PP-5 completo.
- **Reversión:** revertir el commit (mover no destruye historia).
- **Precondición:** solo abordar si algún archivo núcleo supera ~450 líneas o hay lógica
  compartida por 3+ consumidores. Si no, **saltar esta etapa**.

---

# FASE 4 — Admin end-to-end (overrides sin tocar el tema)

## Etapa 4.1 — Inventario del estado real del admin
- **Objetivo:** saber exactamente qué edición/guardado ya funciona y qué falta.
- **Alcance:** revisar `inc/admin/*` + `assets/js/admin-blocks.js`; documentar en
  `docs/ADMIN_STATUS.md` qué bloques son editables, qué se guarda, qué se sanea.
- **Archivos:** `docs/ADMIN_STATUS.md` (nuevo). Solo análisis.
- **Riesgos:** ninguno.
- **Aceptación:** matriz bloque × (editor / sanitizer / guardado) con estado real.
- **Pruebas:** probar el panel en `wp-admin` manualmente.
- **Reversión:** borrar el doc.

## Etapa 4.2 — Completar edición de overrides por bloque (uno por etapa)
- **Objetivo:** cada bloque editable guarda overrides (diff) en `wp_options` y se refleja en front.
- **Alcance:** **un bloque por conversación**: editor + `okip_admin_sanitize_{type}_data()` +
  verificación del pipeline diff → `okip_apply_page_block_overrides`.
- **Archivos:** `inc/admin/editors/{type}.php`, `inc/admin/sanitizers.php`,
  `inc/admin/save-handlers.php` (si falta), `config/blocks/{type}.php` (schema de referencia).
- **Riesgos:** un saneo laxo permite datos inválidos que rompen el render; verificar contra
  `okip_normalize_{type}_data`.
- **Aceptación:** editar en admin → guardar → cambio visible en front; borrar option → vuelve
  a defaults; `config/` intacto.
- **Pruebas:** PP-1, PP-4, PP-5, y ciclo editar/guardar/revertir en `wp-admin`.
- **Reversión:** revertir el commit; borrar el option de prueba.

## Etapa 4.3 — Reordenar bloques desde el admin (verificar y endurecer)
- **Objetivo:** confirmar que el reorden guardado (`okip_page_blocks_order_{slug}`) respeta z
  por orden, navbar por vecino y remapeo de ids.
- **Alcance:** pruebas de reordenar + endurecer el remapeo de `instance_id` si falta cobertura.
- **Archivos:** `inc/data.php`, `inc/admin/admin-pages.php`, `assets/js/admin-blocks.js`.
- **Riesgos:** reordenar rompe apilado o desancla el navbar.
- **Aceptación:** cualquier orden funciona; z y navbar correctos; ids renombrados remapeados.
- **Pruebas:** PP-4, PP-5 con 2–3 órdenes distintos.
- **Reversión:** borrar el option de orden restaura config; revertir commit si hubo código.

---

# FASE 5 — Bloques pendientes y escalabilidad

## Etapa 5.1 — Verificar/consolidar `product-story`, `mission-statement`, `news`
- **Objetivo:** llevar los bloques 4–6 al mismo estándar (contrato, animación por reglas, README).
- **Alcance:** **un bloque por conversación**: revisar contra `TARGET_ARCHITECTURE`, corregir
  desviaciones de animación, añadir README, confirmar fallbacks. Para `news`: `WP_Query` por
  categoría con fallback dummy.
- **Archivos:** `template-parts/blocks/{type}/*`, `config/blocks/{type}.php`.
- **Riesgos:** cambio visual no intencionado; en `news`, consultas que fallen sin posts.
- **Aceptación:** bloque conforme a reglas, con README y fallback; sin regresión visual.
- **Pruebas:** PP-1/2/4/5.
- **Reversión:** revertir el commit del bloque.

## Etapa 5.2 — Scaffold de bloque nuevo (plantilla + guía)
- **Objetivo:** hacer trivial y consistente crear un bloque futuro.
- **Alcance:** crear `template-parts/blocks/_template/` (block.php + style.css + script.js +
  README) con los patrones canónicos comentados, y una guía `docs/NEW_BLOCK.md` (checklist de
  los 6 pasos + contrato). El `_template` no se registra en la whitelist.
- **Archivos:** `template-parts/blocks/_template/*`, `docs/NEW_BLOCK.md` (nuevos).
- **Riesgos:** que el scaffold se registre por error y se intente renderizar.
- **Aceptación:** copiar `_template` + 6 pasos produce un bloque funcional; no aparece en front.
- **Pruebas:** PP-1, PP-3 (confirmar que `_template` no rompe nada).
- **Reversión:** borrar la carpeta y el doc.

## Etapa 5.3 — Páginas internas reales (contacto, sala-de-prensa, fábrica)
- **Objetivo:** poblar páginas hoy vacías con bloques reutilizados, validando el desacople.
- **Alcance:** **una página por conversación**: componer su `config/pages/{slug}.php` con
  instancias de bloques existentes. Sin bloques nuevos (usa 5.2 si hiciera falta).
- **Archivos:** `config/pages/{slug}.php`.
- **Riesgos:** que un bloque asuma "home" en algún selector; verificar scope por instancia.
- **Aceptación:** la página renderiza sus bloques; navbar y transiciones correctos sin Hero.
- **Pruebas:** PP-3 de la URL, PP-4 con el slug, PP-5.
- **Reversión:** revertir `config/pages/{slug}.php` a `[]`.

---

## Dependencias entre etapas

```
0.1 ─▶ 0.2 ─▶ (Fase 1 en paralelo: 1.1 1.2 1.3 1.4 1.5)
                       │
                       ▼
              2.1 ─▶ 2.2 ─▶ 2.3
                       │
                       ▼
              3.1 ─▶ (3.2 opcional)
                       │
                       ▼
              4.1 ─▶ 4.2 ─▶ 4.3
                       │
                       ▼
              5.1 ─▶ 5.2 ─▶ 5.3
```

Fase 1 puede intercalarse; Fases 2→5 conviene ejecutarlas en orden porque cada una asume la
consolidación de la anterior. Ninguna etapa depende de una futura.
