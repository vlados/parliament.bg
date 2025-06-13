<?php

namespace App\Filament\Resources\Committees\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CommitteeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна информация')
                    ->schema([
                        TextInput::make('committee_id')
                            ->label('ID на комисия')
                            ->disabled(),
                        
                        TextInput::make('name')
                            ->label('Име на комисия')
                            ->disabled()
                            ->columnSpanFull(),
                        
                        TextInput::make('committee_type_id')
                            ->label('Тип комисия ID')
                            ->disabled(),
                        
                        TextInput::make('active_count')
                            ->label('Брой активни членове')
                            ->disabled(),
                    ])->columns(2),
                
                Section::make('Период на дейност')
                    ->schema([
                        DateTimePicker::make('date_from')
                            ->label('Дата от')
                            ->disabled(),
                        
                        DateTimePicker::make('date_to')
                            ->label('Дата до')
                            ->disabled(),
                    ])->columns(2),
                
                Section::make('Контактна информация')
                    ->schema([
                        TextInput::make('email')
                            ->label('Имейл')
                            ->disabled(),
                        
                        TextInput::make('room')
                            ->label('Стая')
                            ->disabled(),
                        
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->disabled(),
                        
                        TextInput::make('rules')
                            ->label('Правила')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }
}
