<?php

namespace App\Filament\Resources\BillAnalyses\Schemas;

use App\Models\Bill;
use App\Models\Transcript;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BillAnalysisForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('🔍 Основна информация за анализа')
                    ->description('Основни данни за анализа на законопроекта')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID на анализ')
                            ->state(fn ($record) => $record?->id ?? 'Нов анализ')
                            ->extraAttributes(['class' => 'text-lg font-bold text-primary-600'])
                            ->columnSpanFull(),

                        TextInput::make('bill_identifier')
                            ->label('Идентификатор на законопроект')
                            ->prefixIcon('heroicon-o-document-text')
                            ->helperText('Официален номер или идентификатор'),

                        TextInput::make('proposer_name')
                            ->label('Предложил')
                            ->prefixIcon('heroicon-o-user')
                            ->helperText('Име на лицето/организацията, предложила изменението'),

                        Select::make('amendment_type')
                            ->label('Тип на изменението')
                            ->options([
                                'new' => 'Нов текст',
                                'modification' => 'Изменение',
                                'deletion' => 'Заличаване',
                            ])
                            ->prefixIcon('heroicon-o-pencil-square')
                            ->helperText('Категория на предложеното изменение'),

                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'proposed' => 'Предложено',
                                'approved' => 'Одобрено',
                                'rejected' => 'Отхвърлено',
                                'pending' => 'В очакване',
                            ])
                            ->prefixIcon('heroicon-o-flag')
                            ->helperText('Текущо състояние на предложението'),
                    ])
                    ->columns(2),

                Section::make('🤖 AI Анализ и доверие')
                    ->description('Информация за AI анализа и нивото на доверие')
                    ->icon('heroicon-o-cpu-chip')
                    ->schema([
                        TextEntry::make('confidence_percentage')
                            ->label('Ниво на доверие')
                            ->state(fn ($record) => $record?->confidence_percentage ?? 'Няма данни')
                            ->extraAttributes(['class' => 'text-lg font-bold'])
                            ->badge()
                            ->color(fn ($record) => $record?->confidence_color ?? 'gray'),

                        TextInput::make('ai_confidence')
                            ->label('AI доверие (0.0-1.0)')
                            ->numeric()
                            ->step(0.0001)
                            ->minValue(0)
                            ->maxValue(1)
                            ->prefixIcon('heroicon-o-calculator')
                            ->helperText('Точно ниво на доверие от AI анализа'),

                        TextEntry::make('status_label')
                            ->label('Статус на български')
                            ->state(fn ($record) => $record?->status_label ?? 'Неизвестно')
                            ->extraAttributes(['class' => 'font-medium'])
                            ->badge()
                            ->color(fn ($record) => $record?->status_color ?? 'gray'),

                        TextEntry::make('amendment_type_label')
                            ->label('Тип изменение на български')
                            ->state(fn ($record) => $record?->amendment_type_label ?? 'Няма данни')
                            ->extraAttributes(['class' => 'font-medium']),
                    ])
                    ->columns(2),

                Section::make('🗳️ Резултати от гласуване')
                    ->description('Данни за гласуването, ако е налично')
                    ->icon('heroicon-o-hand-raised')
                    ->schema([
                        TextEntry::make('formatted_vote_results')
                            ->label('Резултати от гласуването')
                            ->state(fn ($record) => $record?->formatted_vote_results ?? 'Няма данни за гласуване')
                            ->extraAttributes(['class' => 'text-lg font-medium'])
                            ->columnSpanFull(),

                        Textarea::make('vote_results')
                            ->label('JSON данни за гласуването')
                            ->disabled()
                            ->rows(3)
                            ->state(fn ($record) => $record?->vote_results ? json_encode($record->vote_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null)
                            ->helperText('Сурови данни в JSON формат')
                            ->columnSpanFull(),
                    ]),

                Section::make('📋 Връзки и контекст')
                    ->description('Връзки към други записи и контекстуална информация')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Select::make('transcript_id')
                            ->label('Стенограма')
                            ->relationship('transcript', 'title')
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-document')
                            ->helperText('Стенограмата, от която е извлечен анализът'),

                        Select::make('bill_id')
                            ->label('Свързан законопроект')
                            ->relationship('bill', 'title')
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-document-text')
                            ->helperText('Законопроектът, който се анализира (ако е идентифициран)'),

                        TextEntry::make('transcript.title')
                            ->label('Заглавие на стенограма')
                            ->state(fn ($record) => $record?->transcript?->title ?? 'Няма данни')
                            ->limit(100)
                            ->columnSpanFull(),

                        TextEntry::make('bill.title')
                            ->label('Заглавие на законопроект')
                            ->state(fn ($record) => $record?->bill?->title ?? 'Не е свързан')
                            ->limit(100)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('📝 Описание и контекст')
                    ->description('Подробно описание на изменението и контекст')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('amendment_description')
                            ->label('Описание на изменението')
                            ->rows(4)
                            ->helperText('Подробно описание на предложеното изменение')
                            ->columnSpanFull(),

                        Textarea::make('raw_context')
                            ->label('Суров контекст')
                            ->disabled()
                            ->rows(6)
                            ->helperText('Оригиналният текст от стенограмата, използван за анализа')
                            ->columnSpanFull(),

                        Textarea::make('metadata')
                            ->label('Метаданни')
                            ->disabled()
                            ->rows(3)
                            ->state(fn ($record) => $record?->metadata ? json_encode($record->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null)
                            ->helperText('Допълнителни данни в JSON формат')
                            ->columnSpanFull(),
                    ]),

                Section::make('⏰ Системна информация')
                    ->description('Дати на създаване и актуализиране')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Създаден на')
                            ->state(fn ($record) => $record?->created_at?->format('d.m.Y H:i:s') ?? 'Няма данни')
                            ->extraAttributes(['class' => 'text-gray-600']),

                        TextEntry::make('updated_at')
                            ->label('Актуализиран на')
                            ->state(fn ($record) => $record?->updated_at?->format('d.m.Y H:i:s') ?? 'Няма данни')
                            ->extraAttributes(['class' => 'text-gray-600']),
                    ])
                    ->columns(2),
            ]);
    }
}