# GSAP (local)

Coloca aquí los archivos **reales** de GSAP (NO se usa CDN):

- `gsap.min.js`
- `ScrollTrigger.min.js`

Descarga desde https://gsap.com/ (o `npm i gsap` y copia desde
`node_modules/gsap/dist/`):

```
node_modules/gsap/dist/gsap.min.js          → assets/vendor/gsap/gsap.min.js
node_modules/gsap/dist/ScrollTrigger.min.js → assets/vendor/gsap/ScrollTrigger.min.js
```

## Comportamiento sin estos archivos

El tema **no se rompe** si no están:

- `inc/enqueue.php` comprueba `file_exists()` y solo encola GSAP/ScrollTrigger
  si los `.min.js` existen realmente.
- `assets/js/gsap-init.js` registra ScrollTrigger solo si `window.gsap` existe.
- Cada bloque (p.ej. el Hero) tiene **fallback CSS** cuando GSAP no está
  disponible.

En cuanto agregues los dos archivos aquí, las animaciones GSAP se activan solas
(cache-busting por `filemtime`).
