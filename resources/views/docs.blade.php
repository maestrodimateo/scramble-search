<!doctype html>
<html lang="en" data-theme="{{ $config->get('ui.theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="color-scheme" content="{{ $config->get('ui.theme', 'light') }}">
    <title>{{ $config->get('ui.title') ?? config('app.name') . ' - API Docs' }}</title>

    <script src="https://unpkg.com/@stoplight/elements@8.4.2/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements@8.4.2/styles.min.css">

    <script>
        const originalFetch = window.fetch;

        // intercept TryIt requests and add the XSRF-TOKEN header,
        // which is necessary for Sanctum cookie-based authentication to work correctly
        window.fetch = (url, options) => {
            const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
            const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";
            const getCookieValue = (key) => {
                const cookie = document.cookie.split(';').find((cookie) => cookie.trim().startsWith(key));
                return cookie?.split("=")[1];
            };

            const updateFetchHeaders = (
                headers,
                headerKey,
                headerValue,
            ) => {
                if (headers instanceof Headers) {
                    headers.set(headerKey, headerValue);
                } else if (Array.isArray(headers)) {
                    headers.push([headerKey, headerValue]);
                } else if (headers) {
                    headers[headerKey] = headerValue;
                }
            };
            const csrfToken = getCookieValue(CSRF_TOKEN_COOKIE_KEY);
            if (csrfToken) {
                const { headers = new Headers() } = options || {};
                updateFetchHeaders(headers, CSRF_TOKEN_HEADER_KEY, decodeURIComponent(csrfToken));
                return originalFetch(url, {
                    ...options,
                    headers,
                });
            }

            return originalFetch(url, options);
        };
    </script>

    <style>
        html, body { margin:0; height:100%; }
        body { background-color: var(--color-canvas); }
        /* issues about the dark theme of stoplight/mosaic-code-viewer using web component:
         * https://github.com/stoplightio/elements/issues/2188#issuecomment-1485461965
         */
        [data-theme="dark"] .token.property {
            color: rgb(128, 203, 196) !important;
        }
        [data-theme="dark"] .token.operator {
            color: rgb(255, 123, 114) !important;
        }
        [data-theme="dark"] .token.number {
            color: rgb(247, 140, 108) !important;
        }
        [data-theme="dark"] .token.string {
            color: rgb(165, 214, 255) !important;
        }
        [data-theme="dark"] .token.boolean {
            color: rgb(121, 192, 255) !important;
        }
        [data-theme="dark"] .token.punctuation {
            color: #dbdbdb !important;
        }

        /* ── Search palette ── */
        #api-search-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0,0,0,.45);
            backdrop-filter: blur(2px);
            align-items: flex-start;
            justify-content: center;
            padding-top: 10vh;
        }
        #api-search-overlay.open { display: flex; }

        #api-search-box {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 24px 60px rgba(0,0,0,.3);
            width: min(680px, 92vw);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        [data-theme="dark"] #api-search-box {
            background: #1e2130;
            color: #e2e8f0;
        }

        #api-search-input-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        [data-theme="dark"] #api-search-input-wrap { border-bottom-color: #2d3748; }

        #api-search-input-wrap svg { flex-shrink: 0; opacity: .45; }

        #api-search-input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 16px;
            background: transparent;
            color: inherit;
        }

        #api-search-shortcut {
            font-size: 11px;
            color: #9ca3af;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 2px 6px;
            white-space: nowrap;
        }
        [data-theme="dark"] #api-search-shortcut {
            background: #2d3748;
            border-color: #4a5568;
            color: #718096;
        }

        #api-search-results {
            max-height: 420px;
            overflow-y: auto;
        }

        .api-route-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            cursor: pointer;
            border-left: 3px solid transparent;
            transition: background .1s;
        }
        .api-route-item:hover,
        .api-route-item.active {
            background: #f0f4ff;
            border-left-color: #6366f1;
        }
        [data-theme="dark"] .api-route-item:hover,
        [data-theme="dark"] .api-route-item.active {
            background: #2d3748;
            border-left-color: #818cf8;
        }

        .api-method-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            min-width: 50px;
            text-align: center;
            flex-shrink: 0;
            letter-spacing: .5px;
        }
        .badge-get    { background: #dcfce7; color: #15803d; }
        .badge-post   { background: #dbeafe; color: #1d4ed8; }
        .badge-put    { background: #fef3c7; color: #b45309; }
        .badge-patch  { background: #e0f2fe; color: #0369a1; }
        .badge-delete { background: #fee2e2; color: #b91c1c; }
        .badge-head, .badge-options { background: #f3f4f6; color: #6b7280; }

        .api-route-info { min-width: 0; }
        .api-route-path {
            font-family: monospace;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .api-route-summary {
            font-size: 12px;
            color: #6b7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        [data-theme="dark"] .api-route-summary { color: #9ca3af; }

        .api-route-tag {
            margin-left: auto;
            font-size: 11px;
            color: #9ca3af;
            white-space: nowrap;
            flex-shrink: 0;
        }

        #api-search-empty {
            text-align: center;
            padding: 32px 16px;
            color: #9ca3af;
            font-size: 14px;
        }

        /* Trigger button */
        #api-search-trigger {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(99,102,241,.4);
            transition: background .15s, transform .1s;
        }
        #api-search-trigger:hover { background: #4f46e5; transform: translateY(-1px); }
    </style>
</head>
<body style="height: 100vh; overflow-y: hidden">

<!-- Search Palette -->
<div id="api-search-overlay" role="dialog" aria-modal="true" aria-label="Recherche de routes">
    <div id="api-search-box">
        <div id="api-search-input-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input id="api-search-input" type="text" placeholder="Search for a route…" autocomplete="off" spellcheck="false"/>
            <span id="api-search-shortcut">Esc</span>
        </div>
        <div id="api-search-results" role="listbox"></div>
    </div>
</div>

<button id="api-search-trigger" title="Search a route (Ctrl+K)">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
    </svg>
    Search
    <span style="font-size:11px;opacity:.7;background:rgba(255,255,255,.2);border-radius:4px;padding:1px 5px;">⌘K</span>
</button>

<elements-api
    id="docs"
    tryItCredentialsPolicy="{{ $config->get('ui.try_it_credentials_policy', 'include') }}"
    router="hash"
    @if($config->get('ui.hide_try_it')) hideTryIt="true" @endif
    @if($config->get('ui.hide_schemas')) hideSchemas="true" @endif
    @if($config->get('ui.logo')) logo="{{ $config->get('ui.logo') }}" @endif
    @if($config->get('ui.layout')) layout="{{ $config->get('ui.layout') }}" @endif
    @if($config->get('ui.search', true) === false) hideSearch="true" @endif
/>
<script>
    (async () => {
        const docs = document.getElementById('docs');
        docs.apiDescriptionDocument = @json($spec);
    })();
</script>

<script>
(function () {
    const spec   = @json($spec);
    const paths  = spec.paths || {};
    const HTTP_METHODS = ['get','post','put','patch','delete','head','options'];

    // Build flat route list from spec
    const routes = [];
    for (const [path, methods] of Object.entries(paths)) {
        for (const method of HTTP_METHODS) {
            if (!methods[method]) continue;
            const op = methods[method];
            routes.push({
                method: method.toUpperCase(),
                path,
                summary:     op.summary || op.description || '',
                operationId: op.operationId || '',
                tag:         (op.tags || [])[0] || '',
            });
        }
    }
    routes.sort((a, b) => {
        const tagCmp = a.tag.localeCompare(b.tag);
        return tagCmp !== 0 ? tagCmp : a.path.localeCompare(b.path);
    });

    const overlay  = document.getElementById('api-search-overlay');
    const input    = document.getElementById('api-search-input');
    const results  = document.getElementById('api-search-results');
    const trigger  = document.getElementById('api-search-trigger');
    let activeIdx  = -1;

    function open() {
        overlay.classList.add('open');
        input.value = '';
        activeIdx = -1;
        render(routes);
        requestAnimationFrame(() => input.focus());
    }

    function close() {
        overlay.classList.remove('open');
    }

    function navigate(operationId) {
        if (operationId) window.location.hash = '#/operations/' + operationId;
        close();
    }

    function highlight(text, query) {
        if (!query) return escHtml(text);
        const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return escHtml(text).replace(re, '<mark style="background:#fef08a;border-radius:2px;padding:0 1px">$1</mark>');
    }

    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function render(list) {
        activeIdx = -1;
        if (!list.length) {
            results.innerHTML = '<div id="api-search-empty">No route found</div>';
            return;
        }
        results.innerHTML = list.map((r, i) => `
            <div class="api-route-item" role="option" data-idx="${i}" data-op="${escHtml(r.operationId)}">
                <span class="api-method-badge badge-${r.method.toLowerCase()}">${r.method}</span>
                <div class="api-route-info">
                    <div class="api-route-path">${highlight(r.path, input.value.trim())}</div>
                    ${r.summary ? `<div class="api-route-summary">${highlight(r.summary, input.value.trim())}</div>` : ''}
                </div>
                ${r.tag ? `<span class="api-route-tag">${escHtml(r.tag)}</span>` : ''}
            </div>
        `).join('');

        results.querySelectorAll('.api-route-item').forEach(el => {
            el.addEventListener('click', () => navigate(el.dataset.op));
        });
    }

    function filter(query) {
        if (!query.trim()) { render(routes); return; }
        const q = query.toLowerCase();
        render(routes.filter(r =>
            r.path.toLowerCase().includes(q)    ||
            r.method.toLowerCase().includes(q)  ||
            r.summary.toLowerCase().includes(q) ||
            r.tag.toLowerCase().includes(q)
        ));
    }

    function setActive(idx) {
        const items = results.querySelectorAll('.api-route-item');
        items.forEach(el => el.classList.remove('active'));
        if (idx >= 0 && idx < items.length) {
            items[idx].classList.add('active');
            items[idx].scrollIntoView({ block: 'nearest' });
            activeIdx = idx;
        }
    }

    // Events
    trigger.addEventListener('click', open);

    overlay.addEventListener('click', e => {
        if (e.target === overlay) close();
    });

    input.addEventListener('input', () => filter(input.value));

    input.addEventListener('keydown', e => {
        const items = results.querySelectorAll('.api-route-item');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(Math.min(activeIdx + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(Math.max(activeIdx - 1, 0));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIdx >= 0 && items[activeIdx]) {
                navigate(items[activeIdx].dataset.op);
            }
        } else if (e.key === 'Escape') {
            close();
        }
    });

    document.addEventListener('keydown', e => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            overlay.classList.contains('open') ? close() : open();
        }
        if (e.key === 'Escape' && overlay.classList.contains('open')) {
            close();
        }
    });
})();
</script>

@if($config->get('ui.theme', 'light') === 'system')
    <script>
        var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        function updateTheme(e) {
            if (e.matches) {
                window.document.documentElement.setAttribute('data-theme', 'dark');
                window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'dark');
            } else {
                window.document.documentElement.setAttribute('data-theme', 'light');
                window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'light');
            }
        }

        mediaQuery.addEventListener('change', updateTheme);
        updateTheme(mediaQuery);
    </script>
@endif
</body>
</html>