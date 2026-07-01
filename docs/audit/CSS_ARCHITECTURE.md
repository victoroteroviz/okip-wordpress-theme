# OKIP Theme — Sistema CSS Completo

## 1. Orden de Cascada Global

```
tokens.css (variables globales)
    ↓
base.css (reset + utilidades)
    ↓
layout.css (grid, containers)
    ↓
components.css (UI reutilizable)
    ↓
transitions.css (sticky-cover, animaciones CSS)
    ↓
animations.css (keyframes GSAP)
    ↓
Google Fonts CSS (dinámico)
    ↓
blocks/{type}/style.css (específico de cada bloque)
```

## 2. Tokens CSS (tokens.css)

### Colores
```css
--okip-color-bg: #020711                    /* Fondo principal oscuro */
--okip-color-bg-elev: #0a1422               /* Fondo elevado */
--okip-color-text: #f4f7fb                  /* Texto principal blanco */
--okip-color-text-muted: #b9c4d4            /* Texto atenuado */
--okip-color-accent: #ff5a14                /* Naranja primario */
--okip-color-accent-2: #3c8cff              /* Azul tecnológico */
--okip-color-line: rgba(255, 255, 255, .12) /* Bordes sutiles */

--okip-color-surface-light: #ffffff         /* Bloque claro (3-6) */
--okip-color-text-dark-muted: #7a8899       /* Texto en fondo claro */
```

### Navbar (Dinámico desde navbar.php)
```css
--okip-navbar-h: 68px
--okip-navbar-bg: rgba(0, 0, 0, .86)        /* Fondo semi-opaco */
--okip-navbar-blur: 14px                    /* Backdrop filter */
--okip-navbar-border: rgba(255, 255, 255, .12)
--okip-navbar-text: #ffffff
--okip-navbar-active: #ffffff               /* Color subrayado activo */
```

### Espaciado (Escala Modular)
```css
--okip-space-1: .25rem
--okip-space-2: .5rem
--okip-space-3: 1rem
--okip-space-4: 1.5rem
--okip-space-5: 2.5rem
--okip-space-6: 4rem
```

### Layout
```css
--okip-container: 1200px        /* Max width */
--okip-container-pad: 1.5rem    /* Padding lateral */
--okip-radius: 14px             /* Border radius estándar */
--okip-z-navbar: 1000           /* Navbar siempre arriba */
```

### Tipografía
```css
--okip-font-base: system-ui, -apple-system, "Segoe UI", ...
--okip-font-mono: ui-monospace, "SF Mono", ...
```

### Easing
```css
--okip-ease: cubic-bezier(.22, 1, .36, 1)
```

### Dinámicas (Generadas en block.php)
```css
--okip-hero-z: <order + 1>
--okip-hero-title-font-family: "Montserrat", ...
--okip-hero-title-font-size: clamp(36px, 4.2vw, 62px)
--okip-hero-title-font-weight: 300
--okip-hero-title-line-height: 1.08
--okip-hero-title-letter-spacing: 0px
--okip-hero-title-color: #ffffff
--okip-card-x: 19%
--okip-card-y: 24%
--okip-card-w: 23vw
--okip-hold-vh: 100   /* sticky-cover hold */
```

## 3. Base CSS (base.css)

**Reset Mínimo:**
```css
* { box-sizing: border-box; }

html {
    scroll-behavior: smooth;
    @media (prefers-reduced-motion: reduce) {
        scroll-behavior: auto;
    }
}

body.okip-body {
    background-color: var(--okip-color-bg);
    color: var(--okip-color-text);
    font-family: var(--okip-font-base);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
}

img, video, svg {
    max-width: 100%;
    height: auto;
    display: block;
}
```

**Accesibilidad:**
```css
:focus-visible {
    outline: 2px solid var(--okip-color-accent-2);
    outline-offset: 2px;
}

.okip-sr-only {
    clip: rect(0, 0, 0, 0);
    position: absolute;
    white-space: nowrap;
    width: 1px;
    height: 1px;
    overflow: hidden;
}

.okip-skip-link {
    position: absolute;
    top: -40px;
    left: 0;
    &:focus {
        top: 0;
    }
}
```

## 4. Layout CSS (layout.css)

**Grid Principal:**
```css
.okip-main {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0;  /* Bloques se apilan sin gap */
}

section[data-block-instance] {
    display: grid;
    grid-template-columns: 1fr;
    min-height: 100vh;  /* Depende del bloque */
    position: relative;
}
```

**Containers:**
```css
.okip-container {
    width: 100%;
    max-width: var(--okip-container);
    margin: 0 auto;
    padding: 0 var(--okip-container-pad);
}
```

**Responsive:**
```css
@media (max-width: 1024px) {
    .okip-container-pad { padding: 1rem; }
}

@media (max-width: 768px) {
    .okip-container-pad { padding: .75rem; }
}
```

## 5. Components CSS (components.css)

**Navbar:**
```css
.okip-navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: var(--okip-navbar-h);
    background-color: var(--okip-navbar-bg);
    backdrop-filter: blur(var(--okip-navbar-blur));
    border-bottom: 1px solid var(--okip-navbar-border);
    z-index: var(--okip-z-navbar);
    transition: opacity 0.3s ease;
}

.okip-navbar.is-hidden {
    opacity: 0;
    pointer-events: none;
}

.okip-navbar--start-hidden {
    opacity: 0;
    &.is-hidden { opacity: 0; }
    &:not(.is-hidden) { opacity: 1; }
}

.okip-navbar__menu a {
    color: var(--okip-navbar-text);
    text-decoration: none;
    position: relative;
    transition: color 0.3s ease;
}

.okip-navbar__menu a::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 0;
    width: 0;
    height: 2px;
    background-color: var(--okip-navbar-active);
    transition: width 0.3s ease;
}

.okip-navbar__menu .current-menu-item > a::after {
    width: 100%;
}
```

**Hamburguesa:**
```css
.okip-navbar__toggle {
    display: none;
    flex-direction: column;
    gap: 6px;
    background: none;
    border: none;
    cursor: pointer;
    
    @media (max-width: 1024px) {
        display: flex;
    }
}

.okip-navbar__toggle-bar {
    width: 24px;
    height: 2px;
    background-color: var(--okip-navbar-text);
    transition: all 0.3s ease;
}

.okip-navbar[data-okip-navbar][aria-expanded="true"] .okip-navbar__toggle-bar:first-child {
    transform: rotate(45deg) translate(10px, 10px);
}
```

## 6. Transitions CSS (transitions.css)

**Sistema Sticky-Cover:**
```css
[data-transition-mode="sticky-cover"] {
    position: sticky;
    top: 0;
    height: auto;
    max-height: none;
    overflow: visible;
    
    /* Reserva scroll para hold */
    padding-bottom: calc(var(--okip-hold-vh, 100) * 1vh);
}

[data-transition-mode="sticky-cover"] > .okip-cover-stage {
    position: relative;
    height: 100svh;
    /* Stage es lo que se ve; sticky está en padre */
}

@media (max-width: 1024px) {
    [data-transition-mode="sticky-cover"] {
        position: static;
        height: auto;
        padding-bottom: 0;
    }
    
    [data-transition-mode="sticky-cover"] > .okip-cover-stage {
        height: auto;
        min-height: 100svh;
    }
}
```

**Sistema Pin (ScrollTrigger):**
```css
[data-transition-mode="scrolltrigger-pin"] {
    /* ScrollTrigger maneja positioning via JavaScript */
}

[data-transition-mode="scrolltrigger-pin"].is-pinned {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 999;  /* Bajo navbar */
}
```

## 7. Animations CSS (animations.css)

**Keyframes Reusables:**
```css
@keyframes okip-fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes okip-slide-up {
    from { transform: translateY(24px); }
    to { transform: translateY(0); }
}

@keyframes okip-blur-clear {
    from { filter: blur(6px); }
    to { filter: blur(0px); }
}

@keyframes okip-scale-in {
    from { transform: scale(0.96); }
    to { transform: scale(1); }
}
```

**Clases de Estado:**
```css
.okip-motion-prepared {
    /* Estado inicial, sin animación */
}

.okip-hero--animated {
    /* Hero tiene animaciones activas */
}

.is-motion-entered-background {
    /* Background entry completada */
}

.is-motion-enabled {
    /* Playback activo */
}

.okip-motion-playback--variant-motion {
    /* Usando preset specific */
    /* Animación específica del preset */
}

.is-motion-exited-{target} {
    /* Exit completada */
}
```

**Fallback Sin GSAP:**
```css
html:not(.okip-js) {
    /* Sin JS, todo visible */
}

html.okip-js {
    /* Con JS, states pueden ocultar */
    
    .okip-hero__content {
        display: none;  /* Oculto hasta que entre */
    }
    
    .is-motion-entered-text .okip-hero__content {
        display: block;
    }
}
```

## 8. Estructura por Bloque

Cada bloque tiene `template-parts/blocks/{type}/style.css`:

```css
/* Hero Example */
.okip-hero {
    position: sticky;
    top: 0;
    height: 100svh;
    z-index: var(--okip-hero-z, 1);
    overflow: hidden;
}

.okip-hero__bg {
    position: absolute;
    inset: 0;
    z-index: 1;
}

.okip-hero__overlay {
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
}

.okip-hero__cards {
    position: absolute;
    inset: 0;
    z-index: 3;
}

.okip-hero__card {
    position: absolute;
    left: calc(var(--okip-card-x) * 1%);
    top: calc(var(--okip-card-y) * 1%);
    width: var(--okip-card-w);
    transform: translate(-50%, -50%);
}

.okip-hero__content {
    position: absolute;
    inset: 0;
    z-index: 4;
    display: flex;
    align-items: center;
    justify-content: center;
    max-width: var(--okip-hero-maxw);
}

.okip-hero__title {
    font-family: var(--okip-hero-title-font-family);
    font-size: var(--okip-hero-title-font-size);
    font-weight: var(--okip-hero-title-font-weight);
    line-height: var(--okip-hero-title-line-height);
    letter-spacing: var(--okip-hero-title-letter-spacing);
    color: var(--okip-hero-title-color);
}
```

## 9. Utilidades

```css
/* Visibilidad responsiva */
@media (max-width: 1024px) {
    .okip-desktop-only { display: none; }
}

@media (max-width: 768px) {
    .okip-tablet-only { display: none; }
}

/* Estados */
.is-hidden { opacity: 0; pointer-events: none; }
.is-visible { opacity: 1; pointer-events: auto; }
.is-scrolled { /* más opaco, por ejemplo */ }

/* Objetos Media */
.okip-aspect-video { aspect-ratio: 16 / 9; }
.okip-aspect-square { aspect-ratio: 1 / 1; }
```

## 10. Performance

**GPU Acceleration:**
```css
.okip-hero__card-motion {
    will-change: transform;  /* GPU acceleration */
    transform: translateZ(0);  /* 3D context */
}

.okip-hero__card-media {
    /* GSAP anima aquí, no en .okip-hero__card (que centea) */
}
```

**Contain:**
```css
[data-block-instance] {
    contain: layout style paint;  /* Layout confinement */
}
```

**Media Queries Breakpoints:**
```css
/* 768px = Mobile cutoff */
/* 1024px = Tablet/Desktop cutoff */
/* Navbar hamburguesa: ≤1024px */
/* ScrollTrigger: ≥1025px */
```

