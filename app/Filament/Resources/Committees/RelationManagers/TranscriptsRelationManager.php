<?php

namespace App\Filament\Resources\Committees\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TranscriptsRelationManager extends RelationManager
{
    protected static string $relationship = 'transcripts';

    protected static ?string $recordTitleAttribute = 'transcript_id';

    protected static ?string $title = 'Стенограми';

    protected static ?string $modelLabel = 'стенограма';

    protected static ?string $pluralModelLabel = 'стенограми';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transcript_id')
            ->columns([
                TextColumn::make('transcript_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('type')
                    ->label('Тип')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Пълен протокол' => 'success',
                        'Съкратен протокол' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('transcript_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('word_count')
                    ->label('Думи')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state) : '0')
                    ->color('success')
                    ->alignEnd(),

                TextColumn::make('created_at')
                    ->label('Добавена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип протокол')
                    ->options([
                        'Пълен протокол' => 'Пълен протокол',
                        'Съкратен протокол' => 'Съкратен протокол',
                    ]),

                SelectFilter::make('year')
                    ->label('Година')
                    ->options(function () {
                        return \App\Models\Transcript::distinct()
                            ->pluck('year', 'year')
                            ->filter()
                            ->sortDesc()
                            ->toArray();
                    }),

                Filter::make('recent')
                    ->label('Скорошни (последните 30 дни)')
                    ->query(fn (Builder $query): Builder => $query->where('transcript_date', '>=', now()->subDays(30))),

                Filter::make('this_year')
                    ->label('Тази година')
                    ->query(fn (Builder $query): Builder => $query->whereYear('transcript_date', now()->year)),
            ])
            ->headerActions([
                // No create action - transcripts are scraped from API
            ])
            ->actions([
                ViewAction::make()
                    ->label('Детайли')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.backoffice.resources.transcripts.view', $record)),
            ])
            ->defaultSort('transcript_date', 'desc');
    }
}