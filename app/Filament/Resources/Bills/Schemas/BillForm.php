<?php

namespace App\Filament\Resources\Bills\Schemas;

use App\Models\Committee;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('📜 Основна информация')
                    ->description('Основни данни за законопроекта')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('sign')
                            ->label('Номер на законопроект')
                            ->state(fn ($record) => $record?->sign ?? 'Няма данни')
                            ->extraAttributes(['class' => 'text-lg font-bold text-primary-600'])
                            ->columnSpanFull(),

                        TextInput::make('bill_id')
                            ->label('ID в системата')
                            ->disabled()
                            ->prefixIcon('heroicon-o-hashtag')
                            ->helperText('Уникален идентификатор'),
                        
                        DateTimePicker::make('bill_date')
                            ->label('Дата на внасяне')
                            ->disabled()
                            ->prefixIcon('heroicon-o-calendar')
                            ->displayFormat('d.m.Y')
                            ->helperText('Кога е внесен в парламента'),

                        TextInput::make('path')
                            ->label('Категория/Област')
                            ->disabled()
                            ->prefixIcon('heroicon-o-tag')
                            ->helperText('Правна категория или област'),

                        TextEntry::make('status_info')
                            ->label('Статус')
                            ->state(fn ($record) => $record ? '📋 Активен законопроект' : 'Няма данни')
                            ->extraAttributes(['class' => 'text-lg font-semibold']),
                    ])
                    ->columns(2),

                Section::make('🏛️ Отговорна комисия')
                    ->description('Комисия, която разглежда законопроекта')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        TextEntry::make('committee.name')
                            ->label('Име на комисия')
                            ->state(fn ($record) => $record?->committee?->name ?? 'Не е определена')
                            ->extraAttributes(['class' => 'text-lg font-bold text-info-600'])
                            ->columnSpanFull(),

                        Select::make('committee_id')
                            ->label('ID на комисия')
                            ->options(Committee::pluck('name', 'committee_id'))
                            ->disabled()
                            ->prefixIcon('heroicon-o-identification')
                            ->helperText('Системен идентификатор'),

                        TextEntry::make('committee_members')
                            ->label('Членове в комисията')
                            ->state(fn ($record) => $record?->committee ? $record->committee->parliamentMembers()->count() . ' души' : 'Няма данни')
                            ->extraAttributes(['class' => 'text-success-600 font-medium']),

                        TextEntry::make('committee_status')
                            ->label('Статус на комисията')
                            ->state(fn ($record) => $record?->committee ? '🟢 Активна комисия' : 'Няма данни')
                            ->extraAttributes(['class' => 'font-bold'])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('📝 Съдържание на законопроекта')
                    ->description('Пълното заглавие и описание')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Заглавие')
                            ->state(fn ($record) => $record?->title ?? 'Няма данни')
                            ->extraAttributes(['class' => 'text-base font-medium leading-relaxed'])
                            ->columnSpanFull(),

                        Textarea::make('title')
                            ->label('Пълен текст на заглавието')
                            ->disabled()
                            ->rows(4)
                            ->helperText('Официалното заглавие на законопроекта както е внесен')
                            ->columnSpanFull(),

                        TextEntry::make('word_count')
                            ->label('Брой думи')
                            ->state(fn ($record) => $record?->title ? str_word_count($record->title) . ' думи' : '0 думи')
                            ->extraAttributes(['class' => 'text-gray-600']),

                        TextEntry::make('char_count')
                            ->label('Брой символи')
                            ->state(fn ($record) => $record?->title ? strlen($record->title) . ' символа' : '0 символа')
                            ->extraAttributes(['class' => 'text-gray-600']),

                        TextEntry::make('complexity')
                            ->label('Сложност')
                            ->state(fn ($record) => $record?->title ? '📊 Анализиран текст' : 'Няма данни')
                            ->extraAttributes(['class' => 'font-medium']),
                    ])
                    ->columns(3),
            ]);
    }
}