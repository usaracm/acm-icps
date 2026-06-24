<?php

namespace CertificateManager\Models;

use App\Facades\Plugin as FacadesPlugin;
use CertificateManager\Enums\CertificateTemplateType;
use CertificateManager\Facades\CertificateFacade;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class Template extends Model
{
    use Sushi;

    public $incrementing = false;

    protected $casts = [
        'type' => CertificateTemplateType::class,
    ];

    public function getRows(): array
    {
        return CertificateFacade::cachedTemplates();
    }
}
