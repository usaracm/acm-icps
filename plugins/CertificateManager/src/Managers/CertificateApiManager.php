<?php

namespace CertificateManager\Managers;

use CertificateManager\CertificateManagerPlugin;
use CertificateManager\Mail\Templates\CertificateInfoMail;
use CertificateManager\Models\Certificate;
use CertificateManager\Models\CertificateTemplate;
use CertificateManager\Models\Template;
use Exception;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CertificateApiManager
{
	public function __construct(
		public CertificateManagerPlugin $plugin,
	) {
		// 
	}

	public function check()
	{
		$lastCheck = $this->plugin->getSetting('last_check');
		if ($lastCheck && now()->diffInMinutes($lastCheck) < 60) {
			return;
		}

		$response = $this->getHttpClient()->post(app()->getApiUrl('leconfe/plugin/check/') . $this->plugin->id);

		if ($response->failed()) {
			$response->throw();
		}

		$data = $response->json();
		$this->plugin->updateSetting('unique_id', $data['unique_id']);
		$this->plugin->updateSetting('expired_at', $data['expired_at']);
		$this->plugin->updateSetting('quota', $data['quota']);
		$this->plugin->updateSetting('last_check', now());
	}

	public function getApiUrl(?string $endpoint = null): string
	{
		return app()->getApiUrl('leconfe/certificate-manager') . ($endpoint ? '/' . $endpoint : '');
	}

	public function templates()
	{
		$response = $this->getHttpClient()->get($this->getApiUrl('templates'));

		if ($response->failed()) {
			$json = $response->json();
			$message = Arr::get($json, 'message');

			if ($message) {
				throw new \Exception($message);
			}

			throw new \Exception('Failed to get certificate templates.');
		}

		return $response->json();
	}

	public function cachedTemplates()
	{
		return Cache::remember('certificate_templates', now()->addHour(6), fn() => $this->templates());
	}

	public function getTemplateOptions()
	{
		$templates = $this->cachedTemplates();

		return collect($templates)->map(fn($template, $key) => $template['name']);
	}

	public function createTemplate(Template $template, string $title, string $email)
	{
		$response = $this->getHttpClient()->post($this->getApiUrl('copyTemplate'), [
			'name' => $title,
			'email' => $email,
			'template' => $template->getKey(), 
		]);

		if ($response->failed()) {
			$json = $response->json();
			$message = Arr::get($json, 'message');

			if ($message) {
				throw new \Exception($message);
			}

			throw new \Exception('Failed to create Certificate template.');
		}

		$responseData = $response->json();

		return Arr::get($responseData, 'data');
	}

	public function shareTemplate(string $email)
	{
		$response = $this->getHttpClient()->post($this->getApiUrl('shareTemplate/' . $this->plugin->getSetting('template_id')), [
			'email' => $email,
		]);

		if ($response->failed()) {
			Log::error('Failed to share certificate template', [
				'response' => $response->json(),
				'status_code' => $response->status(),
			]);
			throw new \Exception('Failed to share certificate template.');
		}

		$responseData = $response->json();

		return Arr::get($responseData, 'data');
	}

	public function generateDocument(string $templateId, string $fileName, array $texts = [])
	{
		$response = $this->getHttpClient()
			->post($this->getApiUrl('generate/' . $templateId), [
				'file_name' => $fileName,
				'texts' => $texts,
			]);

		if ($response->failed()) {
			Log::error('Failed to generate certificate document', [
				'response' => $response->json(),
				'status_code' => $response->status(),
			]);
			$response->throw();
		}

		return $response;
	}

	public function generateDocumentBatch(string $templateId, array $data, array $additionalParams = [])
	{
		$response = $this->getHttpClient()
			->post($this->getApiUrl('generateBatch/' . $templateId), [
				'data' => json_encode($data),
				'webhook' => route('livewirePageGroup.certificate-manager.callbackGenerateCertificate'),
				'params' => json_encode($additionalParams),
			]);

		if ($response->failed()) {
			Log::error('Failed to generate certificate document', [
				'response' => $response->json(),
				'status_code' => $response->status(),
			]);
			$response->throw();
		}

		return $response;
	}

	public function generateDocumentForCertificate(Certificate $record)
	{
		try {
			DB::beginTransaction();

			$record
				->addMediaCollection('document')
				->singleFile();

			$currentScheduledConference = app()->getCurrentScheduledConference();
			$texts = [
				'Id' => $record->certifiable_id,
				'Conference Title' => $currentScheduledConference->title,
				'Conference URL' => $currentScheduledConference->getUrl(),
				'Number' => $this->plugin->formatNumber($record->number),
				...$record->getMeta('form_data') ?? [],
			];


			$filename = implode('-', [
				$record->template->name,
				$record->getKey(),
				$this->plugin->formatNumber($record->number),
				$record->email,
			]);

			$response = $this->generateDocument(
				$record->template->template_id,
				$filename,
				$texts
			);


			$media = $record
				->addMediaFromString($response->body())
				->usingFileName($filename . '.pdf')
				->toMediaCollection('document', 'private-files');

			DB::commit();

			return $media;
		} catch (\Throwable $th) {
			DB::rollBack();
			throw $th;
		}
	}

	protected function prepareData(Certificate $record)
	{
		$currentScheduledConference = app()->getCurrentScheduledConference();
		$texts = [
			'Id' => $record->certifiable_id,
			'Conference Title' => $currentScheduledConference->title,
			'Conference URL' => $currentScheduledConference->getUrl(),
			'Number' => $this->plugin->formatNumber($record->number),
			...$record->getMeta('form_data') ?? [],
		];


		$filename = implode('-', [
			$record->template->name,
			$record->getKey(),
			$this->plugin->formatNumber($record->number),
			$record->email,
		]);

		return [
			'id' => $record->getKey(),
			'filename' => $filename,
			'texts' => $texts,
		];
	}

	public function generateDocumentByTemplate(CertificateTemplate $template, bool $forceRegenerate = false, array $additionalParams = [])
	{
		$template->certificates()->with(['template'])->chunk(200, function(Collection $certificates) use ($forceRegenerate, $additionalParams, $template){

			$batchData = $certificates
				->when(!$forceRegenerate, fn($certificates) => $certificates->filter(fn($certificate) => !$certificate->hasMedia('document')))
				->map(fn($certificate) => $this->prepareData($certificate));
				
			if($batchData->isNotEmpty()){
				$this->generateDocumentBatch($template->template_id, $batchData->toArray(), $additionalParams);
			}
		});

	}

	public function license(string $license): string
	{
		try {
			$response = $this->getHttpClient()
				->post(app()->getApiUrl('leconfe/plugin/license/' . $this->plugin->id), [
					'license' => $license,
				]);

			if ($response->failed()) {
				if ($message = data_get($response->json(), 'message')) {
					throw new Exception($message);
				} else {
					$response->throw();
				}
			}

			$json = $response->json();

			return $json['message'] ?? 'License activated successfully';
		} catch (\Throwable $th) {
			Log::error($th);

			throw $th;
		} finally {
			$this->plugin->updateSetting('last_check', null);
		}
	}

	protected function getHttpClient()
	{
		return Http::withToken(app()->getCurrentScheduledConference()->getEntityToken())
			->acceptJson();
	}

	public function sendEmail(Certificate $record)
    {
        $certificateMailTemplate = new CertificateInfoMail($record);

        if ($customMailSubject = $record->template->getMeta('custom_mail_subject')) $certificateMailTemplate->subjectUsing($customMailSubject);
        if ($customMailHtml = $record->template->getMeta('custom_mail_html')) $certificateMailTemplate->contentUsing($customMailHtml);

        Mail::to($record->email)
            ->send($certificateMailTemplate);

        $record->setMeta('email_sent', true);
    }
}
