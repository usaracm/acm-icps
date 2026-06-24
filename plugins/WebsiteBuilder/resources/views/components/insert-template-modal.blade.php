<div x-data='{
    selectedTemplate: null,
    templates: @json($templates),
    loading: false,
    selectTemplate(templateId) {
        this.selectedTemplate = templateId;
        $wire.set("data.templateId", templateId);
    },
}'
    class="p-6">
    <link rel="stylesheet" href="{{ \App\Facades\Plugin::getPlugin('WebsiteBuilder')->asset('build/app.css') }}">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-h-[60vh] overflow-y-auto pr-2">
        <!-- Custom/Blank Template -->
        <div @click="selectTemplate('custom')"
            class="cursor-pointer rounded-lg border-2 transition-all overflow-hidden group"
            :class="selectedTemplate === 'custom'
                ?
                'border-primary-600 shadow-lg' :
                'border-gray-200 hover:border-primary-400'">

            <!-- SAME IMAGE WRAPPER STYLE -->
            <div class="relative aspect-[4/3] bg-gray-100">
                <div class="absolute inset-0 flex items-center justify-center">
                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>

                <!-- CHECKMARK -->
                <div x-show="selectedTemplate === 'custom'"
                    class="absolute top-2 right-2 bg-primary-600 text-white rounded-full p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                        </path>
                    </svg>
                </div>
            </div>

            <!-- CONTENT -->
            <div class="p-4 bg-white">
                <h3 class="font-semibold text-gray-900">Custom (Blank)</h3>
            </div>
        </div>

        <!-- Other Templates -->
        <template x-for="template in templates" :key="template.id">
            <div @click="selectTemplate(template.id)"
                class="cursor-pointer rounded-lg border-2 transition-all overflow-hidden group"
                :class="selectedTemplate === template.id ?
                    'border-primary-600 shadow-lg' :
                    'border-gray-200 hover:border-primary-400'">

                <!-- IMAGE WRAPPER -->
                <div class="relative aspect-[4/3] bg-gray-100">
                    <img :src="template.thumbnail" :alt="template.name"
                        class="absolute inset-0 w-full h-full object-cover object-center">

                    <div x-show="selectedTemplate === template.id"
                        class="absolute top-2 right-2 bg-primary-600 text-white rounded-full p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>
                </div>

                <!-- CONTENT -->
                <div class="p-4 bg-white">
                    <h3 class="font-semibold text-gray-900 mb-1" x-text="template.name">
                    </h3>
                </div>
            </div>
        </template>
    </div>

    @error('data.templateId')
        <div class="text-danger-600 text-sm mt-2">
            You must select a template.
        </div>
    @enderror
</div>
