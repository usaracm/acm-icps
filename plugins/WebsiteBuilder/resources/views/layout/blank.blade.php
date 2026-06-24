<!DOCTYPE HTML>
<html>

<head>
    <meta charset="utf-8">
    <title></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A drag & drop HTML editor Javascript library for web applications.">
    <link rel="shortcut icon" href="#">
    <base href="{{ request()->url() }}">

    @auth
        <meta name="username" content="{{ auth()->user()->given_name }}" />
    @endauth
    <meta name="editor" content="1">

    <style>
        /* 1. Container Utama */
        .component-wrapper,
        .read-only,
        .component-wrapper-footer {
            position: relative;
            display: block;
            border: 2px dashed transparent;
        }

        .component-wrapper:hover,
        .component-wrapper-footer:hover {
            border: 2px dashed #3b82f6;
        }


        /* Force Visible Header Content */
        .component-wrapper [data-cb-type="nav-menu"] {
            opacity: 1 !important;
            visibility: visible !important;
            transition: none !important;
        }

        .read-only:hover::before {
            pointer-events: auto;
        }

        /* Saat hover, munculkan tombol */
        .component-wrapper:hover .editor-label,
        .component-wrapper-footer:hover .editor-label {
            opacity: 1;
            transform: translate(-50%, 0) scale(1);
        }

        .editor-label {
            position: absolute;
            top: 0;
            left: 50%;

            background: #3b82f6;
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: 700;
            font-size: 14px;
            white-space: nowrap;

            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1);

            opacity: 0;
            z-index: 9999;

            transition: all .4s cubic-bezier(.34, 1.56, .64, 1);
            cursor: pointer;
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 14px;
            font-weight: 700;
        }

        .editor-label-footer {
            top: auto;
            bottom: 0;
            transform: translate(-50%, 20%) scale(.7);
            border-top-right-radius: 1.5rem;
            border-top-left-radius: 1.5rem;
            height: 3rem;
            padding: 0 2rem;
        }

        .editor-label-header {
            top: 0;
            bottom: auto;
            transform: translate(-50%, -20%) scale(.7);
            border-bottom-right-radius: 1.2rem;
            border-bottom-left-radius: 1.2rem;
            font-size: 13px;
            height: 2.3rem;
            padding: 0 1.5rem;
        }

        body {
            margin-bottom: 0 !important;
        }
    </style>

    <link href="{{ $getAsset('/runtime/contentbox-runtime.css') }}" rel="stylesheet">
</head>

<body style="touch-action: pan-y; flex-direction: column;">
    @if (isset($headerUrl))
        <div class="is-section protected component-wrapper is-layout-wrapper" contenteditable="false">
            <div class="editor-label editor-label-header" onclick="parent.open('{{ $headerUrl }}', '_blank');">
                Click to Open Header Editor
            </div>

            @if (isset($header) && $header !== '')
                {!! $headerCss ?? '' !!}
                {!! $header ?? '' !!}
            @else
                <div style="width: 100%; height: 5rem;"></div>
            @endif
        </div>
    @endif

    @if (isset($layoutShowedName) && $layoutShowedName)
        @php
            $home = WebsiteBuilder\Models\Website::where('is_default', true)->first();
        @endphp
    @endif

    @if (isset($home) && $layoutShowedName === 'footer' && $home)
        <div class="is-section protected read-only is-layout-wrapper" contenteditable="false"
            style="opacity: 1; cursor: not-allowed;">
            {!! $home->getMetaAsStringHtmlTag() !!}
            {!! str_replace('is-container', 'is-container-protected', $home->getMeta('content_html', '')) !!}
        </div>
    @endif

    {{ $slot }}

    @if (isset($home) && $layoutShowedName === 'header' && $home)
        <div class="is-section protected read-only is-layout-wrapper" contenteditable="false"
            style="opacity: 1; cursor: not-allowed; z-index: 0;">
            {!! $home->getMetaAsStringHtmlTag() !!}
            {!! str_replace('is-container', 'is-container-protected', $home->getMeta('content_html', '')) !!}
        </div>
    @endif

    @if (isset($footerUrl))
        <div class="is-section protected component-wrapper-footer is-layout-wrapper" contenteditable="false">
            <div class="editor-label editor-label-footer" onclick="parent.open('{{ $footerUrl }}', '_blank');">
                Click to Open Footer Editor
            </div>

            @if (isset($footer) && $footer !== '')
                {!! $footerCss ?? '' !!}
                {!! str_replace('is-container', 'is-container-protected', $footer) !!}
            @else
                <div style="width: 100%; height: 5rem;"></div>
            @endif
        </div>
    @endif

    <script src="{{ $getAsset('/runtime/contentbox-runtime.min.js') }}"></script>
    <script>
        const runtime = new ContentBoxRuntime({});
        runtime.init();

        function registerLatestPlugin(name) {
            runtime.pluginManager.config.plugins[name] = {
                url: `{{ $pluginPath }}/${name}/latest/index.js`,
                css: `{{ $pluginPath }}/${name}/latest/style.css`
            };
        }

        function registerPlugins(names) {
            names.forEach(name => {
                if (name.includes('@')) {
                    const version = name.split('@')[1];
                    const nameWithoutVersion = name.split('@')[0];
                    runtime.pluginManager.config.plugins[name] = {
                        url: `{{ $pluginPath }}/${nameWithoutVersion}/${version}/index.js`,
                        css: `{{ $pluginPath }}/${nameWithoutVersion}/${version}/style.css`
                    };
                    registerLatestPlugin(nameWithoutVersion);
                    return;
                }

                registerLatestPlugin(name);
            });
        }

        function assignVersionType(element) {
            const type = element.getAttribute('data-cb-type');
            if (!type || type.includes('@')) return;

            let version = element.getAttribute('data-plugin-version');
            if (!version) return;

            const versionedType = `${type}@${version}`;
            element.setAttribute('data-cb-type', versionedType);
            element.setAttribute('data-cb-type-original', type);

            return element;
        }

        function assignVersionAttribute(element) {
            const type = element.getAttribute('data-cb-type');
            if (!type || type.includes('@')) return;

            let version = element.getAttribute('data-plugin-version');
            if (version) return;

            if (!runtime.pluginManager.plugins.has(type)) {
                return;
            }

            version = runtime.pluginManager.plugins.get(type).version;
            element.setAttribute('data-plugin-version', version);
            return element;
        }

        function detectPluginWithoutVersion() {
            const elements = document.querySelectorAll("[data-cb-type]");
            elements.forEach(el => {
                const type = el.getAttribute('data-cb-type');
                if (!type || type.includes('@')) return;

                if (el.hasAttribute('data-plugin-version')) {
                    assignVersionType(el);
                } else {
                    assignVersionAttribute(el);
                }
            })
        }

        function initializeComponents(rootElement = document) {
            const elements = rootElement.querySelectorAll("[data-cb-type]");

            elements.forEach((t, s) => {
                if (t.hasAttribute("data-cb-loaded")) return;

                const type = t.dataset.cbType;
                const plugin = runtime.pluginManager.plugins.get(type);

                if (!plugin || typeof plugin.mount !== "function") return;

                try {
                    const options = runtime.pluginManager.parseOptions(t);

                    // Setup sortable index
                    let sortableContainer = t.querySelector(".grid-sortable");
                    if (!sortableContainer && t.classList.contains("grid-sortable")) {
                        sortableContainer = t;
                    }

                    if (sortableContainer) {
                        Array.from(sortableContainer.children).forEach((child, index) => {
                            if (
                                child.nodeType === 1 &&
                                child.tagName !== "STYLE" &&
                                child.tagName !== "SCRIPT"
                            ) {
                                child.setAttribute("data-index", index);
                            }
                        });
                    }

                    // Setup edit id
                    t.querySelectorAll(".edit").forEach((el, index) => {
                        const editId = `content-${Date.now()}-${s}-${index}`;
                        el.setAttribute("data-edit-id", editId);
                    });

                    // Simpan original content
                    if (!t.hasAttribute("data-cb-original-content")) {
                        t.setAttribute("data-cb-original-content", t.innerHTML);
                    }

                    // Mount plugin
                    const instance = plugin.mount(t, options);

                    runtime.pluginManager.components.set(t, instance);

                    runtime.pluginManager.setupInlineEditing(t, options);

                    t.setAttribute("data-cb-loaded", "true");

                    if (typeof t.mount === "function") {
                        t.mount();
                    }

                } catch (error) {
                    console.error(
                        `[PluginManager] Failed to mount component "${type}":`,
                        error
                    );
                }
            });

            return runtime.pluginManager.components.size;
        }

        async function loadPlugin(name) {
            return runtime.pluginManager.loadPlugin(name)
        }

        parent.addEventListener('load-plugin', (e) => {
            const types = runtime.pluginManager.detectComponentTypes();
            const loaded = runtime.pluginManager.plugins;
            const toLoad = types.filter((type) => !loaded.has(type));

            if (toLoad.length !== 0) {
                const promises = toLoad.map((type) => {
                    registerPlugins([type]);
                    return loadPlugin(type).catch((err) => {
                        console.warn('Plugin load failed:', type, err);
                    });
                });

                Promise.all(promises).then(() => {
                    setTimeout(() => {
                        initializeComponents();
                    }, 500);
                }).catch((err) => {
                    console.warn('Some plugins failed to load:', err);
                    // initializeComponents();
                });
            }
        })

        parent.addEventListener('load-plugins', (e) => {
            detectPluginWithoutVersion();
            const types = runtime.pluginManager.detectComponentTypes();
            registerPlugins(types);
            runtime.reinitialize().then(() => {
                detectPluginWithoutVersion();
            }).catch((e) => console.warn('Plugin loading failed:', e));
        })

        // Make available globally for ContentBox editor
        window.builderRuntime = runtime;

        document.body.addEventListener('keydown', (e) => {
            const isInputOrEditable = e.target.closest('.is-container');
            if ((e.key === 'ArrowLeft' || e.key === 'ArrowRight') && !
                isInputOrEditable) { // To prevent horizontal page shift inside iframe
                e.preventDefault();
            }
        });

        document.addEventListener('touchmove', function(e) {
            if (e.touches.length === 2) { // two touches (two-finger gesture)
                e.preventDefault();
            }
        });

        // Auto-scroll to .is-wrapper when everything is loaded
        (function() {
            function scrollToWrapper() {
                const el = document.querySelector('.is-wrapper');
                if (!el) return;
                try {
                    el.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                } catch (err) {
                    // fallback
                    const top = el.getBoundingClientRect().top + window.pageYOffset;
                    window.scrollTo({
                        top,
                        behavior: 'smooth'
                    });
                }
            }

            // Run after window load and a couple of delayed attempts to cover late-inserted content
            window.addEventListener('load', () => {
                setTimeout(scrollToWrapper, 1500);
            });
        })();
    </script>
    <script>
        (function() {
            if (window.self === window.top) return;

            document.addEventListener('click', function(e) {
                const a = e.target.closest('a');
                if (!a || !a.getAttribute('href')) return;

                const href = a.getAttribute('href');

                // 1️⃣ Anchor-only (#id) → biarkan
                if (a.closest('.is-wrapper')) return;

                const current = new URL(window.location.href);
                const next = new URL(href, current.href);

                // 2️⃣ Jika hanya hash yang berubah → biarkan
                if (
                    current.origin === next.origin &&
                    current.pathname === next.pathname &&
                    current.search === next.search
                ) {
                    return;
                }

                e.preventDefault();

                if (confirm('Do you want to leave this page?')) {
                    window.parent.location.href = next.href;
                }
            });
        })();
    </script>
</body>

</html>
