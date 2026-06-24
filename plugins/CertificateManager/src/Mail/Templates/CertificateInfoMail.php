<?php

namespace CertificateManager\Mail\Templates;


use App\Classes\Log;
use App\Mail\Templates\TemplateMailable;
use App\Mail\Templates\Traits\CanCustomizeTemplate;
use App\Models\MailTemplate;
use App\Models\Submission;
use CertificateManager\Models\Certificate;
use CertificateManager\Pages\CertificateManagePage;
use CertificateManager\Pages\CertificatePage;

class CertificateInfoMail extends TemplateMailable
{
    use CanCustomizeTemplate;

    public string $email;

    public string $name;

    public string $loginUrl;

    public string $downloadUrl;

    public function __construct(Certificate $certificate)
    {
        $this->email = $certificate->email;
        $this->loginUrl = route('livewirePageGroup.website.pages.login');

        $additionalData = [
            'Certificates Link' => route('filament.scheduledConference.pages.certificate-page'),
            'Certificate Name' => $certificate->template->name,
            ...$certificate->getMeta('form_data'),
        ];


        $this->setAdditionalData($additionalData);
    }

    public static function getDefaultSubject(): string
    {
        return 'Your Certificate {{ Certificate Name }} - {{ conferenceName }}';
    }

    public static function getDefaultHtmlTemplate(): string
    {
        return <<<'HTML'
            <p>Thank you for being part of {{ conferenceName }}.</p>
            <p>We have create a certificate {{ Certificate Name }} for you.</p>
            <p>You can download the <a href="{{ Certificates Link }}">certificate here</a></p>
        HTML;
    }

    public static function getDefaultDescription(): string
    {
        return 'Email template for sending certificate via email.';
    }
    
    public function setMailTemplate(MailTemplate $mailTemplate)
    {
        $this->mailTemplate = $mailTemplate;
    }
}
