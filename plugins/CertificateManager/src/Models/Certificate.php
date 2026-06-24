<?php

namespace CertificateManager\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Plank\Metable\Metable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Certificate extends Model implements HasMedia
{
    use Metable, InteractsWithMedia;

    protected $fillable = [
        'certificate_template_id',
        'email',
        'number',
        'certifiable_type',
        'certifiable_id'
    ];

    protected $casts = [];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (Certificate $certificate) {
            $certificate->template->setMeta('number', $certificate->template->getMeta('number') + 1);
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }

    public function certifiable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function getAllDefaultMeta(): array
    {
        return [
            'form_data' => [],
        ];
    }
}
