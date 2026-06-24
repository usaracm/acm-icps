<?php

namespace CertificateManager\Facades;

use App\Managers\CitationManager;
use CertificateManager\Managers\CertificateApiManager;
use Illuminate\Support\Facades\Facade;
use LetterOfAcceptance\Managers\LoaService;

class CertificateFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CertificateApiManager::class;
    }
}
