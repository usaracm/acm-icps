<div>
    <style>
        :root {
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
            --text-main: #334155;
            --text-sub: #64748b;
            --primary-color: #3b82f6;
            --primary-bg: #eff6ff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --radius: 12px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 13px;
            color: var(--text-main);
            margin: 0;
            /* Padding atas lebih besar agar konten tidak tertutup filter bar */
            padding: 70px 20px 20px 20px;
            box-sizing: border-box;
        }

        /* --- FILTER BAR (Sticky) --- */
        .filter-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            padding: 12px 20px;
            z-index: 10;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            gap: 8px;
            overflow-x: auto;
            align-items: center;
        }

        .upload-card.drag-active {
            border: 2px dashed #4f46e5;
            background-color: rgba(79, 70, 229, 0.05);
        }


        /* Tombol Filter (Pill Shape) */
        .filter-btn {
            appearance: none;
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-sub);
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .filter-btn:hover {
            background: #f8fafc;
            color: var(--text-main);
            border-color: #cbd5e1;
        }

        .filter-btn.active {
            background: var(--text-main);
            /* Hitam/Abu tua modern */
            color: #fff;
            border-color: var(--text-main);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* --- GRID --- */
        #files {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 20px;
            width: 100%;
            padding-bottom: 40px;
        }

        .file-item {
            position: relative;
        }

        /* Class bantuan untuk menyembunyikan item via JS */
        .hidden-item {
            display: none !important;
        }

        /* --- CARD STYLES --- */
        .file-card {
            cursor: pointer;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            width: 100%;
            height: 100%;
            aspect-ratio: 1 / 1;
            border-radius: var(--radius);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: all 0.2s ease-in-out;
            box-shadow: var(--shadow-sm);
            padding: 0;
            outline: none;
        }

        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: #cbd5e1;
        }

        /* Upload Button Style */
        .upload-card {
            border: 2px dashed var(--border-color);
            background: #f8fafc;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            gap: 8px;
        }

        .upload-card:hover {
            border-color: var(--primary-color);
            background: var(--primary-bg);
        }

        .upload-card svg {
            width: 32px;
            height: 32px;
            transition: transform 0.2s;
        }

        .upload-card:hover svg {
            transform: scale(1.1);
        }

        .spinner {
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-left-color: var(--primary-color);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Preview Area */
        .preview-area {
            flex: 1;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            overflow: hidden;
        }

        .preview-area img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-area svg {
            width: 40px;
            height: 40px;
            color: var(--text-sub);
        }

        .file-info {
            height: 36px;
            padding: 0 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border-top: 1px solid var(--border-color);
            width: 100%;
            box-sizing: border-box;
        }

        .file-info span {
            font-size: 11px;
            color: var(--text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            text-align: center;
        }

        /* Delete Button */
        .btn-delete {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #fff;
            border: 1px solid #fee2e2;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            z-index: 20;
            color: #ef4444;
            padding: 0;
            transition: all 0.2s;
            opacity: 0;
            transform: scale(0.8);
        }

        .file-item:hover .btn-delete {
            opacity: 1;
            transform: scale(1);
        }

        .btn-delete:hover {
            background: #ef4444;
            color: #fff;
            border-color: #ef4444;
        }

        /* Dark Mode Support */
        body.dark {
            background: #0f172a;
        }

        body.dark .filter-container {
            background: rgba(15, 23, 42, 0.95);
            border-color: #334155;
        }

        body.dark .filter-btn {
            border-color: #334155;
            color: #94a3b8;
        }

        body.dark .filter-btn:hover {
            background: #1e293b;
            color: #fff;
        }

        body.dark .filter-btn.active {
            background: #3b82f6;
            border-color: #3b82f6;
            color: #fff;
        }

        body.dark .file-card {
            background: #1e293b;
            border-color: #334155;
        }

        body.dark .upload-card {
            background: #1e293b;
            border-color: #475569;
        }

        body.dark .upload-card:hover {
            background: #334155;
            border-color: var(--primary-color);
        }

        body.dark .file-info {
            background: #1e293b;
            border-top-color: #334155;
        }

        body.dark .file-info span {
            color: #e2e8f0;
        }

        body.dark .preview-area {
            background: #0f172a;
        }
    </style>

    <div class="filter-container">
        <button type="button" class="filter-btn active" onclick="filterAssets('all', this)">All</button>
        <button type="button" class="filter-btn" onclick="filterAssets('image', this)">Images</button>
        <button type="button" class="filter-btn" onclick="filterAssets('video', this)">Videos</button>
        <button type="button" class="filter-btn" onclick="filterAssets('audio', this)">Audio</button>
    </div>

    <div id="files">
        <div class="file-item upload-item" wire:key="assets-upload-item">
            <label class="file-card upload-card" id="upload-label">
                <input type="file" id="file-input" onchange="uploadFile(event)" style="display:none"
                    accept="image/*,video/*,audio/*">

                <div class="upload-content" style="display: flex; flex-direction: column; align-items: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11 11V5H13V11H19V13H13V19H11V13H5V11H11Z"></path>
                    </svg>
                    <span>Upload</span>
                </div>

                <div class="upload-loading"
                    style="display: none; flex-direction: column; align-items: center; gap: 5px;">
                    <div class="spinner"></div>
                    <span style="font-size: 11px; color: var(--text-sub);">Uploading...</span>
                </div>
            </label>
        </div>


        @foreach ($files as $file)
            <div class="file-item asset-item" data-type="{{ $file['type'] }}" wire:key="asset-{{ $file['id'] }}">

                <button type="button" wire:click="deleteMedia({{ $file['id'] }})" class="btn-delete" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="currentColor">
                        <path
                            d="M17 6H22V8H20V21C20 21.5523 19.5523 22 19 22H5C4.44772 22 4 21.5523 4 21V8H2V6H7V3C7 2.44772 7.44772 2 8 2H16C16.5523 2 17 2.44772 17 3V6ZM18 8H6V20H18V8ZM9 11H11V17H9V11ZM13 11H15V17H13V11ZM9 4V6H15V4H9Z">
                        </path>
                    </svg>
                </button>

                @php
                    $isImage = str_starts_with($file['type'], 'image/');
                    $isVideo = str_starts_with($file['type'], 'video/');
                    $isAudio = str_starts_with($file['type'], 'audio/');
                    $url = $file['url'];
                    $name = basename($file['url']);
                @endphp

                <button type="button" class="file-card asset-btn" data-href="{{ $url }}">
                    <div class="preview-area">
                        @if ($isImage)
                            <img src="{{ $url }}" loading="lazy" alt="{{ $name }}" />
                        @elseif ($isVideo)
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M16 4C16.5523 4 17 4.44772 17 5V6.17071L21.2929 1.87782L22.7071 3.29203L18.4142 7.58492C18.7841 8.32431 19 9.14178 19 10V18C19 18.5523 18.5523 19 18 19H4C3.44772 19 3 18.5523 3 18V5C3 4.44772 3.44772 4 4 4H16ZM11 8L8 14H14L11 8Z">
                                </path>
                            </svg>
                        @elseif ($isAudio)
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 13.5352V3H20V5H14V13.5352C13.5117 13.1952 12.8356 13 12 13C10.3431 13 9 14.3431 9 16C9 17.6569 10.3431 19 12 19C13.6569 19 15 17.6569 15 16V13.5352Z">
                                </path>
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M16 2L21 7V21.0082C21 21.556 20.5551 22 20.0066 22H3.9934C3.44476 22 3 21.5447 3 21.0082V2.9918C3 2.44405 3.44495 2 3.9934 2H16ZM11 11H5V13H11V11ZM13 11V13H19V11H13ZM11 7H5V9H11V7ZM13 7V9H19V7H13ZM11 15H5V17H11V15ZM13 15V17H19V15H13Z">
                                </path>
                            </svg>
                        @endif
                    </div>
                    <div class="file-info">
                        <span title="{{ $name }}">{{ $name }}</span>
                    </div>
                </button>
            </div>
        @endforeach
    </div>

    <script>
        // --- JS FILTER FUNCTION ---
        function filterAssets(type, btnElement) {
            // 1. Update UI tombol aktif
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            if (btnElement) btnElement.classList.add('active');

            // 2. Ambil semua item file
            const items = document.querySelectorAll('.asset-item');

            // 3. Loop dan sembunyikan/tampilkan sesuai data-type
            items.forEach(item => {
                const itemType = item.getAttribute('data-type') || '';
                let shouldShow = false;

                if (type === 'all') {
                    shouldShow = true;
                } else if (type === 'image' && itemType.startsWith('image/')) {
                    shouldShow = true;
                } else if (type === 'video' && itemType.startsWith('video/')) {
                    shouldShow = true;
                } else if (type === 'audio' && itemType.startsWith('audio/')) {
                    shouldShow = true;
                }

                if (shouldShow) {
                    item.classList.remove('hidden-item');
                } else {
                    item.classList.add('hidden-item');
                }
            });
        }

        // --- UPLOAD FUNCTION (Sama seperti sebelumnya) ---
        async function uploadFile(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Simpan referensi element agar aman
            const inputElement = e.target;
            const contentDiv = document.querySelector('.upload-content');
            const loadingDiv = document.querySelector('.upload-loading');

            if (contentDiv) contentDiv.style.display = 'none';
            if (loadingDiv) loadingDiv.style.display = 'flex';

            try {
                const content = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = () => resolve(reader.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                });

                const result = await @this.call('uploadFile', content);

                if (result?.success) {
                    try {
                        localStorage.setItem('websitebuilder-assets-updated', String(Date.now()));
                    } catch (e) {}
                    inputElement.value = '';
                } else {
                    // Kembalikan tampilan ke semula jika error
                    if (contentDiv) contentDiv.style.display = 'flex';
                    if (loadingDiv) loadingDiv.style.display = 'none';
                    inputElement.value = '';
                }
            } catch (error) {
                console.error('Upload Error:', error);
                alert('An error occurred during upload.');
                if (contentDiv) contentDiv.style.display = 'flex';
                if (loadingDiv) loadingDiv.style.display = 'none';
                inputElement.value = '';
            }
        }

        // --- EVENT HANDLER PILIH ASSET ---
        document.addEventListener('click', function(e) {
            const elm = e.target.closest('.asset-btn');
            if (!elm || e.target.closest('.btn-delete')) return;

            let url = elm.getAttribute('data-href');
            if (!url) {
                const img = elm.querySelector('img');
                if (img) url = img.getAttribute('src');
            }

            if (url) {
                if (typeof parent.selectAsset === 'function') {
                    parent.selectAsset(url);
                    parent.focus();
                }
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);

        // --- PARENT STYLE SYNC ---
        function applyParentStyles() {
            var cssString = `
                body { background: ${parent._cb?.styleModalBackground || 'transparent'}; }
                .dark * { scrollbar-width: thin; scrollbar-color: rgb(78 78 78 / 62%) auto; }
                .dark *::-webkit-scrollbar { width: 12px; }
                .dark *::-webkit-scrollbar-track { background: transparent; }
                .dark *::-webkit-scrollbar-thumb { background-color:rgb(78 78 78 / 62%); }
            `;
            let themestyle = document.querySelector('[data-theme-style]');
            if (themestyle) themestyle.parentNode.removeChild(themestyle);
            var style = document.createElement("style");
            style.setAttribute('data-theme-style', '');
            style.type = "text/css";
            style.innerHTML = cssString;
            document.getElementsByTagName("head")[0].appendChild(style);

            if (parent._cb?.styleDark) document.body.classList.add('dark');
            else if (parent._cb?.styleColored) document.body.classList.add('colored');
            else if (parent._cb?.styleColoredDark) document.body.classList.add('colored-dark');
            else if (parent._cb?.styleLight) document.body.classList.add('light');
        }
        try {
            applyParentStyles();
        } catch (e) {}

        const handleKeyDown = (e) => {
            if (e.keyCode === 27) parent.closeAsset();
        }
        document.addEventListener('keydown', handleKeyDown);

        if (!window.__websiteBuilderAssetsPickerStorageListener) {
            window.__websiteBuilderAssetsPickerStorageListener = true;
            window.addEventListener('storage', (e) => {
                if (e.key === 'websitebuilder-assets-updated') window.location.reload();
            });
        }


        // --- Drag & drop file ---
        const uploadLabel = document.getElementById('upload-label');
        const fileInput = document.getElementById('file-input');

        // Mencegah behavior default browser
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadLabel.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        // Efek visual saat drag
        uploadLabel.addEventListener('dragover', () => {
            uploadLabel.classList.add('drag-active');
        });

        uploadLabel.addEventListener('dragleave', () => {
            uploadLabel.classList.remove('drag-active');
        });

        // Saat file di-drop
        uploadLabel.addEventListener('drop', (e) => {
            uploadLabel.classList.remove('drag-active');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;

                // Trigger fungsi upload yang sama
                uploadFile({
                    target: fileInput
                });
            }
        });
    </script>
</div>
