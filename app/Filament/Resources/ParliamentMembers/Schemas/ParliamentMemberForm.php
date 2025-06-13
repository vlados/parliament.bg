<?php

namespace App\Filament\Resources\ParliamentMembers\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;

class ParliamentMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна информация')
                    ->schema([
                        TextInput::make('member_id')
                            ->label('ID на представител')
                            ->disabled(),
                        
                        TextInput::make('first_name')
                            ->label('Име')
                            ->disabled(),
                        
                        TextInput::make('middle_name')
                            ->label('Презиме')
                            ->disabled(),
                        
                        TextInput::make('last_name')
                            ->label('Фамилия')
                            ->disabled(),
                        
                        TextInput::make('full_name')
                            ->label('Пълно име')
                            ->disabled(),
                    ])->columns(2),
                
                Section::make('Политическа информация')
                    ->schema([
                        TextInput::make('electoral_district')
                            ->label('Изборен район')
                            ->disabled(),
                        
                        TextInput::make('political_party')
                            ->label('Политическа партия')
                            ->disabled(),
                    ])->columns(2),
                
                Section::make('Контактна информация')
                    ->schema([
                        TextInput::make('profession')
                            ->label('Професия')
                            ->disabled(),
                        
                        TextInput::make('email')
                            ->label('Имейл')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }
}
