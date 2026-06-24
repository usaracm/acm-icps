# WebsiteBuilder
## Development

### Building CSS with Tailwind

This plugin uses Vite and Tailwind CSS v4 to generate the app.css file.

#### Installation

```bash
npm install
```

#### Production Build

```bash
npm run build
```

The compiled CSS will be generated at `dist/app.css`.

### Content Scanning

Tailwind will automatically scan the following directories for class usage:
- `resources/views/**/*.blade.php`
- `resources/views/**/*.php`
- `resources/views/**/*.html`
- `src/**/*.php`

## Usage

### Include CSS in Blade Templates

Add the compiled CSS to your blade template:

```blade
{{-- In your blade file --}}
<link rel="stylesheet" href="{{ asset('plugin/WebsiteBuilder/dist/app.css') }}">
```
