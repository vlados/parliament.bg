<?php

namespace App\Filament\Resources\Committees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommitteesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Име на комисия')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('bold')
                    ->size('base'),
                
                TextColumn::make('active_count')
                    ->label('Активни членове')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                
                TextColumn::make('parliament_members_count')
                    ->label('Общо членове')
                    ->counts('parliamentMembers')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
                TextColumn::make('bills_count')
                    ->label('Законопроекти')
                    ->counts('bills')
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                
                TextColumn::make('status')
                    ->label('Статус')
                    ->getStateUsing(fn ($record) => 
                        $record->date_to === null || $record->date_to > now() 
                            ? 'Активна' 
                            : 'Неактивна'
                    )
                    ->badge()
                    ->color(fn ($state) => $state === 'Активна' ? 'success' : 'gray'),
                
                TextColumn::make('email')
                    ->label('Имейл')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('committee_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('date_from')
                    ->label('Активна от')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('date_to')
                    ->label('Активна до')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Активни комисии')
                    ->query(fn (Builder $query): Builder => $query->whereNull('date_to')->orWhere('date_to', '>', now())),
                
                Filter::make('has_bills')
                    ->label('С законопроекти')
                    ->query(fn (Builder $query): Builder => $query->has('bills')),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Детайли')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.backoffice.resources.committees.view', $record)),
            ])
            ->toolbarActions([])
            ->defaultSort('name');
    }
}
