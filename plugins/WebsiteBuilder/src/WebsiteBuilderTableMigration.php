<?php

namespace WebsiteBuilder;

use App\Models\ScheduledConference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use WebsiteBuilder\Models\Website;
use WebsiteBuilder\Models\WebsiteWidget;

class WebsiteBuilderTableMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('websites')) {
            Schema::create('websites', function (Blueprint $table) {
                $table->id();
                $table->foreignIdFor(ScheduledConference::class)->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->boolean('is_published')->default(false);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('website_widgets') && !Schema::hasTable('website_components')) {
            Schema::create('website_widgets', function (Blueprint $table) {
                $table->id();
                $table->foreignIdFor(ScheduledConference::class)->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('website_revisions')) {
            Schema::create('website_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignIdFor(Website::class)->constrained()->cascadeOnDelete();
                $table->foreignIdFor(\App\Models\User::class)->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->timestamps();
            });
        }

        $this->update();
    }

    // update existing tables to match new structure
    public function update()
    {
        if (Schema::hasTable('websites')) {
            // rename column if exists
            if (Schema::hasColumn('websites', 'is_active')) {
                Schema::table('websites', function (Blueprint $table) {
                    $table->renameColumn('is_active', 'is_published');
                });
            }

            // ensure default is false
            Schema::table('websites', function (Blueprint $table) {
                $table->boolean('is_published')->default(false)->change();
            });

            // drop 'meta' column if exists
            if (Schema::hasColumn('websites', 'meta')) {
                // migrate old data to metable
                \Illuminate\Support\Facades\DB::table('websites')
                    ->whereNotNull('meta')
                    ->orderBy('id')
                    ->chunkById(100, function ($rows) {
                        foreach ($rows as $row) {
                            $meta = json_decode($row->meta, true);
                            if ($meta) {
                                $website = Website::withoutGlobalScopes()->find($row->id);
                                if (!$website) {
                                    continue;
                                }
                                $website->setManyMeta($meta);
                                $website->save();
                            }
                        }
                    });

                Schema::table('websites', function (Blueprint $table) {
                    $table->dropColumn('meta');
                });
            }
        }

        if (Schema::hasTable('website_revisions')) {
            // drop 'meta' column if exists
            if (Schema::hasColumn('website_revisions', 'meta')) {
                // migrate old data to metable
                \Illuminate\Support\Facades\DB::table('website_revisions')
                    ->whereNotNull('meta')
                    ->orderBy('id')
                    ->chunkById(100, function ($rows) {
                        foreach ($rows as $row) {
                            $meta = json_decode($row->meta, true);
                            if ($meta) {
                                $revision = \WebsiteBuilder\Models\WebsiteRevision::find($row->id);
                                if (!$revision) {
                                    continue;
                                }
                                $revision->setManyMeta($meta);
                                $revision->save();
                            }
                        }
                    });

                Schema::table('website_revisions', function (Blueprint $table) {
                    $table->dropColumn('meta');
                });
            }
        }

        // rename 'website_components' to 'website_widgets' if exists and migrate data to metable
        if (Schema::hasTable('website_components') && !Schema::hasTable('website_widgets')) {
            Schema::rename('website_components', 'website_widgets');
        }

        if (
            Schema::hasTable('website_widgets') &&
            Schema::hasColumn('website_widgets', 'content_html') &&
            Schema::hasColumn('website_widgets', 'main_css') &&
            Schema::hasColumn('website_widgets', 'section_css')
        ) {
            DB::table('website_widgets')
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        $meta = [
                            'content_html' => $row->content_html ?? null,
                            'main_css' => $row->main_css ?? null,
                            'section_css' => $row->section_css ?? null,
                        ];

                        WebsiteWidget::withoutGlobalScopes()->find($row->id)->setManyMeta($meta);
                    }
                });
            Schema::dropColumns('website_widgets', ['content_html', 'main_css', 'section_css']);
        }

        // migrate content_html from website table to metable
        if (Schema::hasTable('websites') && Schema::hasColumn('websites', 'content_html')) {
            DB::table('websites')
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        $website = Website::withoutGlobalScopes()->find($row->id);
                        if (!$website) {
                            continue;
                        }
                        $website->setMeta('content_html', $row->content_html);
                    }
                });

            Schema::dropColumns('websites', ['content_html']);
        }

        // migrate content_html from website_revisions table to metable
        if (Schema::hasTable('website_revisions') && Schema::hasColumn('website_revisions', 'content_html')) {
            DB::table('website_revisions')
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        $revision = \WebsiteBuilder\Models\WebsiteRevision::find($row->id);
                        if (!$revision) {
                            continue;
                        }
                        $revision->setMeta('content_html', $row->content_html);
                    }
                });

            Schema::dropColumns('website_revisions', ['content_html']);
        }
    }
}
