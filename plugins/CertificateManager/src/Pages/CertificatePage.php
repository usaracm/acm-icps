<?php

namespace CertificateManager\Pages;

use App\Tables\Columns\IndexColumn;
use CertificateManager\Facades\CertificateFacade;
use CertificateManager\Models\Certificate;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

use function Amp\async;

class CertificatePage extends Page implements HasTable
{
	use InteractsWithTable;

	protected static bool $shouldRegisterNavigation = true;

	protected static string $view = 'CertificateManager::certificate-page';

	protected static ?string $navigationIcon = 'heroicon-o-document';

	protected static ?int $navigationSort = 99;

	public function mount() {}

	public static function getNavigationLabel(): string
	{
		return 'Certificates';
	}

	public function getTitle(): string|Htmlable
	{
		return static::getNavigationLabel();
	}

	public static function getRoutePath(): string
	{
		return '/certificates';
	}

	public static function getEloquentQuery(): Builder
	{
		return Certificate::query()
			->with(['meta', 'template'])
			->whereIn('certificate_template_id', fn($query) => $query->select('id')->from('certificate_templates')->where('scheduled_conference_id', app()->getCurrentScheduledConferenceId()))
			->where('email', auth()->user()->email);
	}

	public static function registerNavigationItems(): void
	{
		if(static::getEloquentQuery()->count() < 1) return;

		parent::registerNavigationItems();
	}

	public function table(Table $table): Table
	{
		return $table
			->query(static::getEloquentQuery())
			->columns([
				IndexColumn::make('#'),
				TextColumn::make('template.name')
					->label("Certificate Name")
					->searchable(),
				IconColumn::make('ready')
					->label("Document Ready")
					->state(fn(Certificate $record) => $record->hasMedia('document'))
					->boolean()
			])
			->actions([
				TableAction::make('download')
					->label('Download Certificate')
					->outlined()
					->icon('heroicon-o-arrow-down-tray')
					->visible(fn(Certificate $record) => $record->hasMedia('document'))
					->action(fn(Certificate $record) => $record->getFirstMedia('document')),
			]);
	}
}
