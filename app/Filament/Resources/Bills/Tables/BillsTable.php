<?php

namespace App\Filament\Resources\Bills\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bill_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('sign')
                    ->label('Номер')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                TextColumn::make('title')
                    ->label('Заглавие')
                    ->searchable()
                    ->limit(80)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 80 ? $state : null;
                    }),
                
                TextColumn::make('committee.name')
                    ->label('Комисия')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                TextColumn::make('bill_date')
                    ->label('Дата')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('path')
                    ->label('Категория')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('Добавен')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('committee_id')
                    ->label('Комисия')
                    ->relationship('committee', 'name')
                    ->searchable()
                    ->preload(),
                
                Filter::make('recent')
                    ->label('Скорошни (последните 30 дни)')
                    ->query(fn (Builder $query): Builder => $query->where('bill_date', '>=', now()->subDays(30))),
                
                Filter::make('this_year')
                    ->label('Тази година')
                    ->query(fn (Builder $query): Builder => $query->whereYear('bill_date', now()->year)),
                
                SelectFilter::make('path')
                    ->label('Категория')
                    ->options(function () {
                        return \App\Models\Bill::distinct()
                            ->pluck('path', 'path')
                            ->filter()
                            ->toArray();
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Преглед')
                    ->modalHeading('Детайли за законопроект'),
            ])
            ->toolbarActions([])
            ->defaultSort('bill_date', 'desc');
    }
}
