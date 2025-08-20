<?php

namespace App\Filament\Resources\BillAnalyses\Pages;

use App\Filament\Resources\BillAnalyses\BillAnalysisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillAnalyses extends ListRecords
{
    protected static string $resource = BillAnalysisResource::class;

    protected static ?string $title = 'Анализи на законопроекти';
    
    public function getHeading(): string 
    {
        return 'Анализи на законопроекти';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Създай анализ')
                ->icon('heroicon-o-plus'),
        ];
    }
}