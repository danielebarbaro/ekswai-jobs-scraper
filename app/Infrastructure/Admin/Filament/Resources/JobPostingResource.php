<?php

namespace App\Infrastructure\Admin\Filament\Resources;

use App\Domain\JobPosting\JobPosting;
use App\Infrastructure\Admin\Filament\Resources\JobPostingResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JobPostingResource extends Resource
{
    protected static ?string $model = JobPosting::class;

    protected static $navigationIcon = 'heroicon-o-briefcase';

    protected static $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Job Posting Information')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('external_id')
                            ->required()
                            ->maxLength(255)
                            ->label('External ID'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('location')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('url')
                            ->required()
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('department')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('first_seen_at')
                            ->required(),
                        Forms\Components\DateTimePicker::make('last_seen_at'),
                        Forms\Components\Textarea::make('raw_payload')
                            ->label('Raw API Payload')
                            ->rows(10)
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(fn ($state) => json_decode($state, true)),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('department')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('first_seen_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->label('First Seen'),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Last Seen'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('first_seen_at')
                    ->form([
                        Forms\Components\DatePicker::make('first_seen_from')
                            ->label('First seen from'),
                        Forms\Components\DatePicker::make('first_seen_until')
                            ->label('First seen until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['first_seen_from'], fn ($q, $date) => $q->whereDate('first_seen_at', '>=', $date))
                            ->when($data['first_seen_until'], fn ($q, $date) => $q->whereDate('first_seen_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Job')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (JobPosting $record) => $record->url)
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('first_seen_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobPostings::route('/'),
            'create' => Pages\CreateJobPosting::route('/create'),
            'view' => Pages\ViewJobPosting::route('/{record}'),
            'edit' => Pages\EditJobPosting::route('/{record}/edit'),
        ];
    }
}
