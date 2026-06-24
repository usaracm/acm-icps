<x-filament-panels::page>
    <div x-data='{
        currentStep: 1,
        selectedTemplate: null,
        templates: @json($templates),
        loadingFinish: false,
        contentBuilderUrl: null,
        nextStep() {
            if (this.currentStep < 3) this.currentStep++;
            window.scrollTo({ top: 0 });
        },
        prevStep() {
            if (this.currentStep > 1) this.currentStep--;
            window.scrollTo({ top: 0 });
        },
        selectTemplate(templateId) {
            this.selectedTemplate = templateId;
        },
        async finish() {
            this.loadingFinish = true;
            const data = await $wire.completeOnboarding(this.selectedTemplate);
            this.contentBuilderUrl = data.redirect;
            this.loadingFinish = false;
            this.nextStep();
        }
    }'
        class="max-w-6xl mx-auto w-full">
        <link rel="stylesheet" href="{{ \App\Facades\Plugin::getPlugin('WebsiteBuilder')->asset('build/app.css') }}">

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center gap-3 max-w-4xl mx-auto w-full">
                <div class="flex items-center" :class="currentStep >= 1 ? 'opacity-100' : 'opacity-50'">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all"
                        :class="currentStep >= 1 ? 'bg-primary-600 border-primary-600 text-white' :
                            'border-gray-300 text-gray-500'">
                        <svg x-show="currentStep > 1" class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <span x-show="currentStep === 1" class="text-sm font-semibold">1</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold text-gray-900">Welcome</p>
                    </div>
                </div>
                <div class="flex-auto h-1 mx-2" :class="currentStep >= 2 ? 'bg-primary-600' : 'bg-gray-300'"></div>
                <div class="flex items-center" :class="currentStep >= 2 ? 'opacity-100' : 'opacity-50'">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all"
                        :class="currentStep >= 2 ? 'bg-primary-600 border-primary-600 text-white' :
                            'border-gray-300 text-gray-500'">
                        <svg x-show="currentStep > 2" class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <span x-show="currentStep <= 2" class="text-sm font-semibold">2</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold text-gray-900">Your First Page</p>
                    </div>
                </div>
                <div class="flex-auto h-1 mx-2" :class="currentStep >= 3 ? 'bg-primary-600' : 'bg-gray-300'"></div>
                <div class="flex items-center" :class="currentStep >= 3 ? 'opacity-100' : 'opacity-50'">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all"
                        :class="currentStep >= 3 ? 'bg-primary-600 border-primary-600 text-white' :
                            'border-gray-300 text-gray-500'">
                        <svg x-show="currentStep > 3" class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <span x-show="currentStep <= 3" class="text-sm font-semibold">3</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold text-gray-900">Complete</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 1: Welcome -->
        <div x-show="currentStep === 1" x-transition class="space-y-6">
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary-100 mb-6">
                    <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Welcome to Website Builder</h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-8">
                    Let's create your conference website in just a few steps. This quick setup will help you get started
                    with a professional-looking site in minutes.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto mt-12">
                    <div class="p-6 rounded-lg border border-gray-200 bg-white">
                        <div
                            class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mb-4 mx-auto">
                            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Professional Templates</h3>
                        <p class="text-sm text-gray-600">Choose from beautiful pre-designed templates
                        </p>
                    </div>
                    <div class="p-6 rounded-lg border border-gray-200 bg-white">
                        <div
                            class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mb-4 mx-auto">
                            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Easy Customization</h3>
                        <p class="text-sm text-gray-600">Drag and drop content builder included</p>
                    </div>
                    <div class="p-6 rounded-lg border border-gray-200 bg-white">
                        <div
                            class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mb-4 mx-auto">
                            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Quick Setup</h3>
                        <p class="text-sm text-gray-600">Get your site running in minutes</p>
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button @click="nextStep()" type="button"
                    class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-colors">
                    Get Started
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Step 2: Choose Template -->
        <div x-show="currentStep === 2" x-transition class="space-y-6">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Create Your First page</h2>
                <p class="text-gray-600">Select a template that best fits your conference style</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <template x-if="!loadingFinish">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
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

                    <!-- Jika templates KOSONG -->
                    <template x-if="!templates || templates.length === 0">
                        <div class="col-span-full text-center py-10 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="mx-auto mb-4 w-12 h-12">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>

                            <p class="text-sm">Something was Wrong. Please try again later.</p>
                        </div>
                    </template>
                </template>
                <div x-show="loadingFinish"
                    class="col-span-1 md:col-span-2 flex items-center justify-center p-6 my-20">
                    <div role="status" aria-live="polite"
                        class="w-full max-w-2xl bg-white/90 backdrop-blur-sm border border-gray-100 rounded-lg shadow p-6 flex items-center gap-4">
                        <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900">Creating your page</h3>
                            <p class="text-sm text-gray-600 mt-1 flex items-center gap-3">
                                <span class="truncate"
                                    x-text="(templates.find(t => t.id === selectedTemplate) || {}).name || 'Using your selected template'"></span>
                                <span class="inline-flex items-center ml-1 text-gray-400" aria-hidden="true">
                                    <span class="dots">
                                        <span class="dot">.</span><span class="dot">.</span><span
                                            class="dot">.</span>
                                    </span>
                                </span>
                            </p>
                        </div>
                    </div>

                    <style>
                        .dots {
                            display: inline-flex;
                            gap: 0.18rem;
                        }

                        .dot {
                            display: inline-block;
                            opacity: 0;
                            transform: translateY(0);
                            animation: dot 1s infinite;
                            font-weight: 700;
                            color: #374151;
                        }

                        .dot:nth-child(1) {
                            animation-delay: 0s;
                        }

                        .dot:nth-child(2) {
                            animation-delay: 0.15s;
                        }

                        .dot:nth-child(3) {
                            animation-delay: 0.3s;
                        }

                        @keyframes dot {
                            0% {
                                opacity: 0;
                                transform: translateY(0);
                            }

                            50% {
                                opacity: 1;
                                transform: translateY(-3px);
                            }

                            100% {
                                opacity: 0;
                                transform: translateY(0);
                            }
                        }
                    </style>
                </div>
            </div>
            <div class="flex justify-between pt-6">
                <button @click="prevStep()" type="button"
                    class="inline-flex items-center px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                    </svg>
                    Back
                </button>
                <button @click="finish()" type="button" :disabled="selectedTemplate === null"
                    class="inline-flex items-center px-6 py-3 font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed bg-primary-600 hover:bg-primary-700 text-white">
                    Continue
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Step 3: Complete -->
        <div x-show="currentStep === 3" x-transition class="space-y-6">
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary-100 mb-6">
                    <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Setup Complete</h2>
                <p class="text-gray-600 mb-6">Your conference website is ready. You can visit it or return to the
                    Website Builder to make further changes.</p>

                <div class="flex justify-center gap-4 mt-5">
                    <a href="{{ route('livewirePageGroup.scheduledConference.pages.home') }}"
                        class="inline-flex items-center px-6 py-3 bg-white border border-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-50 transition">
                        Visit your website
                    </a>

                    <a :href="contentBuilderUrl"
                        class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition">
                        Open Website Builder
                    </a>
                </div>

                <p class="text-sm text-gray-500 mt-4">You'll be able to access both links anytime from the admin menu.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
