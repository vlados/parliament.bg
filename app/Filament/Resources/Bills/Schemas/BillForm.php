<?php

namespace App\Filament\Resources\Bills\Schemas;

use App\Models\Committee;
use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;

class BillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна информация')
                    ->schema([
                        TextInput::make('bill_id')
                            ->label('ID на законопроект')
                            ->disabled(),
                        
                        TextInput::make('sign')
                            ->label('Номер на законопроект')
                            ->disabled(),
                        
                        DateTimePicker::make('bill_date')
                            ->label('Дата на законопроект')
                            ->disabled(),
                        
                        TextInput::make('path')
                            ->label('Категория')
                            ->disabled(),
                    ])->columns(2),
                
                Section::make('Съдържание')
                    ->schema([
                        Textarea::make('title')
                            ->label('Заглавие на законопроект')
                            ->disabled()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                
                Section::make('Възложена комисия')
                    ->schema([
                        Select::make('committee_id')
                            ->label('Комисия')
                            ->options(Committee::pluck('name', 'committee_id'))
                            ->disabled(),
                    ]),
            ]);
    }
}
