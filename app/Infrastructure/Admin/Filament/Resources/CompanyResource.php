<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources;

use App\Domain\Company\Company;
use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Admin\Filament\Resources\CompanyResource\Pages;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-building-office';
    }

    public static function getNavigationSort(): int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Company Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('provider')
                            ->options(
                                collect(JobBoardProvider::cases())
                                    ->mapWithKeys(fn (JobBoardProvider $p) => [$p->value => ucfirst($p->value)])
                                    ->toArray()
                            )
                            ->default(JobBoardProvider::Workable->value)
                            ->required(),
                        Forms\Components\TextInput::make('provider_slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The provider account identifier (e.g., "company-name")')
                            ->unique(ignoreRecord: true),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->helperText('Enable or disable job tracking for this company'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider_slug')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subscribers_count')
                    ->counts('subscribers')
                    ->label('Subscribers')
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_postings_count')
                    ->counts('jobPostings')
                    ->label('Jobs')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('toggle')
                    ->label(fn (Company $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (Company $record) => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Company $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (Company $record) => $record->toggleActivation()),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
