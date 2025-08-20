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
                TextColumn::make('full_name')
                    ->label('Пълно име')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('base'),
                
                TextColumn::make('electoral_district')
                    ->label('Изборен район')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('political_party')
                    ->label('Политическа партия')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->badge()
                    ->color('primary'),
                
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
                    ->sortable()
                    ->badge()
                    ->color('success'),
                
                TextColumn::make('member_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
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
            ->actions([
                ViewAction::make()
                    ->label('Детайли')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.backoffice.resources.parliament-members.view', $record)),
            ])
            ->toolbarActions([])
            ->defaultSort('full_name');
    }
}
