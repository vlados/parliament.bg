<?php

namespace App\Filament\Resources\Transcripts\Pages;

use App\Filament\Resources\Transcripts\TranscriptResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTranscript extends ViewRecord
{
    protected static string $resource = TranscriptResource::class;
    
    protected static ?string $title = 'Преглед на стенограма';
    
    public function getHeading(): string
    {
        return 'Преглед на стенограма';
    }
}