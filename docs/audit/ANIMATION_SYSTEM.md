# OKIP Theme — Sistema Completo de Animaciones

## 1. Concepto Unificado

**Motion** = Animaciones estructuradas en **3 fases** + **3 targets** + **presets reutilizables**

```
Fases:    Entry (entrada) → Playback (continuo) → Exit (salida)
Targets:  Background | Text | Cards
Config:   preset, duration, delay, stagger, ease, transforms, opacity, blur
```

---

## 2. Arquitectura de 3 Fases

### Fase 1: Entry (Reveal)
**Cuándo:** Bloque entra en viewport
**Duración:** ~700ms (configurable)
**Propósito:** Hacer visible el contenido con animación de entrada

**Parámetros:**
- `enabled` (bool) - Activa la fase
- `preset` (string) - Preset visualizado (soft-arrive, fade-blur, stagger-fade-up, etc.)
- `duration_ms` (int) - Duración total (0-20000)
- `delay_ms` (int) - Espera antes de iniciar (0-20000)
- `stagger_ms` (int) - Delay entre items (0-5000)
- `ease` (string) - Easing function (power3.out default)
- `opacity_from`, `opacity_to` - Opacidad inicial → final (0-1)
- `x_from`, `x_to`, `y_from`, `y_to` - Posición inicial → final (px)
- `scale_from`, `scale_to` - Escala inicial → final (0-5)
- `rotate_from`, `rotate_to` - Rotación inicial → final (-360-360°)
- `blur_from`, `blur_to` - Blur inicial → final (0-80px)

**Flujo:**
```
0ms: Estado oculto (from)
      ↓
delay_ms: Comienza animación
      ↓
duration_ms: Termina
      ↓
Estado visible (to)
```

### Fase 2: Playback (Continuo)
**Cuándo:** Bloque está visible en viewport (después de entry)
**Duración:** Ciclo (default 4200ms)
**Propósito:** Movimiento continuo mientras es visible

**Parámetros adicionales:**
- `intensity` (0-1) - Magnitud del movimiento (0.5 default)
- `speed` (0.1-5) - Multiplicador de duración (1 default)
- `direction` (alternate|normal|reverse) - Dirección del movimiento
- `yoyo` (bool) - Invierte dirección en ciclos (true default)

**Flujo:**
```
Ciclo 1: 0→100% (p.ej. izq→der)
      ↓
direction=alternate → Ciclo 2: 100→0% (der→izq)
      ↓
yoyo=true → Repite ciclo 1
      ↓
Continúa mientras esté en viewport
```

### Fase 3: Exit (Salida)
**Cuándo:** Bloque sale del viewport
**Duración:** ~500ms (configurable)
**Propósito:** Fade-out suave

**Parámetros:**
- Similar a entry (enabled, preset, duration, delay, ease, transforms)
- Típicamente: opacidad 1→0, y 0→-18px, scale 1→0.96

**Flujo:**
```
Bloque sale del viewport
      ↓
watchExit() dispara exit
      ↓
duration_ms: Anima salida
      ↓
Estado desaparece
```

---

## 3. Presets por Target × Fase

### Background Presets

**Entry:**
- **soft-arrive** - Blur in, opacidad suave (18% → 100%), escala (102.6% → 100%), blur (6→0)
- **fade-blur** - Fade + blur (opacidad 0 → 1, blur 6 → 0)
- **rise-depth** - Sube mientras aparece y crece (y +8px → 0, scale 102.6% → 100%)
- **none** - Sin animación

**Playback:**
- **variant-motion** - Movimiento líquido (18s ciclo, intensity 0.34, speed 0.82)
- **slow-drift** - Deriva lenta
- **pulse-field** - Pulsación de campo
- **none** - Sin movimiento

**Exit:**
- **fade-depth** - Desvanece a la profundidad (opacity 100% → 35%, y 0 → -30px, scale → 96%, blur → 8px)
- **soft-blur-out** - Blur out suave (opacity → 0, blur → 8px)
- **none** - Sin animación

### Text Presets

**Entry:**
- **stagger-fade-up** - Aparece con delay escalonado y sube (y +28px → 0, opacity 0 → 1, stagger 120ms)
- **soft-mask-up** - Máscara suave hacia arriba
- **fade-only** - Solo opacidad (no transform)
- **none** - Sin animación

**Playback:**
- (Text no tiene playback típicamente)

**Exit:**
- **fade-up** - Desvanece hacia arriba (opacity 1 → 0, y 0 → -18px)
- **fade-down** - Desvanece hacia abajo
- **none** - Sin animación

### Cards Presets

**Entry:**
- **stagger-float-up** - Flota hacia arriba escalonado (y +24px → 0, scale 96% → 100%, stagger 120ms)
- **scale-fade** - Escala mientras aparece
- **slide-depth** - Desliza desde profundidad (z-depth animation)
- **none** - Sin animación

**Playback:**
- **float-soft** - Flotación suave (y 0 → -10px → 0, scale 100% → 102% → 100%, yoyo)
- **glow-pulse** - Pulsación de brillo
- **none** - Sin movimiento

**Exit:**
- **fade-scale** - Desvanece mientras reduce escala (opacity 1 → 0, scale 100% → 96%)
- **float-down** - Flota hacia abajo
- **none** - Sin animación

---

## 4. Easing Functions

Disponibles:
- `power1.out` - Salida lenta (suave)
- `power2.out` - Salida más lenta
- `power3.out` - Salida muy lenta (DEFAULT)
- `power4.out` - Salida extremadamente lenta
- `sine.out` - Sinusoidal suave
- `expo.out` - Exponencial suave
- `circ.out` - Circular suave
- `back.out` - Rebote al final
- `linear` - Constante
- `none` - Instantáneo

---

## 5. Sistema Híbrido: GSAP vs CSS

### Opción 1: GSAP Disponible

```javascript
// okipGsap.ready = true (GSAP y ScrollTrigger cargados)

OKIPAnimations.create(root, config);
  ↓
animateGsap(items, stageConfig, fromSide, toSide)
  ├─ gsap.timeline() → anima con GSAP
  ├─ Soporta ScrollTrigger para coreografías complejas
  ├─ Smooth, responsive, optimizado
  └─ Fallback a CSS si prefers-reduced-motion

Ventajas:
  ✅ Suave en cualquier velocidad
  ✅ Transformaciones optimizadas (GPU)
  ✅ ScrollTrigger para sync con scroll
  ✅ Mejor performance en animaciones complejas
```

### Opción 2: GSAP No Disponible

```javascript
// okipGsap.ready = false (archivo no existe)

OKIPAnimations.create(root, config);
  ↓
animateCss(items, stageConfig, side)
  ├─ CSS transitions
  ├─ requestAnimationFrame loop
  ├─ Funcional pero menos smooth
  └─ Mantiene interactividad

Ventajas:
  ✅ Sin dependencia externa
  ✅ Funciona en cualquier navegador
  ✅ Fallback seguro
  
Desventajas:
  ⚠️ Menos smooth en animations largas
  ⚠️ Límite en coreografías complejas
```

### Opción 3: prefers-reduced-motion

```javascript
// User solicita reducir movimiento

OKIPAnimations.create(root, config)
  ↓
Si OKIP.reduceMotion = true:
  ├─ prepare() → establece estado final (to)
  ├─ enter() → aplica cambios SIN animación
  ├─ playback() → omitido
  ├─ exit() → aplica cambios SIN animación
  └─ Mantiene interactividad pero sin movimiento

Ventaja:
  ✅ Respeta preferencia de accesibilidad
  ✅ No sacrifica funcionalidad
```

---

## 6. Configuración JSON

### Estructura Completa

```json
{
  "motion": {
    "enabled": true,
    "replay_mode": "once",           // once | replay
    "exit_trigger": "viewport_leave", // viewport_leave | none
    
    "background": {
      "entry": {
        "enabled": true,
        "preset": "soft-arrive",
        "duration_ms": 1180,
        "delay_ms": 0,
        "stagger_ms": 0,
        "ease": "power3.out",
        "opacity_from": 0.18,
        "opacity_to": 1,
        "x_from": 0, "x_to": 0,
        "y_from": 8, "y_to": 0,
        "scale_from": 1.026, "scale_to": 1,
        "rotate_from": 0, "rotate_to": 0,
        "blur_from": 6, "blur_to": 0
      },
      "playback": {
        "enabled": true,
        "preset": "variant-motion",
        "duration_ms": 18000,
        "intensity": 0.34,
        "speed": 0.82,
        "direction": "alternate",
        "yoyo": true,
        ... (otros parámetros)
      },
      "exit": {
        "enabled": true,
        "preset": "fade-depth",
        "duration_ms": 700,
        "opacity_from": 1,
        "opacity_to": 0.35,
        "y_from": 0, "y_to": -30,
        "scale_from": 1, "scale_to": 0.96,
        "blur_from": 0, "blur_to": 8,
        "ease": "power2.out"
      }
    },
    
    "text": {
      "entry": { ... },
      "exit": { ... }
    },
    
    "cards": {
      "entry": { ... },
      "playback": { ... },
      "exit": { ... }
    }
  },
  
  "selectors": {
    "background": "[data-okip-motion-target=\"background\"]",
    "text": "[data-okip-motion-target=\"text\"]",
    "cards": "[data-okip-motion-target=\"cards\"]"
  }
}
```

### Minimalista (Defaults)

```json
{
  "motion": {
    "enabled": true,
    "background": {
      "entry": { "preset": "soft-arrive" },
      "playback": { "preset": "variant-motion" },
      "exit": { "preset": "fade-depth" }
    },
    "text": {
      "entry": { "preset": "stagger-fade-up" },
      "exit": { "preset": "fade-up" }
    },
    "cards": {
      "entry": { "preset": "stagger-float-up" },
      "playback": { "preset": "float-soft" },
      "exit": { "preset": "fade-scale" }
    }
  },
  "selectors": { ... }
}
```

---

## 7. API de OKIPAnimations

### Factory

```javascript
const animator = OKIPAnimations.create(root, config);
```

**Retorna objeto animator con métodos:**

```javascript
animator.prepare(targets)
  // Aplica estado "from" sin animación
  // Clases: okip-motion-prepared

animator.enter(targets)
  // Anima entry de targets
  // Clases: is-motion-entered-{target}

animator.playback(targets)
  // Inicia movimiento continuo
  // Clases: is-motion-enabled, okip-motion-playback--{preset}

animator.enterThenPlayback(targets)
  // Espera entry, luego playback
  // Uso: coordinar entrada + movimiento

animator.exit(targets)
  // Anima salida de targets
  // Clases: is-motion-exited-{target}

animator.restore(targets)
  // Restaura a estado final sin animación

animator.prepareAll(targets[])
animator.enterAll(targets[])
animator.playbackAll(targets[])
animator.exitAll(targets[])
animator.finishAll(targets[])
  // Versiones batch de arriba

animator.watchExit(targets[])
  // IntersectionObserver automático
  // Dispara exit cuando targets salen del viewport
  // Limpia observer automáticamente
```

### Parseo de Config

```javascript
const config = OKIPAnimations.parseConfig(root);
// Lee <script data-okip-motion-config> dentro de root
// Retorna JSON parseado

const animator = OKIPAnimations.create(root, config);
```

---

## 8. Flujo de Animaciones en Hero

### Estructura HTML

```html
<section id="home-hero-main" data-okip-hero data-motion-enabled="1">
  <script data-okip-motion-config>{ ... JSON config ... }</script>
  
  <div class="okip-hero__bg" data-okip-motion-target="background">
    <video data-okip-hero-intro><!-- intro --></video>
    <video data-okip-hero-loop><!-- loop --></video>
  </div>
  
  <div class="okip-hero__cards" data-okip-hero-cards>
    <div class="okip-hero__card" data-okip-motion-target="cards">...</div>
    <div class="okip-hero__card" data-okip-motion-target="cards">...</div>
  </div>
  
  <div class="okip-hero__content" data-okip-motion-target="text">
    <h1>...</h1>
  </div>
</section>
```

### Secuencia de Ejecución (hero/script.js)

```javascript
OKIP.ready(() => {
  const hero = document.querySelector('[data-okip-hero]');
  if (!hero || !hero.dataset.motionEnabled) return;
  
  const config = JSON.parse(
    hero.querySelector('[data-okip-motion-config]').textContent
  );
  
  const animator = OKIPAnimations.create(hero, config);
  const targets = hero.querySelectorAll('[data-okip-motion-target]');
  
  // 1. PREPARE (estado inicial oculto)
  animator.prepare(targets);
  
  // 2. INICIA INTRO VIDEO
  const intro = hero.querySelector('[data-okip-hero-intro]');
  if (intro) {
    intro.addEventListener('ended', () => {
      // CROSSFADE A LOOP
      startLoop(animator, hero);
    });
    intro.play();
  }
  
  // 3. SCHEDULE CONTENT ENTRY
  setTimeout(() => {
    animator.enter(backgroundTargets);  // Entra background
    
    setTimeout(() => {
      animator.enter(textTargets);  // Entra texto
      animator.playback(backgroundTargets);  // Playback background
      
      setTimeout(() => {
        animator.enterThenPlayback(cardTargets);  // Entra + playback cards
        animator.watchExit(targets);  // Monitor salida
      }, 300);
    }, 600);
  }, 900);  // content_entry_delay
});
```

---

## 9. Casos de Uso

### Caso 1: Reveal Simple (Sin Playback)

```php
// config/pages/home.php
'motion' => [
    'text' => [
        'entry' => [
            'preset' => 'stagger-fade-up',
            'duration_ms' => 700,
            'delay_ms' => 600,
            'stagger_ms' => 120
        ],
        'exit' => [
            'preset' => 'fade-up',
            'duration_ms' => 500
        ]
    ]
]
```

### Caso 2: Movimiento Continuo (Con Playback)

```php
'motion' => [
    'background' => [
        'entry' => [ 'preset' => 'soft-arrive', 'duration_ms' => 1180 ],
        'playback' => [
            'preset' => 'variant-motion',
            'duration_ms' => 18000,
            'intensity' => 0.34,
            'speed' => 0.82
        ],
        'exit' => [ 'preset' => 'fade-depth', 'duration_ms' => 700 ]
    ]
]
```

### Caso 3: Sin Animación (prefers-reduced-motion)

```javascript
if (OKIP.reduceMotion) {
    // animator.enter() → aplica cambios sin animación
    // animator.playback() → omitido
    // animator.exit() → aplica cambios sin animación
    // → Interactividad mantida, movimiento suprimido
}
```

---

## 10. Debugging de Animaciones

### Ver Clases CSS Aplicadas

```javascript
const hero = document.querySelector('[data-okip-hero]');

// Después de prepare()
hero.classList.contains('okip-motion-prepared')  // true

// Después de enter()
hero.classList.contains('is-motion-entered-background')  // true
hero.classList.contains('is-motion-entered-text')  // true
hero.classList.contains('is-motion-entered-cards')  // true

// Durante playback
hero.classList.contains('is-motion-enabled')  // true
hero.classList.contains('okip-motion-playback--variant-motion')  // true

// Después de exit()
hero.classList.contains('is-motion-exited-background')  // true
```

### Verificar GSAP State

```javascript
console.log(okipGsap.ready);  // true si GSAP está disponible
console.log(okipGsap.hasScrollTrigger);  // true si ScrollTrigger existe
console.log(OKIP.reduceMotion);  // true si usuario solicita reducir movimiento
```

### Inspeccionar Config JSON

```javascript
const hero = document.querySelector('[data-okip-hero]');
const config = JSON.parse(
    hero.querySelector('[data-okip-motion-config]').textContent
);
console.log(config);  // Ver estructura completa
console.log(config.motion.background.entry);  // Ver preset específico
```

