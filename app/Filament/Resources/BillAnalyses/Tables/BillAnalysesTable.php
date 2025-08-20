<?php

namespace App\Filament\Resources\BillAnalyses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillAnalysesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('bill_identifier')
                    ->label('Законопроект')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary')
                    ->default('Няма ID'),

                TextColumn::make('proposer_name')
                    ->label('Предложил')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    })
                    ->default('Неизвестен'),

                TextColumn::make('amendment_type')
                    ->label('Тип изменение')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new' => 'Нов текст',
                        'modification' => 'Изменение',
                        'deletion' => 'Заличаване',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'success',
                        'modification' => 'warning',
                        'deletion' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'proposed' => 'Предложено',
                        'approved' => 'Одобрено',
                        'rejected' => 'Отхвърлено',
                        'pending' => 'В очакване',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'proposed' => 'info',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('ai_confidence')
                    ->label('AI доверие')
                    ->badge()
                    ->formatStateUsing(fn (?float $state): string => $state ? number_format($state * 100, 1) . '%' : 'Няма данни')
                    ->sortable(),

                TextColumn::make('transcript.title')
                    ->label('Стенограма')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 40 ? $state : null;
                    })
                    ->default('Няма връзка')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('bill.sign')
                    ->label('Номер законопроект')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->default('Не е свързан')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('vote_results')
                    ->label('Гласуване')
                    ->formatStateUsing(function (?array $state): string {
                        if (!$state) return 'Няма данни';

                        $results = [];
                        if (isset($state['for'])) $results[] = "За: {$state['for']}";
                        if (isset($state['against'])) $results[] = "Против: {$state['against']}";
                        if (isset($state['abstained'])) $results[] = "Възд.: {$state['abstained']}";

                        return implode(' | ', $results);
                    })
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Създаден')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Актуализиран')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('amendment_type')
                    ->label('Тип изменение')
                    ->options([
                        'new' => 'Нов текст',
                        'modification' => 'Изменение',
                        'deletion' => 'Заличаване',
                    ]),

                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'proposed' => 'Предложено',
                        'approved' => 'Одобрено',
                        'rejected' => 'Отхвърлено',
                        'pending' => 'В очакване',
                    ]),

                Filter::make('high_confidence')
                    ->label('Високо доверие (≥80%)')
                    ->query(fn (Builder $query): Builder => $query->where('ai_confidence', '>=', 0.8)),

                Filter::make('medium_confidence')
                    ->label('Средно доверие (60-79%)')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('ai_confidence', [0.6, 0.79])),

                Filter::make('low_confidence')
                    ->label('Ниско доверие (<60%)')
                    ->query(fn (Builder $query): Builder => $query->where('ai_confidence', '<', 0.6)),

                SelectFilter::make('transcript_id')
                    ->label('Стенограма')
                    ->relationship('transcript', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('bill_id')
                    ->label('Законопроект')
                    ->relationship('bill', 'sign')
                    ->searchable()
                    ->preload(),

                Filter::make('recent')
                    ->label('Скорошни (последните 7 дни)')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),

                Filter::make('this_month')
                    ->label('Този месец')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('created_at', now()->month)),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Детайли')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.backoffice.resources.bill-analyses.view', $record)),

                EditAction::make()
                    ->label('Редактирай')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn ($record) => route('filament.backoffice.resources.bill-analyses.edit', $record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Изтрий избраните'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
