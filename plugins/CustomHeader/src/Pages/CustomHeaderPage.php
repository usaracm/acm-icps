<?php

namespace CustomHeader\Pages;

use App\Facades\Plugin;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class CustomHeaderPage extends Page implements HasForms
{
	use InteractsWithForms;

	protected static ?string $title = 'Custom Header Plugin';

	protected static string $view = 'CustomHeader::custom-header';

	protected static bool $shouldRegisterNavigation = false;

	public ?array $data = [];

	public function mount(): void
	{
		$plugin = Plugin::getPlugin('CustomHeader');

		$this->form->fill([
			'header_content' => $plugin->getSetting('header_content'),
			'footer_content' => $plugin->getSetting('footer_content'),
		]);
	}

	public static function getRoutePath(): string
	{
		return '/custom-header';
	}

	/**
	 * @return array<string>
	 */
	public function getBreadcrumbs(): array
	{
		return [];
	}

	public function getSubheading(): string | Htmlable | null
	{
		return new HtmlString(<<<HTML
			<span class="text-sm text-gray-500">This plugin allows you to add custom headers to the website. This can be used, for example, to add metadata, javascript, css, etc.</span>
		HTML);
	}

	public function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make()
					->schema([
						Textarea::make('header_content')
							->label('Header Content')
							->rows(5)
							->autosize(),
						Textarea::make('footer_content')
							->label('Footer Content')
							->rows(5)
							->autosize(),
					])
			])
			->statePath('data');
	}

	public function submit()
	{
		$plugin = Plugin::getPlugin('CustomHeader');
		$data = $this->form->getState();

		try {
			$plugin->updateSetting('header_content', $data['header_content']);
			$plugin->updateSetting('footer_content', $data['footer_content']);

			Notification::make()
				->success()
				->title(__('general.saved'))
				->send();
		} catch (\Throwable $th) {
			Notification::make()
				->danger()
				->title(__('general.error'))
				->body(__('general.there_was_error_please_contact_administrator'))
				->send();

			Log::error($th);
		}
	}
}
