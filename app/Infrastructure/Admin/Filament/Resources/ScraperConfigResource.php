<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources;

use App\Domain\Company\JobBoardProvider;
use App\Domain\ScraperConfig\ScraperConfig;
use App\Infrastructure\Admin\Filament\Resources\ScraperConfigResource\Pages;
use App\Infrastructure\Services\Scraping\ScraperHealthChecker;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ScraperConfigResource extends Resource
{
    protected static ?string $model = ScraperConfig::class;

    #[\Override]
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    #[\Override]
    public static function getNavigationSort(): int
    {
        return 5;
    }

    #[\Override]
    public static function getNavigationLabel(): string
    {
        return 'Scraper Configs';
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Provider Configuration')
                    ->schema([
                        Forms\Components\Select::make('provider')
                            ->options(
                                collect(JobBoardProvider::cases())
                                    ->reject(fn (JobBoardProvider $p): bool => $p === JobBoardProvider::Workable)
                                    ->mapWithKeys(fn (JobBoardProvider $p): array => [$p->value => ucfirst($p->value)])
                                    ->toArray()
                            )
                            ->required(),
                        Forms\Components\TextInput::make('base_url_pattern')
                            ->required()
                            ->maxLength(255)
                            ->helperText('URL pattern with {slug} placeholder (e.g., "https://jobs.example.com/{slug}")'),
                        Forms\Components\TextInput::make('health_check_selector')
                            ->required()
                            ->maxLength(255)
                            ->helperText('CSS selector used to verify the job board page loaded correctly'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('CSS Selectors')
                    ->schema([
                        Forms\Components\KeyValue::make('selectors')
                            ->keyLabel('Field')
                            ->valueLabel('CSS Selector')
                            ->required()
                            ->helperText('Map field names to their CSS selectors (e.g., "title" => ".job-title")'),
                    ]),
                Section::make('Retry Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('retry_attempts')
                            ->numeric()
                            ->default(3)
                            ->minValue(0)
                            ->maxValue(10)
                            ->label('Retries'),
                        Forms\Components\TextInput::make('retry_delay_seconds')
                            ->numeric()
                            ->default(30)
                            ->minValue(0)
                            ->maxValue(300)
                            ->helperText('Base delay in seconds between retries (exponential backoff applied)'),
                    ])
                    ->columns(2),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_url_pattern')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('health_check_selector')
                    ->limit(30),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('last_health_check_passed')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Health')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_health_check_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Check'),
                Tables\Columns\TextColumn::make('retry_attempts')
                    ->label('Retries'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('runHealthCheck')
                    ->label('Run Health Check')
                    ->icon('heroicon-o-heart')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (ScraperConfig $record): void {
                        $checker = app(ScraperHealthChecker::class);
                        $results = $checker->checkAll();

                        $result = $results->firstWhere('provider', $record->provider->value);

                        if ($result === null) {
                            Notification::make()
                                ->title('Health check could not run')
                                ->body('No active company found for this provider.')
                                ->warning()
                                ->send();

                            return;
                        }

                        if ($result['passed']) {
                            Notification::make()
                                ->title('Health check passed')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Health check failed')
                                ->body($result['error'] ?? 'Unknown error')
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\DeleteAction::make(),
            ])
            ->defaultSort('provider');
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScraperConfigs::route('/'),
            'create' => Pages\CreateScraperConfig::route('/create'),
            'edit' => Pages\EditScraperConfig::route('/{record}/edit'),
        ];
    }
}
