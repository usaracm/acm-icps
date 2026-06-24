<?php

namespace WebsiteBuilder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Facades\Plugin;
use Illuminate\Database\Seeder;
use WebsiteBuilder\Models\Website;
use WebsiteBuilder\Models\WebsiteWidget;

class WebsiteBuilderTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run($scheduledConferenceId): void
    {
        $plugin = Plugin::getPlugin('WebsiteBuilder');

        $logoUrl = app()->getCurrentScheduledConference()->getFirstMedia('logo')?->getAvailableUrl(['thumb', 'thumb-xl']) ?? app()->getCurrentConference()->getFirstMedia('logo')?->getAvailableUrl(['thumb', 'thumb-xl']) ?? $plugin->asset('assets/templates-simple/images/logo.png');
        $logoUrl ??= asset('logo.png');
        if (!$plugin->getWebsiteHeader()) {
            $plugin->updateSetting(
                'website_header',
                [
                    'content_html' => '<div class="is-section is-box is-section-auto nav-menu type-system-ui">
                            <div class="is-overlay">
                                <div class="is-overlay-content">
                                    <div data-cb-type="nav-menu@1.0.0" data-cb-logo="My Website" data-cb-loading="fade" style="--menu-item-border-radius: 6px; --dropdown-border-radius: 8px;">
                                        <style>
                                            [data-cb-type="nav-menu@1.0.0"]:not([data-cb-loaded]) {
                                                opacity: 0;
                                                visibility: hidden;
                                                transition: none;
                                            }

                                            [data-cb-type="nav-menu@1.0.0"]:not([data-cb-loaded]) .nav-link {
                                                transition: none;
                                            }
                                        </style>
                                        <div class="nav-wrapper">
                                            <nav>
                                                <div class="nav-container">
                                                    <a href="/" class="logo">
                                                        <img src="' . $logoUrl . '" alt="My Website" class="logo-img rounded">
                                                    </a>

                                                    <button class="menu-toggle" aria-label="Toggle navigation menu" aria-expanded="false">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <line x1="3" y1="12" x2="21" y2="12"></line>
                                                            <line x1="3" y1="6" x2="21" y2="6"></line>
                                                            <line x1="3" y1="18" x2="21" y2="18"></line>
                                                        </svg>
                                                    </button>

                                                    <ul class="nav-list" role="menubar">
                                                        <li class="nav-item" role="none">
                                                            <a href="' . route('livewirePageGroup.scheduledConference.pages.home') . '" class="nav-link" role="menuitem">Home</a>
                                                        </li>
                                                        <li class="nav-item" role="none">
                                                            <a href="' . route('livewirePageGroup.scheduledConference.pages.about') . '" class="nav-link" role="menuitem">About</a>
                                                        </li>
                                                        <li class="nav-item" role="none">
                                                            <a href="' . route('livewirePageGroup.scheduledConference.pages.announcements') . '" class="nav-link" role="menuitem">Announcements</a>
                                                        </li>
                                                        <li class="nav-item" role="none">
                                                            <a href="' . route('livewirePageGroup.scheduledConference.pages.login') . '" class="nav-link" role="menuitem">Login</a>
                                                        </li>
                                                        <li class="nav-item" role="none">
                                                            <a href="' . route('livewirePageGroup.scheduledConference.pages.register') . '" class="nav-link" role="menuitem">Register</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <link data-name="contentstyle" data-class="type-system-ui" href="' . $plugin->asset('assets/styles/type-system-ui.css') . '" rel="stylesheet">',
                    'main_css' => '',
                    'section_css' => '<link data-name="contentstyle" data-class="type-system-ui" href="' . $plugin->asset('assets/styles/type-system-ui.css') . '" rel="stylesheet">'
                ]
            );
        }

        if (!$plugin->getWebsiteFooter()) {
            $plugin->updateSetting('website_footer', [
                'content_html' => '<div class="is-section is-box is-dark-text is-static is-section-50 box-autofit type-poppins">
                        <div class="is-overlay"></div>
                        <div class="is-container v2 is-content-1100 size-16 leading-13">
                            <div class="row">
                                <div class="column center">
                                    <h2 class="size-32 is-title1-32 is-title-lite font-light tracking-wide">COMPANY NAME</h2>
                                    <p>
                                        12345 Street Name, City. State 12345<br>
                                        P: (123) 456 7890 / 456 7891
                                    </p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="column center">
                                    <div class="is-social size-21" style="margin: 20px 0">
                                        <a href="https://twitter.com/"><i class="icon ion-social-twitter" style="margin-right: 2em"></i></a>
                                        <a href="https://www.facebook.com/"><i class="icon ion-social-facebook" style="margin-right: 2em"></i></a>
                                        <a href="https://www.instagram.com/"><i class="icon ion-social-instagram-outline" style="margin-right: 2em"></i></a>
                                        <a href="mailto:you@example.com"><i class="icon ion-android-drafts"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="column center">
                                    <p class="size-14">© Copyright 2026 Company Name</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <link data-name="contentstyle" data-class="type-poppins" href="' . $plugin->asset('assets/styles/type-poppins.css') . '" rel="stylesheet">',
                'main_css' => '',
                'section_css' => '<link data-name="contentstyle" data-class="type-poppins" href="' . $plugin->asset('assets/styles/type-poppins.css') . '" rel="stylesheet">',
            ]);
        }

    }
}
