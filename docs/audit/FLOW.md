# OKIP Theme — Ciclo de Vida Completo

## Desde la petición HTTP hasta la animación

### 1. PETICIÓN HTTP

```
User en navegador
  ↓
GET http://localhost:8080/ (o URL de página)
  ↓
WordPress Router (WordPress native)
```

---

## 2. WORDPRESS ROUTING

```
¿Es front-page?
  ├─ SÍ → front-page.php
  ├─ NO: ¿Es página custom?
  │   └─ SÍ → page.php
  └─ NO: ¿Es post único?
      └─ SÍ → single.php
      └─ NO → index.php / 404.php
```

### Para HOME (front-page.php)

```php
<?php
get_header();                                    // → header.php
okip_render_page(okip_get_page_blocks('home'));  // ← MOTOR DE BLOQUES
get_footer();                                    // → footer.php
?>
```

---

## 3. HEADER.PHP (SHELL)

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <?php wp_head(); ?>  ← PUNTO DE INYECCIÓN DE CSS/JS
    
    <!-- Aquí se incluyen: -->
    <!-- 1. tokens.css -->
    <!-- 2. base.css -->
    <!-- 3. layout.css -->
    <!-- 4. components.css -->
    <!-- 5. transitions.css -->
    <!-- 6. animations.css -->
    <!-- 7. Google Fonts URL (si usados) -->
    <!-- 8. app.js -->
    <!-- 9. gsap-init.js + GSAP/ScrollTrigger si existen -->
    <!-- 10. animations.js -->
    <!-- 11. navbar.js -->
</head>

<body class="okip-body">
    <?php wp_body_open(); ?>
    
    <script>
        document.documentElement.classList.add('okip-js');  ← MITIGA FLASH
    </script>
    
    <a href="#okip-content" class="okip-skip-link">
        Saltar al contenido
    </a>
    
    <?php get_template_part('template-parts/layout/navbar'); ?>  ← NAVBAR GLOBAL
    
    <main id="okip-content" class="okip-main">
    <!-- Aquí va el contenido del bloque (front-page.php / page.php) -->
```

---

## 4. NAVBAR GLOBAL (template-parts/layout/navbar.php)

### 4.1 Renderización (Server-side)

```php
$okip_nav = okip_navbar_config();  // ← Lee config/blocks/navbar.php

// Server decide si el navbar debe estar oculto al inicio
$okip_start_hidden = is_front_page()
    && in_array('hero', okip_used_block_types(okip_get_page_blocks('home')))
    && $okip_reveal['reveal_mode'] === 'after_hero'
    && $okip_reveal['hide_on_hero'];

// Imprime:
<header class="okip-navbar okip-navbar--start-hidden"  ← Si está en home con hero
        data-okip-navbar
        data-reveal-mode="after_hero"
        data-reveal-offset="0"
        data-hide-on-hero="1"
        style="--okip-navbar-bg: rgba(...); --okip-navbar-blur: 14px; ...">
    
    <!-- Logo, menú, hamburguesa -->
</header>
```

### 4.2 Inicialización (Client-side)

```javascript
// En navbar.js:
document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.querySelector('[data-okip-navbar]');
    if (!navbar) return;
    
    // Detectar modo reveal
    const revealMode = navbar.dataset.revealMode; // 'after_hero'
    
    if (revealMode === 'after_hero') {
        // Buscar bloque siguiente al Hero
        const hero = document.querySelector('[data-okip-hero]');
        if (hero) {
            const nextBlock = hero.nextElementSibling;
            if (nextBlock && nextBlock.dataset.blockInstance) {
                // Monitorear geometría del nextBlock
                // Cuando ~85% cubierto (scrollY), mostrar navbar
                navbar.classList.remove('okip-navbar--start-hidden');
                navbar.classList.add('is-hidden'); // Oculto, no visualmente
                
                observeBlockCoverage(nextBlock, navbar);
            }
        }
    }
    
    // Toggle hamburguesa
    const toggle = navbar.querySelector('[data-okip-nav-toggle]');
    toggle.addEventListener('click', () => {
        const menu = navbar.querySelector('[data-okip-nav]');
        const isOpen = toggle.ariaExpanded === 'true';
        toggle.ariaExpanded = !isOpen;
        // Aplica clase is-open
    });
});
```

---

## 5. MOTOR DE BLOQUES (okip_render_page)

### 5.1 Obtención de Bloques

```php
// front-page.php llamó:
okip_render_page(okip_get_page_blocks('home'));

// okip_get_page_blocks('home') hace:
$blocks = include 'config/pages/home.php';  // Array de instancias

// Aplica filtro 'okip_page_blocks':
apply_filters('okip_page_blocks', $blocks, 'home')
    ├─ [Prio 10] okip_apply_page_block_order()
    │   // Lee wp_options[okip_page_blocks_order_home]
    │   // Reordena $blocks según ese orden
    │
    └─ [Prio 20] okip_apply_page_block_overrides()
        // Lee wp_options[okip_page_blocks_overrides_home]
        // Mezcla diffs en data de cada instancia

return $blocks;  // Lista ordenada, con overrides aplicados
```

### 5.2 Renderización de Bloques

```php
// okip_render_page($blocks):
foreach ($blocks as $order => $block) {  // $order es el índice (0, 1, 2, ...)
    
    $type = $block['type'];                        // 'hero'
    $instance_id = $block['instance_id'];          // 'home-hero-main'
    $data = $block['data'] ?? [];                  // { content, background, ... }
    
    okip_render_block($type, $instance_id, $data, $order);
}

// okip_render_block($type, $instance_id, $data, $order):
{
    // 1. VALIDAR WHITELIST
    if (!okip_is_allowed_block($type)) return;
    
    // 2. VERIFICAR TEMPLATE EXISTE
    $template = 'template-parts/blocks/' . $type . '/block.php';
    if (!is_readable($template)) return;
    
    // 3. NORMALIZAR DATA
    $data = okip_normalize_block_data($type, $data);
        // Merge con defaults
        // Llamar okip_normalize_{type}_data() si existe
    
    // 4. ENCOLAR ASSETS (condicional, primera vez por tipo)
    // Esto sucede en okip_enqueue_assets(), que mira okip_used_block_types()
    
    // 5. RENDERIZAR
    get_template_part(
        'template-parts/blocks/' . $type . '/block',
        null,
        [
            'type'        => $type,
            'instance_id' => $instance_id,
            'data'        => $data,
            'order'       => $order,  ← Usado para z-index dinámico
        ]
    );
}
```

---

## 6. TEMPLATE DE BLOQUE EJEMPLO: HERO

### 6.1 Variables Disponibles en block.php

```php
<?php
// template-parts/blocks/hero/block.php recibe en $args:
$okip_instance = $args['instance_id'];  // 'home-hero-main'
$okip_data     = $args['data'];         // Array normalizado
$okip_order    = $args['order'];        // 0 (primera posición)

// Extrae grupos:
$content    = $okip_data['content'];
$background = $okip_data['background'];
$cards      = $okip_data['cards'];
$motion     = $okip_data['motion'];
$animation  = $okip_data['animation'];
// etc.
```

### 6.2 Resolución de Medios (Media-driven)

```php
// En block.php, resuelve cada medio:
$intro_on = okip_media_exists($intro_src);  // ¿Existe el video intro?
$intro_url = $intro_on ? okip_media_url($intro_src) : '';

// Si no existe media:
// - No renderiza <video>
// - Aplica fallback neutral (CSS color sólido)
// - Nunca un media falso (placeholder gráfico)
```

### 6.3 Renderización HTML

```html
<section id="home-hero-main"
         class="okip-hero okip-hero--animated is-hero-entering"
         data-block-instance="home-hero-main"
         data-okip-hero
         data-bg-type="video"
         data-motion-enabled="1"
         data-intro-fail="2500"
         data-crossfade="1"
         data-crossfade-ms="700"
         data-cards-autoplay="1"
         style="--okip-hero-z: 1; --okip-hero-xfade: 700ms; --okip-hero-title-font-family: ...; ...">
    
    <!-- 1. JSON CONFIG PARA JS -->
    <script type="application/json" data-okip-motion-config>
        {
            "motion": {
                "enabled": true,
                "background": { "entry": {...}, "playback": {...}, "exit": {...} },
                "text": { "entry": {...}, "exit": {...} },
                "cards": { "entry": {...}, "playback": {...}, "exit": {...} }
            },
            "selectors": {
                "background": "[data-okip-motion-target=\"background\"]",
                "text": "[data-okip-motion-target=\"text\"]",
                "cards": "[data-okip-motion-target=\"cards\"]"
            }
        }
    </script>
    
    <!-- 2. FONDO MEDIA -->
    <div class="okip-hero__bg okip-hero__bg--video" data-okip-motion-target="background">
        <video class="okip-hero__media okip-hero__media--intro" data-okip-hero-intro
               muted playsinline preload="auto">
            <source src="/wp-content/themes/okip-theme/assets/video/hero/intro.mp4">
        </video>
        <video class="okip-hero__media okip-hero__media--loop" data-okip-hero-loop
               autoplay muted loop playsinline preload="auto">
            <source src="/wp-content/themes/okip-theme/assets/video/hero/loop.mp4">
        </video>
    </div>
    
    <!-- 3. OVERLAY OPCIONAL -->
    <div class="okip-hero__overlay" style="background-color: #020711; opacity: 0.18;"></div>
    
    <!-- 4. TARJETAS FLOTANTES -->
    <div class="okip-hero__cards" data-okip-hero-cards>
        <div class="okip-hero__card okip-hero__card--glow okip-hero__card--gif"
             data-okip-hero-card
             data-card-id="hero-carro"
             data-play-mode="hover"
             data-play-duration-ms="3320"
             style="--okip-card-x: 19%; --okip-card-y: 24%; --okip-card-w: 23vw;">
            
            <div class="okip-hero__card-motion" data-okip-motion-target="cards">
                <div class="okip-hero__card-media">
                    <img class="okip-hero__card-gif"
                         data-gif-src="/wp-content/themes/okip-theme/assets/gif/hero/carro.gif"
                         alt="Animación de reportes vehiculares"
                         decoding="async">
                </div>
            </div>
        </div>
        <!-- Más tarjetas... -->
    </div>
    
    <!-- 5. CONTENIDO CENTRAL -->
    <div class="okip-hero__content okip-hero__content--center" style="--okip-hero-maxw: 1000px;">
        <h1 class="okip-hero__title">
            <span class="okip-hero__title-line okip-hero__title-line--primary" data-okip-motion-target="text">
                Inteligencia mexicana
            </span>
            <span class="okip-hero__title-line okip-hero__title-line--secondary" data-okip-motion-target="text">
                al servicio de la humanidad
            </span>
        </h1>
        <div class="okip-hero__logo" data-okip-motion-target="text">
            <img src="/wp-content/themes/okip-theme/assets/img/okip-logo-hero.png" alt="OKIP Logo">
        </div>
    </div>
</section>
```

---

## 7. ENQUEUE DE ASSETS

### 7.1 Punto de Inyección

```php
// inc/enqueue.php → okip_enqueue_assets()
// Se dispara al hook: add_action('wp_enqueue_scripts', 'okip_enqueue_assets');
// Ejecuta cuando: wp_enqueue_scripts (durante wp_head())

function okip_enqueue_assets() {
    $slug = okip_current_page_slug();  // 'home'
    $blocks = okip_get_page_blocks($slug);  // Array de instancias
    
    // 1. ENQUEUE CSS GLOBAL
    wp_enqueue_style('okip-tokens', ...);  // tokens.css
    wp_enqueue_style('okip-base', ..., ['okip-tokens']);  // base.css
    // ... más en cascada ...
    
    // 2. GOOGLE FONTS DINÁMICAS
    $fonts = okip_collect_page_google_fonts($blocks);  // { 'Montserrat' => [300, 600], 'Inter' => [400, 500] }
    $fonts_url = okip_google_fonts_url($fonts);  // URL única Google Fonts CSS2
    if ($fonts_url) {
        wp_enqueue_style('okip-google-fonts', $fonts_url);
    }
    
    // 3. GSAP CONDICIONAL
    if (okip_has_gsap()) {
        wp_enqueue_script('gsap', 'assets/vendor/gsap/gsap.min.js');
        
        if (okip_has_scrolltrigger()) {
            wp_enqueue_script('gsap-scrolltrigger', 'assets/vendor/gsap/ScrollTrigger.min.js', ['gsap']);
        }
    }
    
    // 4. JS GLOBAL
    wp_enqueue_script('okip-app', 'assets/js/app.js');
    wp_enqueue_script('okip-gsap-init', 'assets/js/gsap-init.js', ['okip-app', 'gsap']);
    wp_enqueue_script('okip-animations', 'assets/js/animations.js', ['okip-gsap-init']);
    wp_enqueue_script('okip-navbar', 'assets/js/navbar.js', ['okip-animations']);
    
    // 5. LOCALIZAR VARIABLES PHP → JS
    wp_localize_script('okip-gsap-init', 'OKIP_ENV', [
        'hasGsap' => okip_has_gsap(),
        'hasScrollTrigger' => okip_has_scrolltrigger(),
    ]);
    
    // 6. ASSETS POR BLOQUE (condicional)
    okip_enqueue_block_assets(okip_used_block_types($blocks));
    // Para cada tipo en $blocks:
    //   - Encola template-parts/blocks/{type}/style.css (si existe)
    //   - Encola template-parts/blocks/{type}/script.js (si existe)
}
```

### 7.2 Orden Final de Carga (en `<head>`)

```html
<!-- 1. Meta, canonical, favicon (WP native) -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- 2. CSS Global en cascada -->
<link rel="stylesheet" href=".../tokens.css">
<link rel="stylesheet" href=".../base.css">
<link rel="stylesheet" href=".../layout.css">
<link rel="stylesheet" href=".../components.css">
<link rel="stylesheet" href=".../transitions.css">
<link rel="stylesheet" href=".../animations.css">

<!-- 3. Google Fonts dinámico -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Montserrat:wght@300;600&display=swap">

<!-- 4. CSS por bloque -->
<link rel="stylesheet" href=".../blocks/hero/style.css">

<!-- 5. Inyección de clase okip-js ANTES de </head> -->
<script>
    document.documentElement.classList.add('okip-js');
</script>

<!-- 6. JS Global + GSAP -->
<script src=".../app.js"></script>
<script src=".../vendor/gsap/gsap.min.js"></script>
<script src=".../vendor/gsap/ScrollTrigger.min.js"></script>
<script src=".../gsap-init.js"></script>
<script>
    var OKIP_ENV = {"hasGsap":true,"hasScrollTrigger":true};
</script>
<script src=".../animations.js"></script>
<script src=".../navbar.js"></script>

<!-- 7. JS por bloque -->
<script src=".../blocks/hero/script.js"></script>
```

---

## 8. EJECUCIÓN DE JAVASCRIPT

### 8.1 Secuencia de Inicialización

```
1. app.js carga
   └─ Define window.OKIP
      ├─ OKIP.reduceMotion = window.matchMedia('prefers-reduced-motion').matches
      ├─ OKIP.ready(fn) = ejecuta fn cuando DOMContentLoaded
      └─ OKIP.breakpoints, clamp(), readInt(), rafThrottle()

2. gsap-init.js carga
   └─ Define window.okipGsap
      ├─ gsap.registerPlugin(ScrollTrigger) si existen ambos
      └─ okipGsap.ready = (window.gsap !== undefined)
         okipGsap.hasScrollTrigger = (window.ScrollTrigger !== undefined)

3. animations.js carga
   └─ Define window.OKIPAnimations (factory)
      └─ create(root, config) → retorna animator
      └─ Métodos: prepare(), enter(), playback(), exit(), watchExit()

4. navbar.js carga
   └─ OKIP.ready(() => { initNavbar(); })

5. Bloques individuales cargan (hero/script.js, etc.)
   └─ OKIP.ready(() => { initHero(hero); })
      └─ Si motion.enabled → OKIPAnimations.create(hero, config)
```

### 8.2 Punto de Entrada del Usuario

```javascript
// DOMContentLoaded
OKIP.ready(() => {
    // navbar.js
    const navbar = document.querySelector('[data-okip-navbar]');
    
    // hero/script.js
    const hero = document.querySelector('[data-okip-hero]');
    if (hero) {
        const config = JSON.parse(
            hero.querySelector('[data-okip-motion-config]').textContent
        );
        const animator = OKIPAnimations.create(hero, config);
        
        // Inicia secuencia
        animator.prepare(document.querySelectorAll('[data-okip-motion-target]'));
        
        // Espera video intro
        const intro = hero.querySelector('[data-okip-hero-intro]');
        if (intro && intro.paused) {
            intro.play();
        }
    }
});
```

---

## 9. ANIMACIONES (HERO COMO EJEMPLO)

### 9.1 Fase Entry (Entrada)

```javascript
// Cuando hero entra en viewport:

animator.enter(backgrounds);  // Anima background
// FROM: opacity 0.18, y 8px, scale 1.026, blur 6px
// TO: opacity 1, y 0, scale 1, blur 0px
// Duration: 1180ms, ease: power3.out

setTimeout(() => {
    animator.enter(texts);  // Anima texto con delay + stagger
    // FROM: y 28px, opacity 0
    // TO: y 0, opacity 1
    // Duration: 700ms, delay: 600ms, stagger: 120ms
}, 300);

setTimeout(() => {
    animator.playback(backgrounds);  // Inicia playback de fondo
    animator.enter(cards);  // Anima tarjetas
}, 300 + 700 + 120*numCards);
```

### 9.2 Fase Playback (Continuo)

```javascript
// Mientras hero está visible:

animator.playback(backgrounds);
// Preset: 'variant-motion' (movimiento líquido)
// Duration: 18000ms (ciclo)
// Intensity: 0.34 (magnitud)
// Speed: 0.82 (multiplicador duración)
// Direction: 'alternate' (de ida y vuelta)
// Yoyo: true (invierte)

animator.playback(cards);
// Preset: 'float-soft' (flotación suave)
// Duration: 4200ms
// Y: -10px a 10px
// Scale: 1 a 1.02
```

### 9.3 Fase Exit (Salida)

```javascript
// Cuando hero sale del viewport:

animator.exit(backgrounds);
// FROM: opacity 1, y 0, scale 1, blur 0
// TO: opacity 0.35, y -30px, scale 0.96, blur 8px
// Duration: 700ms, ease: power2.out

animator.exit(texts);
// FROM: opacity 1, y 0
// TO: opacity 0, y -18px
// Duration: 500ms

animator.exit(cards);
// FROM: opacity 1, y 0, scale 1
// TO: opacity 0, y 20px, scale 0.96
// Duration: 500ms
```

---

## 10. FOOTER.PHP

```html
    </main><!-- #okip-content -->
    
    <?php get_template_part('template-parts/layout/footer-site'); ?>
    
    <?php wp_footer(); ?>  ← Punto de inyección scripts finales
    
</body>
</html>
```

---

## 11. DESPUÉS: INTERACTIVIDAD USUARIO

### Ejemplo: Hover en Tarjeta de Hero

```javascript
// hero/script.js → setupCards()

card.addEventListener('mouseenter', () => {
    if (playMode !== 'hover') return;
    
    const video = card.querySelector('video');
    if (video) {
        video.currentTime = 0;
        video.play();  ← INICIA REPRODUCCIÓN
        
        // Limpia GIF si está activo
        stopGif();
    }
});

card.addEventListener('mouseleave', () => {
    if (resetOnLeave) {
        video.pause();
        video.currentTime = 0;
    }
});
```

### Ejemplo: Autoplay de Tarjetas

```javascript
// setupCardsAutoplay()

setInterval(() => {
    if (isHeroVisible && !isHovered && !isReduceMotion) {
        const randomCard = pickRandomCard();
        playCard(randomCard);  ← INICIA AUTOPLAY
        
        setTimeout(() => {
            stopCard(randomCard);
        }, playDurationMs);
    }
}, delayMs + Math.random() * (maxDelayMs - minDelayMs));
```

### Ejemplo: Scroll Navbar

```javascript
// navbar.js

window.addEventListener('scroll', () => {
    const navbar = document.querySelector('[data-okip-navbar]');
    
    if (window.scrollY > 8) {
        navbar.classList.add('is-scrolled');  ← BGCOLOR MÁS OPACO
    } else {
        navbar.classList.remove('is-scrolled');
    }
    
    // Detectar cobertura de Hero
    if (heroVisible && nextBlockCoveringHero > 0.85) {
        navbar.classList.remove('is-hidden');  ← MOSTRAR NAVBAR
    }
});
```

---

## Resumen del Flujo Completo

```
HTTP GET /
  ↓
WordPress Router → front-page.php
  ↓
header.php (CSS global + app.js + GSAP + animations.js + navbar.js + Google Fonts)
  ↓
okip_render_page(okip_get_page_blocks('home'))
  ├─ okip_render_block('hero', 'home-hero-main', $data, 0)
  │   └─ template-parts/blocks/hero/block.php
  │       └─ HTML con data-attributes + JSON config + <script> motion config
  │
  ├─ okip_render_block('video-w-title', 'home-video-w-title', $data, 1)
  ├─ okip_render_block('industry-carousel', 'home-industry-carousel', $data, 2)
  ├─ okip_render_block('product-story', 'home-product-story', $data, 3)
  ├─ okip_render_block('mission-statement', 'home-mission-statement', $data, 4)
  └─ okip_render_block('news', 'home-news', $data, 5)
  ↓
footer.php (wp_footer() → hero/script.js + other blocks' script.js)
  ↓
DOMContentLoaded
  ├─ navbar.js → initNavbar()
  ├─ hero/script.js → initHero()
  │   ├─ OKIPAnimations.create(hero, config)
  │   ├─ Prepara backgrounds + texts + cards
  │   ├─ Inicia intro video
  │   ├─ setups hover interactivity
  │   └─ setups autoplay
  │
  ├─ industry-carousel/script.js → initCarousel()
  └─ Otros bloques...
  ↓
User Interaction (hover, click, scroll)
  ├─ navbar: mostrar/ocultar por scroll + cobertura Hero
  ├─ hero: play card en hover, autoplay al azar
  ├─ carousel: reorder tarjetas en horizontal-scroll
  └─ Otros: según lógica de cada bloque
  ↓
ANIMACIONES CORREN VISUALMENTE
  ├─ GSAP timeline si okipGsap.ready = true
  └─ CSS transitions si okipGsap.ready = false (fallback)
```

