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
            var buttons = group.querySelectorAll('.okip-admin-tab-btn');
            var panels = group.querySelectorAll('.okip-admin-tab-panel');

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
            if (!group.querySelector('.okip-admin-tab-btn.is-active') && buttons[0]) {
                activate(buttons[0].getAttribute('data-okip-tab-target'));
            }
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
                    el.name = el.name.replace(/\[cards\]\[[^\]]*\]/, '[cards][' + i + ']');
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
            if (idField) { idField.value = uniqueId('card-' + cards().length); }
            var lg = node.querySelector('[data-okip-card-legend]');
            if (lg && idField) { lg.textContent = idField.value; }
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
            if (idField) { idField.value = uniqueId((idField.value || 'card') + '-copy'); }
            var lg = clone.querySelector('[data-okip-card-legend]');
            if (lg && idField) { lg.textContent = idField.value; }
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
        function buildStage() {
            if (!stage) { return; }
            stage.querySelectorAll('.okip-admin-stage__card, .okip-admin-stage__snap').forEach(function (n) { n.remove(); });
            cards().forEach(function (card) {
                var mini = document.createElement('div');
                mini.className = 'okip-admin-stage__card';
                var box = activeBox(card);
                if (box && !box.checked) { mini.classList.add('is-inactive'); }
                var label = document.createElement('span');
                var idField = getInput(card, 'id');
                label.textContent = idField ? idField.value : '';
                mini.appendChild(label);
                var handle = document.createElement('span');
                handle.className = 'okip-admin-stage__resize';
                mini.appendChild(handle);
                mini.style.left = num(card, 'x', 50) + '%';
                mini.style.top = num(card, 'y', 50) + '%';
                mini.style.width = num(card, 'width_pct', 14) + '%';
                mini.addEventListener('pointerdown', function (e) {
                    if (e.target === handle) { startResize(mini, card, e); }
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
                var sx = applySnap(rawx), sy = applySnap(rawy);
                clearSnap();
                if (CARD_SNAP.indexOf(sx) !== -1) { addSnapLine('v', sx); }
                if (CARD_SNAP.indexOf(sy) !== -1) { addSnapLine('h', sy); }
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
        function startResize(mini, card, e) {
            if (!stage) { return; }
            e.preventDefault();
            e.stopPropagation();
            var rect = stage.getBoundingClientRect();
            function move(ev) {
                var cx = num(card, 'x', 50);
                var px = (ev.clientX - rect.left) / rect.width * 100;
                var w = clamp(round1(Math.abs(px - cx) * 2), 6, 30);
                setVal(card, 'width_pct', w);
                mini.style.width = w + '%';
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
            if (/\]\[(x|y|width_pct|id|active)\]$/.test(target.name || '')) { buildStage(); }
        }
        list.addEventListener('input', function (e) { syncFromInput(e.target); });
        list.addEventListener('change', function (e) { syncFromInput(e.target); });

        reindex();
        updateCount();
        buildStage();
    }

    function initHeroCards() {
        document.querySelectorAll('[data-okip-cards]').forEach(setupCardsGroup);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initFonts();
            initMediaFields();
            initBlockTabs();
            initHeroCards();
        });
    } else {
        initFonts();
        initMediaFields();
        initBlockTabs();
        initHeroCards();
    }
})();
