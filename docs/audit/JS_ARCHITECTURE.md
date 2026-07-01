# OKIP Theme — Sistema JavaScript Completo

## 1. Carga y Orden de Dependencias

```
OKIP.ready(fn)          [app.js]
    ↓
okipGsap.ready          [gsap-init.js]
okipGsap.hasScrollTrigger
    ↓
OKIPAnimations.*        [animations.js]
    ↓
navbar.initNavbar()     [navbar.js]
    ↓
hero.initHero()         [hero/script.js]
```

## 2. app.js — Bootstrap Global

**Expone `window.OKIP`:**
```javascript
OKIP = {
    reduceMotion: boolean,      // prefers-reduced-motion
    ready(fn),                  // DOMContentLoaded wrapper
    breakpoints: {
        mobile: 768,
        tablet: 1024
    },
    toArray(nodeList),          // NodeList → Array
    clamp(value, min, max),     // Limita valor
    readInt(value, fallback),   // parseInt seguro
    readFloat(value, fallback), // parseFloat seguro
    rafThrottle(fn)             // Throttle por rAF
}
```

**Inicialización:**
```javascript
document.addEventListener('DOMContentLoaded', () => {
    OKIP.reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;
});
```

## 3. gsap-init.js — Estado de GSAP

**Expone `window.okipGsap`:**
```javascript
okipGsap = {
    ready: boolean,           // ¿GSAP está cargado?
    hasScrollTrigger: boolean // ¿ScrollTrigger registrado?
}
```

**Inicialización:**
```javascript
if (window.gsap && window.ScrollTrigger) {
    gsap.registerPlugin(ScrollTrigger);
    okipGsap.hasScrollTrigger = true;
} else {
    okipGsap.hasScrollTrigger = false;
}

okipGsap.ready = (window.gsap !== undefined);

// Pasa a JS desde PHP:
// window.OKIP_ENV.hasGsap (boolean)
// window.OKIP_ENV.hasScrollTrigger (boolean)
```

## 4. animations.js — Motor de Animaciones

**Factory:**
```javascript
const animator = OKIPAnimations.create(root, config);
```

**API Completa:**
```javascript
animator.prepare(targets)
    // Aplica estado "from" (oculto)
    // No anima

animator.enter(targets)
    // Anima entry (from → to)
    // Respeta reduce-motion

animator.playback(targets)
    // Inicia bucle continuo
    // Omitido si reduce-motion

animator.enterThenPlayback(targets)
    // Espera entry, luego playback
    // Timing coordinado

animator.exit(targets)
    // Anima salida (to → final)
    // Omitido si reduce-motion

animator.restore(targets)
    // Estado final sin animar

animator.watchExit(targets)
    // IO automático
    // Dispara exit al salir viewport
```

**Batch:**
```javascript
animator.prepareAll(targets[])
animator.enterAll(targets[])
animator.playbackAll(targets[])
animator.exitAll(targets[])
animator.finishAll(targets[])
```

**Parseo:**
```javascript
const config = OKIPAnimations.parseConfig(root);
// Lee <script data-okip-motion-config>

const animator = OKIPAnimations.create(root, config);
```

**Implementación:**
```javascript
function styleFor(stage, side) {
    // side = 'from' | 'to'
    // Retorna objeto de estilos (opacity, transform, filter)
}

function animateGsap(items, stageConfig, fromSide, toSide) {
    // Usa GSAP timeline
    // Si okipGsap.ready = true
}

function animateCss(items, stageConfig, side) {
    // CSS transitions fallback
    // Si okipGsap.ready = false
}
```

## 5. navbar.js — Visibilidad del Navbar

**Inicialización:**
```javascript
OKIP.ready(() => {
    const navbar = document.querySelector('[data-okip-navbar]');
    if (!navbar) return;
    
    initNavbar(navbar);
});
```

**Modos de Revelación:**

**after_hero:**
```javascript
const hero = document.querySelector('[data-okip-hero]');
const nextBlock = hero.nextElementSibling;

// Detectar cobertura: nextBlock cubre ~85% del Hero
const coverageThreshold = 0.15;  // 15% top visible = 85% cubierto

window.addEventListener('scroll', () => {
    const rect = nextBlock.getBoundingClientRect();
    if (rect.top < window.innerHeight * coverageThreshold) {
        navbar.classList.remove('is-hidden');
    } else {
        navbar.classList.add('is-hidden');
    }
});
```

**always:**
```javascript
// Navbar siempre visible
```

**manual:**
```javascript
// Requiere control manual (admin decide)
```

**Hamburguesa:**
```javascript
const toggle = navbar.querySelector('[data-okip-nav-toggle]');
const menu = navbar.querySelector('[data-okip-nav]');

toggle.addEventListener('click', () => {
    const isOpen = toggle.ariaExpanded === 'true';
    toggle.ariaExpanded = !isOpen;
    menu.classList.toggle('is-open');
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        toggle.ariaExpanded = 'false';
        menu.classList.remove('is-open');
    }
});
```

**Scroll Highlight:**
```javascript
window.addEventListener('scroll', () => {
    if (window.scrollY > 8) {
        navbar.classList.add('is-scrolled');
    } else {
        navbar.classList.remove('is-scrolled');
    }
});
```

## 6. hero/script.js — Script del Bloque Hero

**Inicialización:**
```javascript
OKIP.ready(() => {
    const hero = document.querySelector('[data-okip-hero]');
    if (!hero) return;
    
    initHero(hero);
});
```

**Parsed Config:**
```javascript
const configScript = hero.querySelector('[data-okip-motion-config]');
const config = JSON.parse(configScript.textContent);
const animator = OKIPAnimations.create(hero, config);
```

**Secuencia de Entrada:**
```javascript
// 1. Prepare (estado oculto)
animator.prepare(hero.querySelectorAll('[data-okip-motion-target]'));

// 2. Inicia intro video
const intro = hero.querySelector('[data-okip-hero-intro]');
if (intro && okip_media_exists(intro.src)) {
    intro.play();
    intro.addEventListener('ended', () => startLoop(animator, hero));
} else {
    // Fallback: saltar directamente a loop
    setTimeout(() => startLoop(animator, hero), hero.dataset.introFail || 2500);
}

// 3. Schedule content entry
setTimeout(() => {
    animator.enter(
        hero.querySelectorAll('[data-okip-motion-target="background"]')
    );
    
    setTimeout(() => {
        animator.enter(
            hero.querySelectorAll('[data-okip-motion-target="text"]')
        );
        animator.playback(
            hero.querySelectorAll('[data-okip-motion-target="background"]')
        );
        
        setTimeout(() => {
            animator.enterThenPlayback(
                hero.querySelectorAll('[data-okip-motion-target="cards"]')
            );
            animator.watchExit(
                hero.querySelectorAll('[data-okip-motion-target]')
            );
        }, 300);
    }, 600);
}, hero.dataset.contentEntryDelay || 900);
```

**Transición Intro → Loop:**
```javascript
function startLoop(animator, hero) {
    const intro = hero.querySelector('[data-okip-hero-intro]');
    const loop = hero.querySelector('[data-okip-hero-loop]');
    
    // Crossfade opcional
    if (hero.dataset.crossfade === '1') {
        const duration = hero.dataset.crossfadeMs || 700;
        gsap.to(intro, {
            opacity: 0,
            duration: duration / 1000,
            onComplete: () => {
                intro.pause();
                intro.classList.add('is-intro-hidden');
                loop.classList.add('is-loop-visible');
                if (loop && !loop.paused) loop.play();
            }
        });
    } else {
        intro.classList.add('is-intro-hidden');
        loop.classList.add('is-loop-visible');
    }
}
```

**Interactividad de Tarjetas:**
```javascript
function setupCards(hero) {
    const cards = hero.querySelectorAll('[data-okip-hero-card]');
    
    cards.forEach(card => {
        const video = card.querySelector('video');
        const playMode = card.dataset.playMode || 'hover';
        const duration = parseInt(card.dataset.playDurationMs) || 3500;
        
        if (video && playMode === 'hover') {
            card.addEventListener('mouseenter', () => {
                video.currentTime = 0;
                video.play();
                
                setTimeout(() => {
                    video.pause();
                }, duration);
            });
        }
    });
}
```

**Autoplay de Tarjetas:**
```javascript
function setupCardsAutoplay(hero, players) {
    if (hero.dataset.cardsAutoplay !== '1') return;
    
    const minDelay = parseInt(hero.dataset.cardsAutoplayMin) || 2500;
    const maxDelay = parseInt(hero.dataset.cardsAutoplayMax) || 6500;
    const startDelay = parseInt(hero.dataset.cardsAutoplayStart) || 1200;
    
    setTimeout(() => {
        setInterval(() => {
            if (OKIP.reduceMotion) return;
            if (!isHeroVisible(hero)) return;
            
            const randomCard = pickRandomCard(players);
            playCard(randomCard);
            
            setTimeout(() => stopCard(randomCard), 
                      parseInt(randomCard.dataset.playDurationMs) || 3500);
        }, minDelay + Math.random() * (maxDelay - minDelay));
    }, startDelay);
}
```

**Pausa al Cubrir:**
```javascript
function setupCoverPause(hero) {
    const nextBlock = hero.nextElementSibling;
    
    window.addEventListener('scroll', () => {
        if (!nextBlock) {
            hero.classList.remove('is-hero-paused');
            return;
        }
        
        const rect = nextBlock.getBoundingClientRect();
        if (rect.top < window.innerHeight * 0.15) {  // 85% cubierto
            hero.classList.add('is-hero-paused');
        } else {
            hero.classList.remove('is-hero-paused');
        }
    });
}
```

## 7. Bloques Adicionales

Cada bloque sigue el patrón:

```javascript
// template-parts/blocks/{type}/script.js
OKIP.ready(() => {
    const block = document.querySelector('[data-block-instance="{id}"]');
    if (!block) return;
    
    // Parse config
    const configScript = block.querySelector('[data-okip-motion-config]');
    const config = configScript ? JSON.parse(configScript.textContent) : {};
    
    // Create animator
    const animator = OKIPAnimations.create(block, config);
    
    // Setup interactividad específica del bloque
    setupBlockInteractivity(block, animator);
});
```

## 8. Patrón de Scope (Por Instancia)

```javascript
// Usar data-block-instance como selector único
const block = document.querySelector(
    '[data-block-instance="home-hero-main"]'
);

// O buscar dentro de un contenedor
const hero = rootElement.querySelector('[data-okip-hero]');
const cards = hero.querySelectorAll('[data-okip-hero-card]');

// Evitar selectores globales
// ❌ document.querySelectorAll('video')  // Puede coger videos de otros bloques
// ✅ hero.querySelectorAll('video')      // Solo videos dentro del hero
```

## 9. Utilidades Compartidas

```javascript
// OKIP.ready(fn)
OKIP.ready(() => {
    // Ejecuta cuando DOM está listo
});

// OKIP.clamp(value, min, max)
const z = OKIP.clamp(order + 1, 1, 1000);

// OKIP.readInt(value, fallback)
const ms = OKIP.readInt(element.dataset.duration, 700);

// OKIP.rafThrottle(fn)
const handleScroll = OKIP.rafThrottle(() => {
    // Ejecuta máximo una vez por frame
});
window.addEventListener('scroll', handleScroll);

// OKIP.toArray(nodeList)
const arr = OKIP.toArray(document.querySelectorAll('[data-okip]'));
```

## 10. Manejo de Errores

```javascript
// Verificar elemento existe
const hero = document.querySelector('[data-okip-hero]');
if (!hero) {
    console.warn('[OKIP] Hero no encontrado');
    return;
}

// Verificar config JSON válido
try {
    const config = JSON.parse(configScript.textContent);
} catch (e) {
    console.error('[OKIP] Config JSON inválido:', e);
    return;
}

// Verificar GSAP disponible para operaciones complejas
if (okipGsap.ready) {
    // GSAP animation
} else {
    // CSS fallback
}

// Verificar reduce-motion
if (OKIP.reduceMotion) {
    // Sin animaciones
}
```

## 11. Performance Tips

**Evitar**:
```javascript
// ❌ Animar directamente (causa reflows)
card.style.transform = 'translateX(10px)';  // Reflow cada 16ms

// ✅ Usar GSAP (batch optimizador)
gsap.to(card, { x: 10 });  // GSAP batch transforma

// ❌ Listeners sin throttle
window.addEventListener('scroll', updateUI);  // Cientos de veces por segundo

// ✅ Con throttle
window.addEventListener('scroll', OKIP.rafThrottle(updateUI));  // Max 1 por frame
```

**Optimizaciones**:
```javascript
// Cache selectores
const cards = hero.querySelectorAll('[data-okip-hero-card]');
cards.forEach(card => {  // Reutiliza NodeList
    // ...
});

// Usar will-change (CSS)
card.style.willChange = 'transform';  // GPU acceleration

// Event delegation
document.addEventListener('click', (e) => {
    const card = e.target.closest('[data-okip-hero-card]');
    if (card) handleCardClick(card);
});
```

