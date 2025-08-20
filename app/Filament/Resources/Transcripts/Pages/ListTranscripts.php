<?php

namespace App\Filament\Resources\Transcripts\Pages;

use App\Filament\Resources\Transcripts\TranscriptResource;
use Filament\Resources\Pages\ListRecords;

class ListTranscripts extends ListRecords
{
    protected static string $resource = TranscriptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for transcripts - they're scraped from API
        ];
    }
}