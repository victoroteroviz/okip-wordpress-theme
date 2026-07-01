# OKIP Theme — Architecture Decision Records (ADR)

> Sistema de registro de decisiones arquitectónicas del proyecto.
> **Autoridad:** un ADR aprobado está por debajo de `ENGINEERING_PRINCIPLES.md` y por encima de
> `TARGET_ARCHITECTURE.md`, `ROADMAP.md` y `BACKLOG.md` (ver `ENGINEERING_PRINCIPLES §0`).
>
> **Estado de esta carpeta:** el **sistema** está diseñado (este README + `TEMPLATE.md`).
> Aún **no** se han escrito los ADR concretos. Ver §7 "Backlog de ADR a redactar".

---

## 1. Qué es un ADR y para qué sirve

Un **Architecture Decision Record** es un documento corto e inmutable que captura **una**
decisión arquitectónica: el contexto en que se tomó, las alternativas consideradas, la decisión
final y sus consecuencias.

**Por qué existen en OKIP:** el proyecto evoluciona por conversaciones independientes (una etapa
por sesión). Sin un registro de *por qué* se decidió algo, una conversación futura puede
"deshacer" una decisión deliberada creyendo que fue un accidente. El ADR preserva la **intención**
detrás de la arquitectura, no solo su estado (eso lo hace `docs/audit/*`).

Un ADR responde a: *"¿Por qué esto es así y no de otra forma? ¿Qué se descartó y por qué?"*

---

## 2. Cuándo crear un ADR (y cuándo NO)

### Crea un ADR cuando la decisión…

- **Fija o cambia una regla** de `ENGINEERING_PRINCIPLES.md` o de `TARGET_ARCHITECTURE.md`.
- **Elige entre alternativas técnicas** con consecuencias duraderas: una librería, un patrón de
  animación, una frontera núcleo/bloque, un modelo de datos, una convención transversal.
- **Introduce o acepta una excepción** a un principio (p. ej. la excepción de Google Fonts).
- **Establece un contrato** que otros consumirán: una API pública (`OKIP.*`), un schema
  (JSON de motion), un formato de option (`okip_page_blocks_*`).
- **Revierte o supersede** una decisión previa.

### NO crees un ADR cuando…

- Es un **hecho descriptivo** del código (eso va a `docs/audit/*` o al README del bloque).
- Es contenido o data de una página (`config/pages/*`).
- Es una corrección de bug sin cambio de arquitectura.
- Es una preferencia trivial sin consecuencias duraderas (un nombre de variable local).

**Prueba rápida:** si dentro de seis meses alguien podría querer saber *por qué* se decidió, y
cambiarlo tendría coste o riesgo → merece un ADR. Si solo describe *qué hay* hoy → no.

---

## 3. Formato

Todo ADR usa `TEMPLATE.md`. Estructura obligatoria:

| Campo | Contenido |
|---|---|
| **Título** | `# ADR NNNN — <decisión en una frase>` |
| **Estado** | `Propuesto` · `Aceptado` · `Rechazado` · `Superseded por ADR-NNNN` · `Deprecado` |
| **Fecha** | `YYYY-MM-DD` (fecha absoluta de la decisión/último cambio de estado). |
| **Contexto** | El problema y las fuerzas en juego. Qué se necesitaba decidir y por qué ahora. |
| **Decisión** | La decisión, en presente afirmativo ("Usamos X…", "Prohibimos Y…"). |
| **Alternativas consideradas** | Cada opción descartada con **por qué** se descartó. |
| **Consecuencias** | Positivas, negativas y neutras. Qué deuda o excepción se acepta. |
| **Relación con los principios** | Qué principio/regla fija, cambia o excepciona. |
| **Enlaces** | Etapa(s) del `ROADMAP`, ítem(s) del `BACKLOG`, ADR relacionados/superseded. |

Reglas de forma:

- **Uno por decisión.** Si un documento cubre dos decisiones, sepáralo en dos ADR.
- **Corto.** Un ADR cabe en una pantalla o dos; si crece, probablemente mezcla decisiones.
- **En español** (prosa), identificadores de código en inglés (convención del proyecto).
- **Autocontenido:** se entiende sin abrir otros documentos (aunque enlace a ellos).

---

## 4. Numeración y nombres de archivo

- Formato de archivo: `NNNN-slug-kebab-case.md` (p. ej. `0001-use-gsap.md`).
- **NNNN** = entero de 4 dígitos, **secuencial y global**, empezando en `0001`. No se reutiliza
  ni se reordena: el número es un identificador permanente, no una prioridad.
- El **slug** resume la decisión en pocas palabras (`no-build-step`, `block-contract`).
- Los ADR **rechazados** conservan su número (no se borran): el registro de lo que se descartó
  también es conocimiento.
- Un ADR que **supersede** a otro toma el **siguiente número libre**; nunca "hereda" el número
  del que reemplaza.

---

## 5. Ciclo de vida y estados

```
        crear                 aprobar
Propuesto ───────▶ (revisión) ───────▶ Aceptado
    │                                      │
    │ rechazar                             │ una decisión posterior lo reemplaza
    ▼                                      ▼
Rechazado                            Superseded por ADR-MMMM
                                           │
                                    (o queda) Deprecado  ← ya no aplica, sin reemplazo directo
```

- **Propuesto:** redactado, pendiente de aprobación. Puede editarse libremente **mientras** esté
  en este estado.
- **Aceptado:** aprobado y vigente. **A partir de aquí es inmutable en su contenido**: solo puede
  cambiar su *Estado* (a Superseded/Deprecado) y añadirse enlaces.
- **Rechazado:** se evaluó y se descartó. Se conserva con el motivo del rechazo.
- **Superseded por ADR-MMMM:** una decisión nueva lo reemplaza. Ambos se enlazan mutuamente
  (el viejo apunta al nuevo y viceversa).
- **Deprecado:** ya no aplica (p. ej. desapareció la parte del sistema a la que se refería) y no
  tiene un reemplazo directo.

### Cuándo se "actualiza" un ADR

- **Nunca** se reescribe el contenido de un ADR **Aceptado** para cambiar la decisión. Para
  cambiar de opinión se **crea un ADR nuevo** que lo supersede.
- **Sí** se permite editar un ADR Aceptado para: cambiar su campo *Estado*, corregir una errata
  evidente, o **añadir enlaces** (a la etapa que lo implementó, a un ADR que lo supersede).
  Cualquier edición de este tipo actualiza la *Fecha*.

Esta inmutabilidad es lo que hace fiable el registro: leer un ADR viejo cuenta lo que se pensaba
**entonces**, no una versión reescrita a posteriori.

---

## 6. Aprobación

- **Quién aprueba:** el **mantenedor/propietario del proyecto** (hoy, el usuario) es la única
  autoridad que mueve un ADR de `Propuesto` a `Aceptado` o `Rechazado`.
- **Rol de la IA / colaboradores:** **redactar** el ADR (contexto, alternativas, recomendación) y
  **proponerlo**; nunca auto-aprobarlo. Una conversación puede dejar un ADR en `Propuesto` como
  entregable, pero la implementación de lo que decide **no comienza hasta que esté `Aceptado`**.
- **Un ADR que contradiga un principio no negociable** (`ENGINEERING_PRINCIPLES §2`) es inválido,
  salvo que su propia decisión sea **modificar explícitamente** ese principio — y esa
  modificación requiere la misma aprobación del mantenedor.

---

## 7. Relación con el roadmap, el backlog y los principios

- **Con `ENGINEERING_PRINCIPLES.md`:** un ADR **desarrolla o excepciona** un principio; nunca lo
  contradice en silencio. Si un principio necesita cambiar, el ADR lo dice y edita el principio.
- **Con `TARGET_ARCHITECTURE.md`:** un ADR **Aceptado** puede refinar la arquitectura objetivo;
  cuando lo hace, `TARGET_ARCHITECTURE` se actualiza para reflejarlo y **cita el ADR**.
- **Con `ROADMAP.md`:** una etapa que tome una decisión arquitectónica **produce un ADR** como
  parte de su entregable, y lo enlaza en su ficha. Una etapa **no** puede desviarse de un ADR
  Aceptado; si necesita hacerlo, primero se propone un ADR que lo supersede.
- **Con `BACKLOG.md`:** un ítem del backlog cuya resolución implica una decisión (p. ej. B3
  auto-host de fuentes) se cierra **con** el ADR correspondiente enlazado.

### Backlog de ADR a redactar (propuesta inicial, aún NO escritos)

Estos capturan decisiones **ya tomadas** en el proyecto; redactarlos formaliza el registro.
El orden es sugerencia, no obligación.

| Nº sugerido | Decisión a registrar | Origen |
|---|---|---|
| `0001-use-gsap` | GSAP local y condicional como motor de animación, con fallback vanilla. | Decisión cerrada #8 (`CLAUDE.md §3`) |
| `0002-no-build-step` | Sin bundler/módulos ES/CDNs; scripts encolados y versionados por `filemtime`. | `ENGINEERING_PRINCIPLES §2.9`, `TARGET_ARCHITECTURE §5` |
| `0003-block-contract` | Contrato de bloque (whitelist, scope por instancia, z por orden, README). | `ENGINEERING_PRINCIPLES §6`, `TARGET_ARCHITECTURE §3` |
| `0004-animation-decision-tree` | Árbol de decisión de animaciones (GSAP/ST/sticky/IO/CSS). | `ENGINEERING_PRINCIPLES §5`, `TARGET_ARCHITECTURE §4` |
| `0005-content-in-wp-options` | Contenido editable en `wp_options` como diff, nunca en archivos del tema. | Decisión cerrada #7 (`CLAUDE.md §3`) |
| `0006-doc-precedence` | Jerarquía de precedencia documental y matiz "código real". | Conflicto **CD-1** (`ENGINEERING_PRINCIPLES §10`) |
| `0007-google-fonts-source` | Resolver la excepción de Google Fonts (auto-host vs. excepción aceptada). | Conflicto **CD-2** / `BACKLOG B3` |

> **No** redactar todos de golpe: cada ADR es un entregable pequeño y se propone cuando su
> decisión se toca. `0006` y `0007` corresponden a conflictos abiertos y requieren decisión del
> usuario antes de pasar a `Aceptado`.

---

## 8. Índice de ADR

*(Vacío por ahora — se irá poblando. Una línea por ADR, más reciente primero o por número.)*

| Nº | Título | Estado | Fecha |
|----|--------|--------|-------|
| —  | *(ninguno todavía)* | — | — |
</content>
