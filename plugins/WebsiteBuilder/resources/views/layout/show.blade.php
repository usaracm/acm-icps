<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $name }} - {{ app()->getCurrentScheduledConference()->title }}</title>
    <meta name="application-name" content="Leconfe" />

    @auth
        <meta name="username" content="{{ auth()->user()->given_name }}" />
        <meta name="profile_picture" content="{{ auth()->user()->getFirstMediaUrl('profile') }}">
    @endauth

    <!-- Open Graph Meta Tags (for Facebook, LinkedIn, etc.) -->
    <meta property="og:title" content="{{ $name }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @if (isset($description))
        <meta property="og:description" content="{{ $description }}">
    @endif

    @if (isset($faviconUrl))
        <meta property="og:image" content="{{ $faviconUrl }}">

        <link rel="icon" type="image/png" sizes="32*32" href="{{ $faviconUrl ?? '' }}" />
        <link rel="icon" type="image/png" sizes="16*16" href="{{ $faviconUrl ?? '' }}" />
        <link rel="apple-touch-icon" sizes="180x180" href="{{ $faviconUrl }}">
    @endif

    {!! $meta !!}
    @if (isset($header) && isset($header['main_css']) && isset($header['section_css']))
        {!! $header['main_css'] !!}
        {!! $header['section_css'] !!}
    @endif
    @if (isset($footer) && isset($footer['main_css']) && isset($footer['section_css']))
        {!! $footer['main_css'] !!}
        {!! $footer['section_css'] !!}
    @endif

    <link href="{{ $assetsBasePath }}/runtime/contentbox-runtime.css" rel="stylesheet">
</head>

<body style="touch-action: pan-y">

    @if (isset($published) && !$published)
        <div
            style="
                background-color: #fff;
                color: #000;
                border-bottom: 1px solid gray;
                padding: 10px 20px;
                text-align: center;
                font-family: sans-serif;
                font-size: 13px;
                font-weight: 500;
                letter-spacing: 0.5px;
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 8px;
            ">
            <span
                style="background: #ecc94b; color: #000; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                Draft
            </span>
            <span>
                This page isn’t live yet
            </span>
        </div>
    @endif

    @if (isset($header) && isset($header['content_html']))
        {!! $header['content_html'] !!}
    @endif

    {{ $slot }}

    @if (isset($footer) && isset($footer['content_html']))
        {!! $footer['content_html'] !!}
    @endif

    <script src="{{ $assetsBasePath }}/runtime/contentbox-runtime.min.js"></script>
    <script>
        const runtime = new ContentBoxRuntime();

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
                    return;
                }

                registerLatestPlugin(name);
            });
        }

        detectPluginWithoutVersion()
        const types = runtime.pluginManager.detectComponentTypes();
        registerPlugins(types);
        console.log('sdf')

        runtime.init();
    </script>
</body>

</html>
