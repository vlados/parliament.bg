<?php

namespace App\Filament\Resources\Committees\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CommitteeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('🏛️ Обща информация')
                    ->description('Основни данни за комисията')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Име на комисия')
                            ->state(fn ($record) => $record?->name ?? 'Няма данни')
                            ->extraAttributes(['class' => 'text-lg font-bold text-primary-600'])
                            ->columnSpanFull(),

                        TextInput::make('committee_id')
                            ->label('ID на комисия')
                            ->disabled()
                            ->prefixIcon('heroicon-o-hashtag'),
                        
                        TextInput::make('committee_type_id')
                            ->label('Тип комисия')
                            ->disabled()
                            ->prefixIcon('heroicon-o-tag'),

                        TextEntry::make('active_count')
                            ->label('Активни членове')
                            ->state(fn ($record) => $record?->active_count ? $record->active_count . ' души' : 'Няма данни')
                            ->extraAttributes(['class' => 'text-success-600 font-semibold']),
                        
                        TextEntry::make('total_members')
                            ->label('Общо членове')
                            ->state(fn ($record) => $record ? $record->parliamentMembers()->count() . ' души' : '0 души')
                            ->extraAttributes(['class' => 'text-info-600 font-semibold']),

                        TextEntry::make('status')
                            ->label('Статус')
                            ->state(fn ($record) => 
                                $record && ($record->date_to === null || $record->date_to > now()) 
                                    ? '🟢 Активна' 
                                    : '🔴 Неактивна'
                            )
                            ->extraAttributes(['class' => 'text-lg font-bold'])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('📅 Период на дейност')
                    ->description('Времеви период на функциониране')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        DateTimePicker::make('date_from')
                            ->label('Дата от')
                            ->disabled()
                            ->prefixIcon('heroicon-o-play')
                            ->displayFormat('d.m.Y')
                            ->helperText('Начало на мандата'),
                        
                        DateTimePicker::make('date_to')
                            ->label('Дата до')
                            ->disabled()
                            ->prefixIcon('heroicon-o-stop')
                            ->displayFormat('d.m.Y')
                            ->helperText('Край на мандата (ако е приключил)'),

                        TextEntry::make('bills_count')
                            ->label('Законопроекти')
                            ->state(fn ($record) => $record ? $record->bills()->count() . ' броя' : '0 броя')
                            ->extraAttributes(['class' => 'text-warning-600 font-semibold'])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('📞 Контактна информация')
                    ->description('Как да се свържете с комисията')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextInput::make('email')
                            ->label('Имейл адрес')
                            ->disabled()
                            ->prefixIcon('heroicon-o-envelope')
                            ->helperText('Официален електронен адрес'),
                        
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->disabled()
                            ->prefixIcon('heroicon-o-phone')
                            ->helperText('Контактен телефон'),

                        TextInput::make('room')
                            ->label('Офис/Стая')
                            ->disabled()
                            ->prefixIcon('heroicon-o-building-office')
                            ->helperText('Местонахождение в сградата'),
                        
                        TextInput::make('rules')
                            ->label('Правила')
                            ->disabled()
                            ->prefixIcon('heroicon-o-document-text')
                            ->helperText('Вътрешни правила и регламент'),
                    ])
                    ->columns(2),
            ]);
    }
}
