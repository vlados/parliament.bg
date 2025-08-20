<?php

namespace App\Filament\Resources\ParliamentMembers\Pages;

use App\Filament\Resources\ParliamentMembers\ParliamentMemberResource;
use Filament\Resources\Pages\ViewRecord;

class ViewParliamentMember extends ViewRecord
{
    protected static string $resource = ParliamentMemberResource::class;
    
    protected static ?string $title = 'Преглед на народен представител';
    
    public function getHeading(): string
    {
        return 'Преглед на народен представител';
    }
}