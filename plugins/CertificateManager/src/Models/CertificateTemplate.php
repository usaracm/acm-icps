<?php

namespace CertificateManager\Models;

use App\Models\Concerns\BelongsToScheduledConference;
use CertificateManager\Enums\CertificateTemplateType;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Plank\Metable\Metable;

class CertificateTemplate extends Model
{
    use BelongsToScheduledConference, Metable, Cachable;

    protected $fillable = [
        'scheduled_conference_id',
        'name',
        'email',
        'type',
        'order_column',
        'template_id',
    ];

    protected $casts = [
        'type' => CertificateTemplateType::class,
    ];

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    protected function getAllDefaultMeta(): array
    {
        return [
            'number' => 0,
        ];
    }
}
