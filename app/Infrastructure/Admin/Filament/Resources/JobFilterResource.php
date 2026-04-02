<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources;

use App\Domain\JobFilter\JobFilter;
use App\Infrastructure\Admin\Filament\Resources\JobFilterResource\Pages;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class JobFilterResource extends Resource
{
    protected static ?string $model = JobFilter::class;

    #[\Override]
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-funnel';
    }

    #[\Override]
    public static function getNavigationSort(): int
    {
        return 5;
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Filter Configuration')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->nullable()
                            ->helperText('Leave empty for a global filter'),
                        Forms\Components\TagsInput::make('title_include')
                            ->helperText('Job titles must contain at least one of these keywords'),
                        Forms\Components\TagsInput::make('title_exclude')
                            ->helperText('Job titles containing these keywords will be excluded'),
                        Forms\Components\TagsInput::make('department_include')
                            ->helperText('Only include jobs from these departments'),
                        Forms\Components\Toggle::make('remote_only')
                            ->default(false)
                            ->helperText('Only show remote positions'),
                    ])
                    ->columns(2),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->default('Global')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title_include')
                    ->badge()
                    ->separator(','),
                Tables\Columns\TextColumn::make('title_exclude')
                    ->badge()
                    ->separator(','),
                Tables\Columns\IconColumn::make('remote_only')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListJobFilters::route('/'),
            'edit' => Pages\EditJobFilter::route('/{record}/edit'),
        ];
    }
}
