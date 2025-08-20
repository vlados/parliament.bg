<?php

namespace App\Filament\Resources\Committees\Pages;

use App\Filament\Resources\Committees\CommitteeResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCommittee extends ViewRecord
{
    protected static string $resource = CommitteeResource::class;
    
    protected static ?string $title = 'Преглед на комисия';
    
    public function getHeading(): string
    {
        return 'Преглед на комисия';
    }
}