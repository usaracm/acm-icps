
(function (window) {
    'use strict';
    console.log('Global Components Script: Loading...');

    class GlobalComponents {
        constructor(builder, config) {
            console.log('Global Components: Initializing...');
            this.builder = builder;
            this.config = config || {};
            this.lastSelectedElement = null;
            // config.endpoints should contain { list, create, update }

            this.injectStyles();
            this.addButtons();
            this.startSelectionMonitor();

            window.GlobalComponentsInstance = this;
        }

        injectStyles() {
            const style = document.createElement('style');
            style.textContent = `
                .gc-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; border: 1px solid #ccc; z-index: 10000; box-shadow: 0 4px 20px rgba(0,0,0,0.15); width: 400px; font-family: sans-serif; }
                .gc-modal h3 { margin-top: 0; color: #333; }
                .gc-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; }
                .gc-btn { padding: 8px 15px; cursor: pointer; background: #3b82f6; color: white; border: none; margin-right: 5px; border-radius: 4px; transition: background 0.2s;}
                .gc-btn:hover { background: #2563eb; }
                .gc-btn-cancel { background: #9ca3af; }
                .gc-btn-cancel:hover { background: #6b7280; }
                .gc-list-item { padding: 12px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
                .gc-list-item:hover { background: #f8f9fa; }
                .gc-actions { display: flex; gap: 5px; }
            `;
            document.head.appendChild(style);
        }

        addButtons() {
            console.log('Global Components: Adding buttons...');
            // Save button
            this.builder.addButton({
                pos: 4,
                title: 'Save as Global Component',
                html: '<svg class="is-icon-flex" viewBox="0 0 24 24" style="width:14px;height:14px;fill:rgba(0,0,0,0.7)"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
                onClick: () => this.promptCreateComponent()
            });

            // List button
            this.builder.addButton({
                pos: 7,
                title: 'Insert Global Component',
                html: '<svg class="is-icon-flex" viewBox="0 0 24 24" style="width:14px;height:14px;fill:rgba(0,0,0,0.7)"><path d="M4 6h18V4H4c-1.1 0-2 .9-2 2v11H0v3h14v-3H4V6zm19 2h-6c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V9c0-.55-.45-1-1-1zm-1 9h-4v-7h4v7z"/></svg>',
                onClick: () => this.showComponentList()
            });
        }

        getActiveElement() {
            const iframe = this.builder.iframe;
            const doc = iframe.contentDocument;

            console.log('Detecting active element...');
            console.log('Last selected element:', this.lastSelectedElement);

            // Prioritize lastSelectedElement from click tracking
            if (this.lastSelectedElement && doc.contains(this.lastSelectedElement)) {
                console.log('Using tracked last selected element');
                return this.lastSelectedElement;
            }

            // Try common ContentBox/ContentBuilder classes
            let el = doc.querySelector('.is-selected, .active, .is-active, .elm-active, [data-mode="active"]');
            if (el) {
                console.log('Found element with active class:', el);
                return el;
            }

            // Try to find a section or block element
            const sections = doc.querySelectorAll('.is-section, .is-box, section, [class*="section"], [class*="block"]');
            if (sections.length > 0) {
                console.log('Found sections, returning first:', sections[0]);
                return sections[0];
            }

            console.log('No element found');
            return null;
        }

        async promptCreateComponent() {
            return el;
        }

        async promptCreateComponent() {
            const el = this.getActiveElement();
            if (!el) {
                alert('Please select a block/element first be clicking on it (ensure it is highlighted).');
                return;
            }

            const name = prompt('Enter a name for this global component:');
            if (!name) return;

            const html = el.outerHTML;

            try {
                const response = await fetch(this.config.endpoints.create, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({
                        name: name,
                        html_content: html
                    })
                });

                if (!response.ok) throw new Error('Failed to create');
                const component = await response.json();

                // Mark the element
                el.setAttribute('data-global-component-id', component.id);
                el.setAttribute('data-global-component-name', component.name);

                alert(`Global Component "${name}" created!`);
            } catch (e) {
                console.error(e);
                alert('Error creating component: ' + e.message);
            }
        }

        async showComponentList() {
            try {
                console.log('Fetching components list from:', this.config.endpoints.list);
                const response = await fetch(this.config.endpoints.list);
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(`Failed to fetch: ${response.status} ${response.statusText} \n ${text}`);
                }
                const components = await response.json();
                console.log('Components loaded:', components);
                this.renderModal(components);
            } catch (e) {
                console.error(e);
                alert('Error loading components. Check console for details.');
            }
        }

        renderModal(components) {
            const overlay = document.createElement('div');
            overlay.className = 'gc-modal-overlay';

            const modal = document.createElement('div');
            modal.className = 'gc-modal';

            let listHtml = components.length === 0 ? '<p style="text-align:center;color:#666;">No components found.</p>' : '';

            listHtml += components.map(c => `
                <div class="gc-list-item">
                    <span>${c.name}</span>
                    <div class="gc-actions">
                         <button class="gc-btn" onclick="window.GlobalComponentsInstance.insertComponent('${c.id}')">Insert</button>
                    </div>
                </div>
            `).join('');

            // Allow storing components locally for quick access
            this.componentsCache = components;

            modal.innerHTML = `
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                    <h3>Global Components</h3>
                    <button class="gc-btn gc-btn-cancel" style="padding:4px 8px;font-size:12px;">X</button>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    ${listHtml}
                </div>
            `;

            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            const closeBtn = modal.querySelector('.gc-btn-cancel');
            closeBtn.onclick = () => document.body.removeChild(overlay);
        }

        insertComponent(id) {
            const component = this.componentsCache.find(c => c.id == id);
            if (!component) return;

            // Insert HTML
            // ContentBox usually adds to bottom or current selection.
            // We'll wrap the HTML to ensure attributes are present.

            // Check if HTML already has the attribute (it should if they saved it that way).
            // If not (e.g. migration), add it.
            let html = component.html_content;
            if (!html.includes('data-global-component-id')) {
                // Naive injection
                html = html.replace(/^<([\w-]+)/, `<$1 data-global-component-id="${component.id}" data-global-component-name="${component.name}"`);
            }

            // ContentBox specific insertion
            // Assuming builder.loadHtml(html) REPLACES everything
            // builder.addHtml(html) usually appends module
            // If ContentBox API exposes `addHtml`?
            /*
               Based on symbols/plugin.js:
               doc.write(html) into iframe? No.
               The user uses generic ContentBox.
               Usually `builder.box` or similar.
               Looking at symbols plugin again: it adds specific symbols via iframe?

               Let's try appending a snippet.
               There is likely a `_cb.addSnippet?` No.
            */

            // I'll try just inserting into the iframe document at the end of the container.
            const doc = this.builder.iframe.contentDocument;
            const wrapper = doc.querySelector('.is-wrapper') || doc.body;

            // Create a template
            const temp = document.createElement('div');
            temp.innerHTML = html;
            const node = temp.firstElementChild;

            if (node) {
                // Ensure attributes
                node.setAttribute('data-global-component-id', component.id);
                node.setAttribute('data-global-component-name', component.name);
                wrapper.appendChild(node);
                alert('Component inserted at the bottom.');
            }

            // Close modal
            const overlay = document.querySelector('.gc-modal-overlay');
            if (overlay) document.body.removeChild(overlay);
        }

        startSelectionMonitor() {
            const check = setInterval(() => {
                if (this.builder.iframe && this.builder.iframe.contentDocument) {
                    clearInterval(check);
                    this.setupListeners();
                    this.refreshAllInstancesOnLoad();
                }
            }, 1000);
        }

        async refreshAllInstancesOnLoad() {
            try {
                const response = await fetch(this.config.endpoints.list);
                if (!response.ok) return;
                const components = await response.json();
                this.componentsCache = components; // Cache them

                const doc = this.builder.iframe.contentDocument;
                components.forEach(c => {
                    const instances = doc.querySelectorAll(`[data-global-component-id="${c.id}"]`);
                    instances.forEach(el => {
                        let newHtml = c.html_content;
                        if (!newHtml.includes(`data-global-component-id="${c.id}"`)) {
                            newHtml = newHtml.replace(/^<([\w-]+)/, `<$1 data-global-component-id="${c.id}" data-global-component-name="${c.name}"`);
                        }
                        // Normalize to compare?
                        // For now, blind update is safer to ensure latest version
                        el.outerHTML = newHtml;
                    });
                });
                console.log('Global components refreshed.');
            } catch (e) { console.error('Auto-refresh components failed', e); }
        }

        setupListeners() {
            const doc = this.builder.iframe.contentDocument;

            // Track clicks to save last selected element
            doc.addEventListener('click', (e) => {
                let target = e.target;

                // Find the closest block-level element
                const blockElement = target.closest('.is-section, .is-box, .is-block, section, [class*="section"], [class*="block"]') || target;

                // Save as last selected
                this.lastSelectedElement = blockElement;
                console.log('Element clicked and saved:', blockElement);

                // Check if it's a global component
                while (target && target !== doc.body) {
                    if (target.hasAttribute('data-global-component-id')) {
                        this.showEditControl(target);
                        break;
                    }
                    target = target.parentElement;
                }
            });
        }

        showEditControl(element) {
            // Remove existing controls
            const existing = this.builder.iframe.contentDocument.querySelector('.gc-edit-control');
            if (existing) existing.remove();

            const id = element.getAttribute('data-global-component-id');
            const name = element.getAttribute('data-global-component-name');

            const btn = document.createElement('button');
            btn.className = 'gc-edit-control';
            btn.innerText = `Update Global Component: ${name}`;
            Object.assign(btn.style, {
                position: 'absolute',
                zIndex: 1000,
                background: '#2563eb',
                color: 'white',
                border: 'none',
                padding: '5px 10px',
                fontSize: '12px',
                cursor: 'pointer',
                top: (element.getBoundingClientRect().top + window.scrollY) + 'px',
                left: (element.getBoundingClientRect().left + window.scrollX) + 'px'
            });

            btn.onclick = (e) => {
                e.stopPropagation();
                e.preventDefault();
                if (confirm(`Update "${name}" and all usage occurrences?`)) {
                    this.updateComponent(id, element.outerHTML);
                }
            };

            this.builder.iframe.contentDocument.body.appendChild(btn);

            // Remove button when selection changes (click elsewhere)
            const clearer = () => {
                btn.remove();
                this.builder.iframe.contentDocument.removeEventListener('click', clearer);
            };
            setTimeout(() => {
                this.builder.iframe.contentDocument.addEventListener('click', clearer);
            }, 100);
        }

        async updateComponent(id, html) {
            try {
                // Update via API
                // URL: use replace on a template URL from config
                const url = this.config.endpoints.update.replace('ID_PLACEHOLDER', id);

                const response = await fetch(url, {
                    method: 'PUT', // Route defined as PUT
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ html_content: html })
                });

                if (!response.ok) throw new Error('Update failed');

                // Refresh all instances
                const doc = this.builder.iframe.contentDocument;
                const instances = doc.querySelectorAll(`[data-global-component-id="${id}"]`);
                instances.forEach(el => {
                    el.outerHTML = html;
                });

                alert('Component updated successfully!');

            } catch (e) {
                console.error(e);
                alert('Failed to update component.');
            }
        }
    }

    window.GlobalComponentsManager = GlobalComponents;

})(window);
