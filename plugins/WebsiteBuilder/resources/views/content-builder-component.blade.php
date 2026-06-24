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
                    style="line-height:1;font-size:0.95rem;padding:0 0.15rem;margin-left: 0;">Save</span>
            </button>
        </div>
    </div>
    <div class="builder-ui keep-selection custom-topbar" style="opacity: 1" data-tooltip>
        <div>
            Widget:
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

    <script>
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
            iframeSrc: removeHostFromUrl(
                "{{ WebsiteBuilder\Pages\ContentBuilderBlankPage::getUrl(['doNotShowHeaderFooter' => true]) }}"
            ),

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
            }
        });

        builder.onStart = () => {
            window.dispatchEvent(new Event('load-plugins'));
        }

        const initialHtml = @json($record->getMeta('content_html', ''));
        const initialMainCss = @json($record->getMeta('main_css', ''));
        const initialSectionCss = @json($record->getMeta('section_css', ''));
        builder.loadHtml(initialHtml);
        builder.loadStyles(initialMainCss, initialSectionCss);

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

            const slug = @json($record->slug);
            if (!slug) return;


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

            //To update preview
            localStorage.setItem('preview-html', html);
            localStorage.setItem('preview-maincss', mainCss);
            localStorage.setItem('preview-sectioncss', sectionCss);

            try {
                const result = await @this.call('save', {
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
