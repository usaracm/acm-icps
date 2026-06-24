<?php

namespace WebsiteBuilder\Models;

use App\Models\Concerns\BelongsToScheduledConference;
use Illuminate\Database\Eloquent\Model;
use Plank\Metable\Metable;

class WebsiteWidget extends Model
{
    use BelongsToScheduledConference, Metable;

    protected $fillable = [
        'name',
    ];

}
