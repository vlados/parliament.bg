<?php

namespace App\Filament\Resources\Bills\Pages;

use App\Filament\Resources\Bills\BillResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBill extends ViewRecord
{
    protected static string $resource = BillResource::class;
    
    protected static ?string $title = 'Преглед на законопроект';
    
    public function getHeading(): string
    {
        return 'Преглед на законопроект';
    }
}