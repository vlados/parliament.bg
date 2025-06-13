<?php

namespace App\Filament\Resources\Committees\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Actions\CreateAction;
use Filament\Actions\AttachAction;
use Filament\Actions\EditAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\DeleteBulkAction;

class BillsRelationManager extends RelationManager
{
    protected static string $relationship = 'bills';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bill_id')
                    ->label('ID на законопроект')
                    ->disabled(),
                
                TextInput::make('sign')
                    ->label('Номер')
                    ->disabled(),
                
                Textarea::make('title')
                    ->label('Заглавие')
                    ->disabled()
                    ->rows(3),
                
                DateTimePicker::make('bill_date')
                    ->label('Дата')
                    ->disabled(),
                
                TextInput::make('path')
                    ->label('Категория')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('bill_id')
                    ->label('ID')
                    ->sortable(),
                
                TextColumn::make('sign')
                    ->label('Номер')
                    ->searchable()
                    ->copyable(),
                
                TextColumn::make('title')
                    ->label('Заглавие')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 60 ? $state : null;
                    }),
                
                TextColumn::make('bill_date')
                    ->label('Дата')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('path')
                    ->label('Категория')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('recent')
                    ->label('Скорошни (последните 30 дни)')
                    ->query(fn ($query) => $query->where('bill_date', '>=', now()->subDays(30))),
            ])
            ->headerActions([])
            ->actions([])
            ->toolbarActions([])
            ->defaultSort('bill_date', 'desc');
    }
}