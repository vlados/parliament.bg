<?php

namespace App\Filament\Resources\Transcripts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TranscriptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transcript_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),
                
                TextColumn::make('committee.name')
                    ->label('Комисия')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->badge()
                    ->color('info')
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 40 ? $state : null;
                    }),
                
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
                    ->formatStateUsing(fn (int $state): string => number_format($state))
                    ->color('success')
                    ->alignEnd(),
                
                TextColumn::make('character_count')
                    ->label('Символи')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state))
                    ->color('info')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('year')
                    ->label('Година')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('month')
                    ->label('Месец')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => date('F', mktime(0, 0, 0, $state, 1)))
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('Добавена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('committee_id')
                    ->label('Комисия')
                    ->relationship('committee', 'name')
                    ->searchable()
                    ->preload(),
                
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
                
                SelectFilter::make('month')
                    ->label('Месец')
                    ->options([
                        1 => 'Януари',
                        2 => 'Февруари',
                        3 => 'Март',
                        4 => 'Април',
                        5 => 'Май',
                        6 => 'Юни',
                        7 => 'Юли',
                        8 => 'Август',
                        9 => 'Септември',
                        10 => 'Октомври',
                        11 => 'Ноември',
                        12 => 'Декември',
                    ]),
                
                Filter::make('recent')
                    ->label('Скорошни (последните 30 дни)')
                    ->query(fn (Builder $query): Builder => $query->where('transcript_date', '>=', now()->subDays(30))),
                
                Filter::make('this_year')
                    ->label('Тази година')
                    ->query(fn (Builder $query): Builder => $query->whereYear('transcript_date', now()->year)),
                
                Filter::make('has_content')
                    ->label('Със съдържание')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('content_text')->where('content_text', '!=', '')),
                
                Filter::make('content_search')
                    ->label('Търсене в съдържанието')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('search')
                            ->label('Търсене')
                            ->placeholder('Въведете текст за търсене...')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['search'] ?? null,
                            fn (Builder $query, $search): Builder => $query->where('content_text', 'like', "%{$search}%")
                        );
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Детайли')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.backoffice.resources.transcripts.view', $record)),
            ])
            ->toolbarActions([])
            ->defaultSort('transcript_date', 'desc');
    }
}