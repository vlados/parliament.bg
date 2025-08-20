<?php

namespace App\Filament\Resources\Transcripts\Schemas;

use App\Models\Committee;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TranscriptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('📝 Основна информация')
                    ->description('Основни данни за стенограмата')
                    ->icon('heroicon-o-document')
                    ->schema([
                        TextEntry::make('transcript_id')
                            ->label('ID на стенограма')
                            ->state(fn ($record) => $record?->transcript_id ?? 'Няма данни')
                            ->extraAttributes(['class' => 'text-lg font-bold text-primary-600'])
                            ->columnSpanFull(),

                        TextInput::make('type')
                            ->label('Тип стенограма')
                            ->disabled()
                            ->prefixIcon('heroicon-o-document-text')
                            ->helperText('Съкратен или пълен протокол'),
                        
                        DatePicker::make('transcript_date')
                            ->label('Дата на заседанието')
                            ->disabled()
                            ->prefixIcon('heroicon-o-calendar')
                            ->displayFormat('d.m.Y')
                            ->helperText('Кога е проведено заседанието'),

                        TextEntry::make('year')
                            ->label('Година')
                            ->state(fn ($record) => $record?->year ?? 'Няма данни')
                            ->extraAttributes(['class' => 'text-lg font-semibold']),

                        TextEntry::make('month')
                            ->label('Месец')
                            ->state(fn ($record) => $record?->month ? date('F', mktime(0, 0, 0, $record->month, 1)) : 'Няма данни')
                            ->extraAttributes(['class' => 'text-lg font-semibold']),
                    ])
                    ->columns(2),

                Section::make('🏛️ Комисия')
                    ->description('Комисия, която е провела заседанието')
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
                    ])
                    ->columns(2),

                Section::make('📊 Статистики')
                    ->description('Статистически данни за стенограмата')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        TextEntry::make('word_count')
                            ->label('Брой думи')
                            ->state(fn ($record) => $record?->word_count ? number_format($record->word_count) . ' думи' : '0 думи')
                            ->extraAttributes(['class' => 'text-blue-600 font-medium']),

                        TextEntry::make('character_count')
                            ->label('Брой символи')
                            ->state(fn ($record) => $record?->character_count ? number_format($record->character_count) . ' символа' : '0 символа')
                            ->extraAttributes(['class' => 'text-blue-600 font-medium']),

                        TextEntry::make('content_length')
                            ->label('Размер на съдържанието')
                            ->state(function ($record) {
                                if (!$record?->content_text) return 'Няма данни';
                                $kb = strlen($record->content_text) / 1024;
                                return number_format($kb, 1) . ' KB';
                            })
                            ->extraAttributes(['class' => 'text-gray-600']),
                    ])
                    ->columns(3),

                Section::make('📄 Съдържание')
                    ->description('Пълният текст на стенограмата')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('content_text')
                            ->label('Текстово съдържание')
                            ->disabled()
                            ->rows(20)
                            ->helperText('Извлеченият текст от HTML стенограмата')
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'font-mono text-sm']),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}