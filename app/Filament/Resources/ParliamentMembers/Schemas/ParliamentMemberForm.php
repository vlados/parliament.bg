<?php

namespace App\Filament\Resources\ParliamentMembers\Schemas;


use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ParliamentMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('👤 Лична информация')
                    ->description('Основни данни за народния представител')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Пълно име')
                            ->state(fn ($record) => $record?->full_name ?? 'Няма данни')
                            ->extraAttributes(['class' => 'text-lg font-bold text-primary-600'])
                            ->columnSpanFull(),

                        TextInput::make('first_name')
                            ->label('Име')
                            ->disabled()
                            ->prefixIcon('heroicon-o-identification'),

                        TextInput::make('middle_name')
                            ->label('Презиме')
                            ->disabled()
                            ->prefixIcon('heroicon-o-identification'),

                        TextInput::make('last_name')
                            ->label('Фамилия')
                            ->disabled()
                            ->prefixIcon('heroicon-o-identification'),

                        TextInput::make('member_id')
                            ->label('ID на представител')
                            ->disabled()
                            ->prefixIcon('heroicon-o-hashtag')
                            ->helperText('Уникален идентификатор в системата'),
                    ])
                    ->columns(2),

                Section::make('🏛️ Политическа информация')
                    ->description('Партийна принадлежност и избирателен район')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        TextEntry::make('political_party')
                            ->label('Политическа партия')
                            ->state(fn ($record) => $record?->political_party ?? 'Няма данни')
                            ->extraAttributes(['class' => 'text-primary-600 font-semibold'])
                            ->columnSpanFull(),

                        TextInput::make('electoral_district')
                            ->label('Изборен район')
                            ->disabled()
                            ->prefixIcon('heroicon-o-map-pin')
                            ->helperText('Района, който представлява'),

                        TextEntry::make('committees_count')
                            ->label('Комисии')
                            ->state(fn ($record) => $record ? $record->committees()->count() . ' комисии' : '0 комисии')
                            ->extraAttributes(['class' => 'text-success-600 font-medium']),
                    ])
                    ->columns(2),

                Section::make('📞 Контактна информация')
                    ->description('Професионални и контактни данни')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextInput::make('profession')
                            ->label('Професия')
                            ->disabled()
                            ->prefixIcon('heroicon-o-briefcase')
                            ->helperText('Професионална квалификация'),

                        TextInput::make('email')
                            ->label('Имейл адрес')
                            ->disabled()
                            ->prefixIcon('heroicon-o-envelope')
                            ->helperText('Официален електронен адрес'),
                    ])
                    ->columns(2),
            ]);
    }
}
