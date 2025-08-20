<?php

namespace App\Filament\Resources\BillAnalyses\Pages;

use App\Filament\Resources\BillAnalyses\BillAnalysisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillAnalysis extends CreateRecord
{
    protected static string $resource = BillAnalysisResource::class;
    
    protected static ?string $title = 'Създаване на анализ';
    
    public function getHeading(): string
    {
        return 'Създаване на нов анализ на законопроект';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}