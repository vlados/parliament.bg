<?php

namespace App\Filament\Resources\BillAnalyses\Pages;

use App\Filament\Resources\BillAnalyses\BillAnalysisResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBillAnalysis extends ViewRecord
{
    protected static string $resource = BillAnalysisResource::class;
    
    protected static ?string $title = 'Преглед на анализ';
    
    public function getHeading(): string
    {
        return 'Преглед на анализ на законопроект';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Редактирай')
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}