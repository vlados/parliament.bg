<?php

namespace App\Filament\Resources\ParliamentMembers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ParliamentMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('full_name')
                    ->label('Пълно име')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('electoral_district')
                    ->label('Изборен район')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('political_party')
                    ->label('Политическа партия')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                TextColumn::make('profession')
                    ->label('Професия')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('email')
                    ->label('Имейл')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                
                TextColumn::make('committees_count')
                    ->label('Комисии')
                    ->counts('committees')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Добавен')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('political_party')
                    ->label('Политическа партия')
                    ->options(function () {
                        return \App\Models\ParliamentMember::distinct()
                            ->pluck('political_party', 'political_party')
                            ->filter()
                            ->toArray();
                    }),
                
                SelectFilter::make('electoral_district')
                    ->label('Изборен район')
                    ->options(function () {
                        return \App\Models\ParliamentMember::distinct()
                            ->pluck('electoral_district', 'electoral_district')
                            ->filter()
                            ->toArray();
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Преглед')
                    ->modalHeading('Детайли за народен представител'),
            ])
            ->toolbarActions([])
            ->defaultSort('full_name');
    }
}
