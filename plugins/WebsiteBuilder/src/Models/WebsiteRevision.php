<?php

namespace WebsiteBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Plank\Metable\Metable;

class WebsiteRevision extends Model
{
    use Metable;

    protected $fillable = [
        'website_id',
        'user_id',
        'slug',
        'name',
        'meta',
    ];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
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
}
