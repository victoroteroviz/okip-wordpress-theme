(function () {
    'use strict';

    var catalog = (window.OKIP_ADMIN && Array.isArray(window.OKIP_ADMIN.fonts)) ? window.OKIP_ADMIN.fonts : [];
    var previewLinks = {};

    function fontName(font) {
        return font && (font.family || font.label) ? String(font.family || font.label) : '';
    }

    function cleanFamily(value) {
        return String(value || '').replace(/["'\\]/g, '').replace(/[^A-Za-z0-9 _-]/g, '').replace(/\s+/g, ' ').trim();
    }

    function googleFontsUrl(family) {
        family = cleanFamily(family);
        if (!family || family.toLowerCase() === 'system') { return ''; }
        return 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(family).replace(/%20/g, '+') + ':wght@300;400;500;600;700;800&display=swap';
    }

    function ensurePreviewFont(family) {
        family = cleanFamily(family);
        if (!family || family.toLowerCase() === 'system' || previewLinks[family]) { return; }
        var href = googleFontsUrl(family);
        if (!href) { return; }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        document.head.appendChild(link);
        previewLinks[family] = true;
    }

    function updatePreview(input) {
        var family = cleanFamily(input.value);
        var preview = document.getElementById(input.getAttribute('data-preview'));
        if (!preview) { return; }
        ensurePreviewFont(family);
        preview.style.fontFamily = family && family.toLowerCase() !== 'system'
            ? '"' + family + '", system-ui, sans-serif'
            : 'system-ui, sans-serif';
    }

    function renderFontResults(input) {
        var holder = input.parentNode.querySelector('[data-font-results]');
        if (!holder) { return; }
        var q = cleanFamily(input.value).toLowerCase();
        holder.innerHTML = '';
        if (q.length < 2) {
            holder.hidden = true;
            return;
        }

        var matches = catalog.filter(function (font) {
            return fontName(font).toLowerCase().indexOf(q) !== -1;
        }).slice(0, 8);

        if (!matches.length) {
            holder.hidden = true;
            return;
        }

        matches.forEach(function (font) {
            var family = fontName(font);
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'okip-admin-font-result';
            button.textContent = family;
            button.addEventListener('click', function () {
                input.value = family;
                holder.hidden = true;
                updatePreview(input);
            });
            holder.appendChild(button);
        });
        holder.hidden = false;
    }

    function initFonts() {
        document.querySelectorAll('.okip-admin-font-search').forEach(function (input) {
            var timer = null;
            input.addEventListener('input', function () {
                window.clearTimeout(timer);
                timer = window.setTimeout(function () {
                    renderFontResults(input);
                    updatePreview(input);
                }, 120);
            });
            input.addEventListener('focus', function () {
                renderFontResults(input);
                updatePreview(input);
            });
            updatePreview(input);
        });
        document.addEventListener('click', function (event) {
            if (event.target.closest('.okip-admin-font-search') || event.target.closest('.okip-admin-font-results')) {
                return;
            }
            document.querySelectorAll('.okip-admin-font-results').forEach(function (holder) {
                holder.hidden = true;
            });
        });
    }

    function initMediaFields() {
        document.addEventListener('click', function (event) {
            var select = event.target.closest('[data-okip-media-select]');
            var clear = event.target.closest('[data-okip-media-clear]');

            if (clear) {
                var clearWrap = clear.closest('.okip-admin-media-field');
                var clearInput = clearWrap ? clearWrap.querySelector('[data-okip-media-value]') : null;
                if (clearInput) { clearInput.value = ''; }
                return;
            }

            if (!select || !window.wp || !window.wp.media) { return; }

            var wrap = select.closest('.okip-admin-media-field');
            var input = wrap ? wrap.querySelector('[data-okip-media-value]') : null;
            if (!input) { return; }

            var frame = window.wp.media({
                title: 'Seleccionar medio',
                button: { text: 'Usar este medio' },
                multiple: false
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                input.value = attachment.id || attachment.url || '';
            });

            frame.open();
        });
    }

    function initBlockTabs() {
        document.querySelectorAll('[data-okip-tabs]').forEach(function (group) {
            var buttons = Array.prototype.slice.call(group.querySelectorAll('.okip-admin-tab-btn')).filter(function (button) {
                return button.closest('[data-okip-tabs]') === group;
            });
            var panels = Array.prototype.slice.call(group.querySelectorAll('.okip-admin-tab-panel')).filter(function (panel) {
                return panel.closest('[data-okip-tabs]') === group;
            });

            function activate(name) {
                buttons.forEach(function (b) {
                    b.classList.toggle('is-active', b.getAttribute('data-okip-tab-target') === name);
                });
                panels.forEach(function (p) {
                    p.classList.toggle('is-active', p.getAttribute('data-okip-tab') === name);
                });
            }

            buttons.forEach(function (b) {
                b.addEventListener('click', function () {
                    activate(b.getAttribute('data-okip-tab-target'));
                });
            });

            // Activar la primera tab si ninguna viene marcada desde el servidor.
            if (!buttons.some(function (button) { return button.classList.contains('is-active'); }) && buttons[0]) {
                activate(buttons[0].getAttribute('data-okip-tab-target'));
            }
        });
    }

    // ---- Visibilidad condicional (tipo de fondo / tipo de tarjeta) ----
    // Los contenedores con data-okip-when-bg / data-okip-when-card-type listan los
    // valores (separados por espacio) en los que deben mostrarse. Los inputs ocultos
    // siguen enviándose en el POST: por eso cada `name` aparece una sola vez en el DOM.
    function toggleWhen(scope, selector, attr, value) {
        scope.querySelectorAll(selector).forEach(function (el) {
            var allowed = (el.getAttribute(attr) || '').split(/\s+/);
            el.hidden = allowed.indexOf(value) === -1;
        });
    }

    function applyCardType(card) {
        var typeSel = card.querySelector('select[name$="][type]"]');
        if (typeSel) { toggleWhen(card, '[data-okip-when-card-type]', 'data-okip-when-card-type', typeSel.value); }
    }

    function initConditionalFields() {
        document.querySelectorAll('[data-okip-tabs]').forEach(function (root) {
            var bgSelect = Array.prototype.slice.call(root.querySelectorAll('select[name$="[background][type]"]')).filter(function (select) {
                return select.closest('[data-okip-tabs]') === root;
            })[0];

            if (bgSelect) {
                var applyBg = function () {
                    toggleWhen(root, '[data-okip-when-bg]', 'data-okip-when-bg', bgSelect.value);
                };
                bgSelect.addEventListener('change', applyBg);
                applyBg();
            }

            Array.prototype.slice.call(root.querySelectorAll('[data-okip-card]')).filter(function (card) {
                return card.closest('[data-okip-tabs]') === root;
            }).forEach(applyCardType);

            root.addEventListener('change', function (e) {
                var sel = e.target;
                if (sel && sel.matches && sel.matches('select[name$="][type]"]')) {
                    if (sel.closest('[data-okip-tabs]') !== root) { return; }
                    var card = sel.closest('[data-okip-card]');
                    if (card) { applyCardType(card); }
                }
            });
        });
    }

    // ---- Tarjetas dinámicas del Hero (add/duplicar/eliminar + maqueta drag/snap) ----
    var CARD_SNAP = [0, 25, 50, 75, 100];
    var CARD_SNAP_THRESHOLD = 2.5;

    function clamp(v, lo, hi) { return Math.min(hi, Math.max(lo, v)); }
    function round1(v) { return Math.round(v * 10) / 10; }

    function setupCardsGroup(group) {
        var list = group.querySelector('[data-okip-cards-list]');
        var stage = group.querySelector('[data-okip-stage]');
        var addBtn = group.querySelector('[data-okip-card-add]');
        var countEl = group.querySelector('[data-okip-card-count]');
        var tpl = group.querySelector('[data-okip-card-template]');
        var max = parseInt(group.getAttribute('data-okip-max'), 10) || 10;
        // Colección configurable: el Hero usa la default `cards`; otros bloques
        // (p.ej. video-w-title) declaran su propia clave/prefijo/variante de maqueta.
        var collKey = group.getAttribute('data-okip-cards-key') || 'cards';
        var idPrefix = group.getAttribute('data-okip-cards-idprefix') || 'card';
        var variant = group.getAttribute('data-okip-cards-variant') || '';
        var collRe = new RegExp('\\[' + collKey + '\\]\\[[^\\]]*\\]');
        // Referencias para mapear medidas en px del bloque (viewport real) a la maqueta.
        var REF_VW = 1440, REF_VH = 900;
        if (!list || !tpl) { return; }

        function cards() {
            return Array.prototype.slice.call(list.querySelectorAll('[data-okip-card]'));
        }
        function getInput(card, key) {
            return card.querySelector('[name$="][' + key + ']"]:not([type="hidden"])');
        }
        function activeBox(card) {
            return card.querySelector('input[type="checkbox"][name$="][active]"]');
        }
        function num(card, key, dflt) {
            var f = getInput(card, key);
            var v = f ? parseFloat(f.value) : NaN;
            return isFinite(v) ? v : dflt;
        }
        function setVal(card, key, value) {
            var f = getInput(card, key);
            if (f) { f.value = value; }
            if (key === 'id') {
                var lg = card.querySelector('[data-okip-card-legend]');
                if (lg) { lg.textContent = value; }
            }
        }
        function uniqueId(baseId) {
            var ids = cards().map(function (c) { var f = getInput(c, 'id'); return f ? f.value : ''; });
            var candidate = baseId, n = 2;
            while (ids.indexOf(candidate) !== -1) { candidate = baseId + '-' + n; n++; }
            return candidate;
        }
        function reindex() {
            cards().forEach(function (card, i) {
                card.querySelectorAll('[name]').forEach(function (el) {
                    el.name = el.name.replace(collRe, '[' + collKey + '][' + i + ']');
                });
            });
        }
        function updateCount() {
            var n = cards().length;
            if (countEl) { countEl.textContent = n + ' / ' + max; }
            if (addBtn) { addBtn.disabled = n >= max; }
        }
        function afterStructureChange() {
            reindex();
            updateCount();
            buildStage();
        }

        function addCard() {
            if (cards().length >= max) { return; }
            var node = tpl.content.firstElementChild.cloneNode(true);
            list.appendChild(node);
            reindex();
            var idField = getInput(node, 'id');
            if (idField) { idField.value = uniqueId(idPrefix + '-' + cards().length); }
            var lg = node.querySelector('[data-okip-card-legend]');
            if (lg && idField) { lg.textContent = idField.value; }
            applyCardType(node);
            updateCount();
            buildStage();
        }
        function duplicateCard(card) {
            if (cards().length >= max) { return; }
            var clone = card.cloneNode(true);
            // cloneNode no copia value/checked en vivo: copiarlos por posición.
            var src = card.querySelectorAll('input, select, textarea');
            var dst = clone.querySelectorAll('input, select, textarea');
            for (var i = 0; i < src.length; i++) {
                if (!dst[i]) { continue; }
                if (src[i].type === 'checkbox' || src[i].type === 'radio') {
                    dst[i].checked = src[i].checked;
                } else {
                    dst[i].value = src[i].value;
                }
            }
            card.parentNode.insertBefore(clone, card.nextSibling);
            reindex();
            var idField = getInput(clone, 'id');
            if (idField) { idField.value = uniqueId((idField.value || idPrefix) + '-copy'); }
            var lg = clone.querySelector('[data-okip-card-legend]');
            if (lg && idField) { lg.textContent = idField.value; }
            applyCardType(clone);
            updateCount();
            buildStage();
        }
        function removeCard(card) {
            card.parentNode.removeChild(card);
            afterStructureChange();
        }

        // ---- Maqueta ----
        function clearSnap() {
            if (!stage) { return; }
            stage.querySelectorAll('.okip-admin-stage__snap').forEach(function (n) { n.remove(); });
        }
        function addSnapLine(dir, pct) {
            var line = document.createElement('div');
            line.className = 'okip-admin-stage__snap okip-admin-stage__snap--' + dir;
            if (dir === 'v') { line.style.left = pct + '%'; } else { line.style.top = pct + '%'; }
            stage.appendChild(line);
        }
        function applySnap(value) {
            for (var i = 0; i < CARD_SNAP.length; i++) {
                if (Math.abs(value - CARD_SNAP[i]) <= CARD_SNAP_THRESHOLD) { return CARD_SNAP[i]; }
            }
            return value;
        }

        // ---- Magnetismo (snap) on/off ----
        // Es una ayuda de edición, no un dato del bloque: no se envía en el POST; se
        // recuerda en localStorage por colección (hero / text_boxes independientes).
        var snapToggle = null;
        function snapOn() { return !snapToggle || snapToggle.checked; }
        function buildSnapToggle() {
            if (!stage || snapToggle) { return; }
            var lsKey = 'okipSnap:' + collKey;
            var wrap = document.createElement('label');
            wrap.className = 'okip-admin-snap-toggle';
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            var saved = null;
            try { saved = window.localStorage.getItem(lsKey); } catch (e) {}
            cb.checked = (saved === null) ? true : (saved === '1');
            cb.addEventListener('change', function () {
                try { window.localStorage.setItem(lsKey, cb.checked ? '1' : '0'); } catch (e) {}
            });
            var span = document.createElement('span');
            span.textContent = 'Magnetismo (ajustar a la cuadrícula)';
            wrap.appendChild(cb);
            wrap.appendChild(span);
            stage.parentNode.insertBefore(wrap, stage.nextSibling);
            snapToggle = cb;
        }
        function buildStage() {
            if (!stage) { return; }
            stage.querySelectorAll('.okip-admin-stage__card, .okip-admin-stage__snap').forEach(function (n) { n.remove(); });
            var rect0 = stage.getBoundingClientRect();
            cards().forEach(function (card) {
                var mini = document.createElement('div');
                mini.className = 'okip-admin-stage__card' + (variant ? ' okip-admin-stage__card--' + variant : '');
                var box = activeBox(card);
                if (box && !box.checked) { mini.classList.add('is-inactive'); }
                var hasHeight = !!getInput(card, 'height_px');

                // Etiqueta: en la variante texto refleja el CONTENIDO real (con su
                // alineación, color, peso y tamaño proporcional) → preview fiel al bloque.
                var label = document.createElement('span');
                var idField = getInput(card, 'id');
                if (variant === 'text') {
                    var contentField = getInput(card, 'content');
                    var text = (contentField && contentField.value.trim() !== '') ? contentField.value : (idField ? idField.value : '');
                    label.textContent = text;
                    var alignField = getInput(card, 'align');
                    label.style.textAlign = alignField ? alignField.value : 'center';
                    var colorField = getInput(card, 'color');
                    if (colorField && colorField.value) { mini.style.color = colorField.value; }
                    mini.style.fontWeight = num(card, 'font_weight', 400);
                    label.style.lineHeight = num(card, 'line_height', 1.2);
                    label.style.fontSize = Math.max(7, num(card, 'font_size_px', 32) * (rect0.width / REF_VW)) + 'px';
                } else {
                    label.textContent = idField ? idField.value : '';
                }
                mini.appendChild(label);

                // Tiradores: esquina = ambos ejes; bordes = un eje (solo si hay alto editable).
                var handle = document.createElement('span');
                handle.className = 'okip-admin-stage__resize okip-admin-stage__resize--corner';
                mini.appendChild(handle);
                var handleE = null, handleS = null;
                if (hasHeight) {
                    handleE = document.createElement('span');
                    handleE.className = 'okip-admin-stage__resize okip-admin-stage__resize--e';
                    mini.appendChild(handleE);
                    handleS = document.createElement('span');
                    handleS.className = 'okip-admin-stage__resize okip-admin-stage__resize--s';
                    mini.appendChild(handleS);
                }

                mini.style.left = num(card, 'x', 50) + '%';
                mini.style.top = num(card, 'y', 50) + '%';
                mini.style.width = num(card, 'width_pct', 14) + '%';
                if (hasHeight) {
                    var hpx = num(card, 'height_px', 0);
                    mini.style.height = hpx > 0 ? (hpx / REF_VH * 100) + '%' : 'auto';
                }

                mini.addEventListener('pointerdown', function (e) {
                    if (e.target === handle) { startResize(mini, card, e, 'both'); }
                    else if (e.target === handleE) { startResize(mini, card, e, 'x'); }
                    else if (e.target === handleS) { startResize(mini, card, e, 'y'); }
                    else { startDrag(mini, card, e); }
                });
                stage.appendChild(mini);
            });
        }
        function startDrag(mini, card, e) {
            if (!stage) { return; }
            e.preventDefault();
            var rect = stage.getBoundingClientRect();
            var miniRect = mini.getBoundingClientRect();
            var halfW = (miniRect.width / rect.width) * 100 / 2;
            var halfH = (miniRect.height / rect.height) * 100 / 2;
            mini.classList.add('is-dragging');
            function move(ev) {
                var rawx = (ev.clientX - rect.left) / rect.width * 100;
                var rawy = (ev.clientY - rect.top) / rect.height * 100;
                var on = snapOn();
                var sx = on ? applySnap(rawx) : rawx;
                var sy = on ? applySnap(rawy) : rawy;
                clearSnap();
                if (on && CARD_SNAP.indexOf(sx) !== -1) { addSnapLine('v', sx); }
                if (on && CARD_SNAP.indexOf(sy) !== -1) { addSnapLine('h', sy); }
                var px = clamp(sx, halfW, 100 - halfW);
                var py = clamp(sy, halfH, 100 - halfH);
                setVal(card, 'x', round1(px));
                setVal(card, 'y', round1(py));
                mini.style.left = px + '%';
                mini.style.top = py + '%';
            }
            function up() {
                mini.classList.remove('is-dragging');
                clearSnap();
                document.removeEventListener('pointermove', move);
                document.removeEventListener('pointerup', up);
            }
            document.addEventListener('pointermove', move);
            document.addEventListener('pointerup', up);
        }
        function startResize(mini, card, e, axis) {
            if (!stage) { return; }
            e.preventDefault();
            e.stopPropagation();
            axis = axis || 'both';
            var rect = stage.getBoundingClientRect();
            var hasHeight = !!getInput(card, 'height_px');
            // Rango de ancho por variante (coincide con el clamp del servidor).
            var wMin = variant === 'text' ? 5 : 6;
            var wMax = variant === 'text' ? 100 : 30;
            function move(ev) {
                if (axis === 'both' || axis === 'x') {
                    var cx = num(card, 'x', 50);
                    var px = (ev.clientX - rect.left) / rect.width * 100;
                    var w = clamp(round1(Math.abs(px - cx) * 2), wMin, wMax);
                    setVal(card, 'width_pct', w);
                    mini.style.width = w + '%';
                }
                if ((axis === 'both' || axis === 'y') && hasHeight) {
                    var cy = num(card, 'y', 50);
                    var py = (ev.clientY - rect.top) / rect.height * 100;
                    // % del alto de la maqueta → px del bloque (vía viewport de referencia).
                    var hpx = clamp(Math.round(Math.abs(py - cy) * 2 / 100 * REF_VH), 0, 1200);
                    setVal(card, 'height_px', hpx);
                    mini.style.height = hpx > 0 ? (hpx / REF_VH * 100) + '%' : 'auto';
                }
            }
            function up() {
                document.removeEventListener('pointermove', move);
                document.removeEventListener('pointerup', up);
            }
            document.addEventListener('pointermove', move);
            document.addEventListener('pointerup', up);
        }

        // ---- Wiring ----
        if (addBtn) { addBtn.addEventListener('click', addCard); }
        list.addEventListener('click', function (e) {
            var dup = e.target.closest('[data-okip-card-dup]');
            var rem = e.target.closest('[data-okip-card-remove]');
            if (dup) { e.preventDefault(); duplicateCard(dup.closest('[data-okip-card]')); }
            else if (rem) { e.preventDefault(); removeCard(rem.closest('[data-okip-card]')); }
        });
        function syncFromInput(target) {
            if (/\]\[(x|y|width_pct|height_px|id|active|content|align|color|font_weight|font_size_px|line_height)\]$/.test(target.name || '')) { buildStage(); }
        }
        list.addEventListener('input', function (e) { syncFromInput(e.target); });
        list.addEventListener('change', function (e) { syncFromInput(e.target); });

        buildSnapToggle();
        reindex();
        updateCount();
        buildStage();
    }

    function initHeroCards() {
        document.querySelectorAll('[data-okip-cards]').forEach(setupCardsGroup);
    }

    // ---- Orden de bloques por página ----
    function setupBlockOrder(root) {
        var list = root.querySelector('[data-okip-order-list]');
        var reset = root.querySelector('[data-okip-order-reset]');
        var dragging = null;

        if (!list) { return; }

        function items() {
            return Array.prototype.slice.call(list.querySelectorAll('[data-okip-order-item]'));
        }

        function updateState() {
            var all = items();
            all.forEach(function (item, index) {
                var pos = item.querySelector('[data-okip-order-position]');
                var up = item.querySelector('[data-okip-order-up]');
                var down = item.querySelector('[data-okip-order-down]');

                if (pos) { pos.textContent = String(index + 1); }
                if (up) { up.disabled = index === 0; }
                if (down) { down.disabled = index === all.length - 1; }
            });
        }

        function moveItem(item, direction) {
            if (!item) { return; }
            if (direction < 0 && item.previousElementSibling) {
                list.insertBefore(item, item.previousElementSibling);
            } else if (direction > 0 && item.nextElementSibling) {
                list.insertBefore(item.nextElementSibling, item);
            }
            updateState();
        }

        function resetToBase() {
            var baseOrder;
            try {
                baseOrder = JSON.parse(root.getAttribute('data-base-order') || '[]');
            } catch (e) {
                baseOrder = [];
            }

            if (!Array.isArray(baseOrder) || !baseOrder.length) {
                return;
            }

            var byId = {};
            items().forEach(function (item) {
                byId[item.getAttribute('data-instance-id') || ''] = item;
            });

            baseOrder.forEach(function (id) {
                if (byId[id]) {
                    list.appendChild(byId[id]);
                    delete byId[id];
                }
            });

            Object.keys(byId).forEach(function (id) {
                list.appendChild(byId[id]);
            });

            updateState();
        }

        list.addEventListener('click', function (event) {
            var up = event.target.closest('[data-okip-order-up]');
            var down = event.target.closest('[data-okip-order-down]');
            if (!up && !down) { return; }

            event.preventDefault();
            moveItem(event.target.closest('[data-okip-order-item]'), up ? -1 : 1);
        });

        list.addEventListener('dragstart', function (event) {
            var item = event.target.closest('[data-okip-order-item]');
            if (!item) { return; }

            dragging = item;
            item.classList.add('is-dragging');
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', item.getAttribute('data-instance-id') || '');
            }
        });

        list.addEventListener('dragover', function (event) {
            var item;
            var rect;
            var after;

            if (!dragging) { return; }

            item = event.target.closest('[data-okip-order-item]');
            if (!item || item === dragging) { return; }

            event.preventDefault();
            rect = item.getBoundingClientRect();
            after = event.clientY > rect.top + (rect.height / 2);
            list.insertBefore(dragging, after ? item.nextSibling : item);
            updateState();
        });

        list.addEventListener('drop', function (event) {
            if (dragging) {
                event.preventDefault();
            }
        });

        list.addEventListener('dragend', function () {
            if (dragging) {
                dragging.classList.remove('is-dragging');
                dragging = null;
                updateState();
            }
        });

        if (reset) {
            reset.addEventListener('click', function (event) {
                event.preventDefault();
                resetToBase();
            });
        }

        updateState();
    }

    function initBlockOrder() {
        document.querySelectorAll('[data-okip-order]').forEach(setupBlockOrder);
    }

    function initAll() {
        initFonts();
        initMediaFields();
        initBlockTabs();
        initConditionalFields();
        initHeroCards();
        initBlockOrder();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
