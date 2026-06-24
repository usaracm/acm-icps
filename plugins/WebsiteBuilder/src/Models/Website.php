<?php

namespace WebsiteBuilder\Models;

use App\Models\Concerns\BelongsToScheduledConference;
use Illuminate\Database\Eloquent\Model;
use Plank\Metable\Metable;

class Website extends Model
{
    use BelongsToScheduledConference, Metable;

    protected $fillable = [
        'scheduled_conference_id',
        'slug',
        'name',
        'is_published',
        'is_default',
    ];

    // protected $casts = [
    //     'meta' => 'array',
    // ];

    public function revisions()
    {
        return $this->hasMany(WebsiteRevision::class);
    }

    public function getMetaAsStringHtmlTag()
    {
        $html = '';

        if ($this->getMeta('description')) {
            $html .= '<meta name="description" content="' . e($this->getMeta('description')) . '">' . "\n";
        }

        if ($this->getMeta('section_css')) {
            $html .= $this->getMeta('section_css') . "\n";
        }

        if ($this->getMeta('main_css')) {
            $html .= $this->getMeta('main_css') . "\n";
        }

        return $html;
    }

    public static function header()
    {
        return WebsiteWidget::where('name', 'header')->first();
    }

    public static function footer()
    {
        return WebsiteWidget::where('name', 'footer')->first();
    }
}
