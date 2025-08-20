<?php

namespace App\Filament\Resources\ParliamentMembers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;

class CommitteesRelationManager extends RelationManager
{
    protected static string $relationship = 'committees';
    
    protected static ?string $title = 'Комисии';
    
    protected static ?string $modelLabel = 'Комисия';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('committee_id')
                    ->label('ID на комисия')
                    ->disabled(),
                
                TextInput::make('name')
                    ->label('Име на комисия')
                    ->disabled(),
                
                TextInput::make('active_count')
                    ->label('Брой активни членове')
                    ->disabled(),
                
                DateTimePicker::make('date_from')
                    ->label('Дата от')
                    ->disabled(),
                
                DateTimePicker::make('date_to')
                    ->label('Дата до')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('committee_id')
                    ->label('ID')
                    ->sortable(),
                
                TextColumn::make('name')
                    ->label('Име на комисия')
                    ->searchable()
                    ->wrap(),
                
                TextColumn::make('active_count')
                    ->label('Активни членове')
                    ->sortable(),
                
                TextColumn::make('date_from')
                    ->label('Активна от')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('date_to')
                    ->label('Активна до')
                    ->date()
                    ->sortable()
                    ->placeholder('Текуща'),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Активни комисии')
                    ->query(fn ($query) => $query->whereNull('date_to')->orWhere('date_to', '>', now())),
            ])
            ->headerActions([])
            ->actions([])
            ->toolbarActions([])
            ->defaultSort('name');
    }
}