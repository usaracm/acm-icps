<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />
    <link rel="icon" type="image/png" sizes="16*16" href="{{ asset('logo.png') ?? '' }}" />
    <title>{{ $name }} - {{ config('app.name') }}</title>
    <script src="{{ $contentBoxJs }}"></script>
    <link href="{{ $contentBoxCss }}" rel="stylesheet">
    <link href="{{ $contentBuilderCss }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @filamentStyles
    {{ filament()->getTheme()->getHtml() }}

    <style>
        :root {
            --font-family: '{!! filament()->getFontFamily() !!}';
            --sidebar-width: {{ filament()->getSidebarWidth() }};
            --collapsed-sidebar-width: {{ filament()->getCollapsedSidebarWidth() }};
            --default-theme-mode: {{ filament()->getDefaultThemeMode()->value }};
        }

        [x-cloak] {
            display: none !important;
        }

        .add-more,
        .pop-separator {
            display: none !important;
        }

        :root {
            --topspace: 7rem;
        }

        .custom-topbar {
            height: 4rem;
            margin-top: 3rem;
        }

        .custom-topbar>div:nth-child(3) {
            height: 4rem;
        }

        .custom-topbar>div:first-child {
            height: 4rem;
        }

        .brand {
            position: fixed;
            top: 0;
            height: 3rem;
            left: 0;
            right: 0;
            display: flex;
            z-index: 9999;

            border-bottom: 1px solid gray;
            background-color: white;
            padding: 0 1rem;
            justify-content: space-between;
            align-items: center;
        }

        .fi-no {
            z-index: 100000 !important;
        }

        .brand button {
            width: fit-content;
            padding: 0.3rem 1rem;
            border-radius: 20px;
        }
    </style>
</head>

<body>
    {{ $slot }}

    @livewire(Filament\Livewire\Notifications::class)
    @filamentScripts(withCore: true)
</body>

</html>
