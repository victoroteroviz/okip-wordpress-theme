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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initFonts();
            initMediaFields();
        });
    } else {
        initFonts();
        initMediaFields();
    }
})();
