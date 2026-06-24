<x-filament-panels::page>
    <div class="brand builder-ui keep-selection">
        <div style="display: flex; align-items: center; gap: 3rem;">
            <img src="{{ asset('logo.png') }}" alt="leconfe-logo" style="height: 2.3rem;">

            <div style="font-weight: 500;">
                {{ app()->getCurrentConference()->name }}
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.7rem; font-weight: 500;">
            <div class="save-wrap" style="display:inline-flex;align-items:center;gap:0.4rem;">
                <span id="btn-save-icon" class="save-icon" aria-hidden="true"
                    style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;flex:none;"></span>
            </div>
            <button id="btn-save" type="button" onclick="save()" title="save">
                <span id="btn-save-label" class="save-label"
                    style="line-height:1;font-size:0.95rem;padding:0 0.15rem; margin-left: 0;">Save</span>
            </button>

            <button type="button" onclick="preview()">
                Preview
            </button>
            <button type="button" id="btn-publish" onclick="publishToggle()">
                @if ($record->is_published)
                    Unpublish
                @else
                    Publish
                @endif
            </button>
        </div>
    </div>
    <div class="builder-ui keep-selection custom-topbar" style="opacity: 1;" data-tooltip>
        <div>
            {{ $record->name }}
        </div>

        <div>
            <button class="btn-back" onclick="back()" title="Back">
                <div>
                    <svg>
                        <use xlink:href="#icon-back"></use>
                    </svg>
                </div>
                <span>Back</span>
            </button>

            <div class="separator"></div>

            <button class="btn-undo" onclick="undo()" title="Undo">
                <div>
                    <svg>
                        <use xlink:href="#icon-undo"></use>
                    </svg>
                </div>
            </button>

            <button class="btn-redo" onclick="redo()" title="Redo">
                <div>
                    <svg>
                        <use xlink:href="#icon-redo"></use>
                    </svg>
                </div>
            </button>
        </div>

        <div>
            <button class="btn-device-mobile" data-device="mobile" onclick="setScreenMode(event)" title="Mobile">
                <div>
                    <svg style="width:18px;height:18px;">
                        <use xlink:href="#icon-device-mobile"></use>
                    </svg>
                </div>
            </button>

            <button class="btn-device-tablet" data-device="tablet" onclick="setScreenMode(event)"
                title="Tablet - Portrait">
                <div>
                    <svg style="width:18px;height:18px;">
                        <use xlink:href="#icon-device-tablet"></use>
                    </svg>
                </div>
            </button>

            <button class="btn-device-tablet-landscape" data-device="tablet-landscape" onclick="setScreenMode(event)"
                title="Tablet - Landscape">
                <div>
                    <svg style="width:18px;height:18px;transform:rotate(-90deg)">
                        <use xlink:href="#icon-device-tablet"></use>
                    </svg>
                </div>
            </button>

            <button class="btn-device-desktop" data-device="desktop" onclick="setScreenMode(event)"
                title="Desktop / Laptop">
                <div>
                    <svg style="width:18px;height:18px;">
                        <use xlink:href="#icon-device-laptop"></use>
                    </svg>
                </div>
            </button>

            <button class="btn-device-desktop-large" data-device="desktop-lg" onclick="setScreenMode(event)"
                title="Desktop - Large Screen">
                <div>
                    <svg style="width:18px;height:18px;">
                        <use xlink:href="#icon-device-desktop"></use>
                    </svg>
                </div>
            </button>

            <button class="btn-fullview" data-device="fullview" onclick="setScreenMode(event)" title="Full View">
                <div>
                    <svg style="width:18px;height:18px;">
                        <use xlink:href="#icon-fullview"></use>
                    </svg>
                </div>
            </button>
        </div>
    </div>

    <script wire:ignore>
        const rawAssetBasePath = @json($assetsBasePath ?? '/plugin/WebsiteBuilder/');
        const normalizeBasePath = (basePath) => {
            if (!basePath) return '/plugin/WebsiteBuilder/';

            // Allow absolute URLs (http/https), otherwise treat as path.
            if (/^https?:\/\//i.test(basePath)) {
                return basePath.replace(/\/?$/, '/');
            }

            let normalized = String(basePath);
            if (!normalized.startsWith('/')) normalized = '/' + normalized;
            return normalized.replace(/\/?$/, '/');
        };

        const getRuntimeFromIframe = () => {
            const win = builder?.iframe?.contentWindow;
            return win?.builderRuntime || null;
        };

        const removeHostFromUrl = (url) => {
            if (!url || typeof url !== 'string') return url;
            // Removes http://domain.com:port and leaves /path...
            return url.replace(/^https?:\/\/[^\/]+/, '');
        };

        const assetBasePath = normalizeBasePath(rawAssetBasePath);

        const builder = new ContentBox({
            topSpace: true,
            controlPanel: true,
            assetBasePath,
            iframeSrc: removeHostFromUrl("{{ WebsiteBuilder\Pages\ContentBuilderBlankPage::getUrl() }}"),

            imageSelect: "{{ WebsiteBuilder\Pages\ContentBuilderAssetsPickerPage::getUrl() | '' }}",
            videoSelect: "{{ WebsiteBuilder\Pages\ContentBuilderAssetsPickerPage::getUrl() | '' }}",
            audioSelect: "{{ WebsiteBuilder\Pages\ContentBuilderAssetsPickerPage::getUrl() | '' }}",
            mediaSelect: "{{ WebsiteBuilder\Pages\ContentBuilderAssetsPickerPage::getUrl() | '' }}",

            onUploadCoverImage: async (e) => {
                const data = await uploadFile(e);
                builder.boxImage(data.url); // change cover image
            },

            onImageUpload: async (e) => {
                const data = await uploadFile(e);
                builder.returnUrl(data.url);
            },

            onVideoUpload: async (e) => {
                const data = await uploadFile(e);
                builder.returnUrl(data.url);
            },

            onAudioUpload: async (e) => {
                const data = await uploadFile(e);
                builder.returnUrl(data.url);
            },

            onMediaUpload: async (e) => {
                const data = await uploadFile(e);
                builder.returnUrl(data.url);
            },

            templates: @json($templates ?? []),

            htmlButton: false,
            columnHtmlEditor: false,
            rowHtmlEditor: false,

            designUrl1: '',
            designUrl2: '',
            designPath: assetBasePath + 'assets/designs/',

            defaultHeaders: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },

            sidebarData: {
                "buttons": [{
                        "name": "section",
                        "title": "Widgets"
                    },
                    {
                        "name": "typography",
                        "title": "Typography"
                    },
                ]
            },
        });

        builder.onStart = () => {
            window.dispatchEvent(new Event('load-plugins'));
        }

        const initialHtml = @json($record->getMeta('content_html', ''));
        const headerFooterMainCss = @json(($header['main_css'] ?? '') . ($footer['main_css'] ?? ''));
        const headerFooterSectioncCss = @json(($header['section_css'] ?? '') . ($footer['section_css'] ?? ''));
        const initialMainCss = @json($record->getMeta('main_css', ''));
        const initialSectionCss = @json($record->getMeta('section_css', ''));
        builder.loadHtml(initialHtml);
        builder.loadStyles(initialMainCss + headerFooterMainCss, initialSectionCss + headerFooterSectioncCss);

        builder.addButton({
            pos: 2,
            title: 'Animation',
            html: '<svg class="is-icon-flex" style="fill:rgba(0, 0, 0, 0.7);width:14px;height:14px;"><use xlink:href="#icon-wand"></use></svg>',
            onClick: () => {
                builder.openAnimationPanel();
            }
        });

        builder.addButton({
            pos: 3,
            title: 'Timeline Editor',
            html: '<svg><use xlink:href="#icon-anim-timeline"></use></svg>',
            onClick: () => {
                builder.openAnimationTimeline();
            }
        });

        builder.addButton({
            pos: 5,
            title: 'Clear Content',
            html: '<svg class="is-icon-flex"><use xlink:href="#icon-eraser"></use></svg>',
            onClick: () => {
                builder.clear();
            }
        });

        builder.addButton({
            'pos': 6,
            'title': 'Settings',
            'src': '{{ WebsiteBuilder\Pages\ContentBuilderSettingsFormPage::getUrl(['website' => $record->id]) }}',
            'html': `<svg><use xlink:href="#icon-settings"></use></svg>`,
            'class': 'sidebar-sections',
        })

        builder.addButton({
            'pos': 7,
            'title': 'History',
            'src': '{{ WebsiteBuilder\Pages\RevisionPage::getUrl(['website' => $record->id]) }}',
            'html': `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16"><path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976q.576.129 1.126.342zm1.37.71a7 7 0 0 0-.439-.27l.493-.87a8 8 0 0 1 .979.654l-.615.789a7 7 0 0 0-.418-.302zm1.834 1.79a7 7 0 0 0-.653-.796l.724-.69q.406.429.747.91zm.744 1.352a7 7 0 0 0-.214-.468l.893-.45a8 8 0 0 1 .45 1.088l-.95.313a7 7 0 0 0-.179-.483m.53 2.507a7 7 0 0 0-.1-1.025l.985-.17q.1.58.116 1.17zm-.131 1.538q.05-.254.081-.51l.993.123a8 8 0 0 1-.23 1.155l-.964-.267q.069-.247.12-.501m-.952 2.379q.276-.436.486-.908l.914.405q-.24.54-.555 1.038zm-.964 1.205q.183-.183.35-.378l.758.653a8 8 0 0 1-.401.432z"/><path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0z"/><path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5"/></svg>`,
            'class': 'sidebar-sections',
        })

        async function uploadFile(e) {
            const file = e.target.files[0];
            const content = await new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.readAsDataURL(file);
            });

            const result = await @this.call('uploadFile', content);

            // Notify other frames (e.g. assets picker) to refresh their listing.
            if (result?.success) {
                try {
                    localStorage.setItem('websitebuilder-assets-updated', String(Date.now()));
                } catch (e) {
                    // ignore
                }
            }

            return result;
        }

        function back(e) {
            if (!builder) return;
            window.location.href = @json($backUrl ?? '');
        }

        function undo(e) {
            if (!builder) return;
            builder.undo();
        }

        function redo(e) {
            if (!builder) return;
            builder.redo();
        }

        function togglePanel() {
            if (!builder) return;
            builder.toggleEditPanel();
        }

        function preview() {
            if (!builder) return;

            open(@json(WebsiteBuilder\Pages\PreviewPage::getUrl(['website' => $record->id])), '_blank');
        }

        function setScreenMode(e) {
            if (!builder) return;

            document.querySelectorAll('.custom-topbar [data-device]').forEach(btn => btn.classList.remove('on'));

            const btn = e.target.closest('button');
            const screenMode = btn.getAttribute('data-device');

            builder.setScreenMode(screenMode);
            btn.classList.add('on');
        }

        function setSaveState(state, message) {
            const btn = document.getElementById('btn-save');
            const icon = document.getElementById('btn-save-icon');
            const label = document.getElementById('btn-save-label');

            if (!btn || !icon || !label) return;

            // Clear icon content
            icon.innerHTML = '';
            btn.disabled = false;

            if (state === 'loading') {
                btn.disabled = true;
                // inline SVG spinner (no CSS keyframes required)
                icon.innerHTML = '<svg viewBox="0 0 50 50" width="16" height="16" aria-hidden="true">' +
                    '<circle cx="25" cy="25" r="20" fill="none" stroke="rgba(0,0,0,0.12)" stroke-width="4"/>' +
                    '<path d="M45 25a20 20 0 0 1-20 20" stroke="currentColor" stroke-width="4" stroke-linecap="round" fill="none">' +
                    '<animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.9s" repeatCount="indefinite"/>' +
                    '</path>' +
                    '</svg>';
            } else if (state === 'success') {
                icon.innerHTML =
                    '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">' +
                    '<path d="M6 10.2L3.8 8l-.9 1 3.1 3.2L13 4.4l-1-1L6 10.2z" fill="currentColor"/>' +
                    '</svg>';
                setTimeout(() => {
                    icon.innerHTML = '';
                    label.textContent = 'Save';
                }, 1800);
            } else if (state === 'error') {
                icon.innerHTML =
                    '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">' +
                    '<path d="M4.5 4.5l7 7M11.5 4.5l-7 7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" fill="none"/>' +
                    '</svg>';
                label.textContent = message || 'Error';
                setTimeout(() => {
                    icon.innerHTML = '';
                    label.textContent = 'Save';
                }, 2500);
            }
        }

        async function save() {
            if (!builder) return;

            setSaveState('loading');

            const html = builder.html();
            const mainCss = builder.mainCss(); // Global styles used on the page
            const sectionCss = builder.sectionCss(); // Section-specific styles

            // prevent header footer style removed
            builder.loadStyles(mainCss + headerFooterMainCss, sectionCss + headerFooterSectioncCss);

            const slug = @json($record->slug);
            if (!slug) {
                console.error('Missing page slug (recordSlug).');
                setSaveState('error', 'Missing slug');
                return;
            }

            //To update preview
            try {
                localStorage.setItem('preview-html', html);
                localStorage.setItem('preview-maincss', mainCss);
                localStorage.setItem('preview-sectioncss', sectionCss);
            } catch (e) {
                // ignore storage errors
            }

            try {
                const result = await @this.call('save', {
                    slug,
                    content: html,
                    mainCss,
                    sectionCss,
                });

                if (result?.error) {
                    console.error('Save error:', result.error);
                    setSaveState('error', 'Save failed');
                } else {
                    console.log('Content saved successfully.');
                    setSaveState('success', 'Saved');
                }
            } catch (e) {
                console.error('Save error:', e);
                setSaveState('error', 'Save failed');
            }
        }

        async function publishToggle() {
            const btnPublish = document.getElementById('btn-publish');

            try {
                const result = await @this.call('togglePublish');

                if (result?.error) {
                    console.error('Publish toggle error:', result.error);
                } else {
                    const isPublished = result.is_published;
                    if (isPublished) {
                        btnPublish.textContent = 'Unpublish';
                    } else {
                        btnPublish.textContent = 'Publish';
                    }
                }
            } catch (e) {
                console.error('Publish toggle error:', e);
            }
        }

        const isBuilderReady = () => {
            const doc = builder?.iframe?.contentDocument;
            return !!(doc && typeof doc.getElementsByTagName === 'function');
        };

        window.WebsiteBuilder = {
            builder,
            isReady: isBuilderReady,
            getRendered: () => {
                if (!isBuilderReady()) return null;

                try {
                    return {
                        html: builder.html(),
                        mainCss: builder.mainCss(),
                        sectionCss: builder.sectionCss(),
                    };
                } catch (e) {
                    // Keep runtime safe; caller can retry after iframe is ready.
                    console.warn('ContentBox not ready yet:', e);
                    return null;
                }
            },
        };
    </script>
</x-filament-panels::page>
