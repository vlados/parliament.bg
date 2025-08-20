<?php

namespace App\Filament\Resources\Transcripts;

use App\Filament\Resources\Transcripts\Pages\ListTranscripts;
use App\Filament\Resources\Transcripts\Pages\ViewTranscript;
use App\Filament\Resources\Transcripts\Schemas\TranscriptForm;
use App\Filament\Resources\Transcripts\Tables\TranscriptsTable;
use App\Models\Transcript;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TranscriptResource extends Resource
{
    protected static ?string $model = Transcript::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;
    
    protected static ?string $navigationLabel = 'Стенограми';
    
    protected static UnitEnum|string|null $navigationGroup = 'Комисии';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $modelLabel = 'Стенограма';
    
    protected static ?string $pluralModelLabel = 'Стенограми';

    public static function form(Schema $schema): Schema
    {
        return TranscriptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TranscriptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranscripts::route('/'),
            'view' => ViewTranscript::route('/{record}'),
        ];
    }
}