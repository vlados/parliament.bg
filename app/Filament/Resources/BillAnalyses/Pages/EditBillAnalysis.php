<?php

namespace App\Filament\Resources\BillAnalyses\Pages;

use App\Filament\Resources\BillAnalyses\BillAnalysisResource;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBillAnalysis extends EditRecord
{
    protected static string $resource = BillAnalysisResource::class;
    
    protected static ?string $title = 'Редактиране на анализ';
    
    public function getHeading(): string
    {
        return 'Редактиране на анализ на законопроект';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Преглед')
                ->icon('heroicon-o-eye'),
            DeleteAction::make()
                ->label('Изтрий')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}