# OKIP Theme — Documentación

Punto de entrada del conocimiento del proyecto. Léelo primero en cada sesión nueva.

## Orden de lectura recomendado

1. **`../CLAUDE.md`** — contexto rápido del tema (ver correcciones en el punto 0 de abajo).
2. **`TARGET_ARCHITECTURE.md`** — arquitectura objetivo y **reglas no negociables**. Manda
   sobre la auditoría y sobre `CLAUDE.md` cuando haya conflicto.
3. **`ROADMAP.md`** — etapas de evolución. Ejecuta **una** por conversación.
4. **`BACKLOG.md`** — mejoras priorizadas (Crítica/Alta/Media/Baja) mapeadas a etapas.

## Auditoría (estado actual del sistema) — `audit/`

Fuente de conocimiento del **estado actual**. Descriptiva, no prescriptiva.

- `audit/ARCHITECTURE.md` — arquitectura completa del sistema.
- `audit/FLOW.md` — ciclo de vida petición → render → animación.
- `audit/DEPENDENCIES.md` — mapa de dependencias PHP/CSS/JS.
- `audit/ANIMATION_SYSTEM.md` — motion (entry/playback/exit) y presets.
- `audit/CSS_ARCHITECTURE.md` — cascada, tokens, BEM.
- `audit/JS_ARCHITECTURE.md` — capas JS y API `OKIP.*` / `OKIPAnimations.*`.
- `audit/ADMIN_SYSTEM.md` — panel admin y flujo de overrides.
- `audit/PROJECT_MAP.md` — mapa navegable de archivos y responsabilidades.

## Precedencia ante conflictos

**Código real > `TARGET_ARCHITECTURE.md` > `docs/audit/*` > `CLAUDE.md`.**

Correcciones conocidas de la auditoría: ver `TARGET_ARCHITECTURE.md §0` (JS en footer, admin
implementado no stub, ejemplos CSS anidados son ilustrativos).

## Regla de oro

Ninguna etapa puede dejar la landing rota ni cambiar el comportamiento visual salvo bug real.
Cada cambio: pequeño, aislado, reversible, verificable. GSAP sigue siendo el motor principal.
El orden de los bloques es dinámico: nunca asumas un orden fijo.
