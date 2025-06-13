<?php

namespace App\Filament\Resources\ParliamentMembers\Pages;

use App\Filament\Resources\ParliamentMembers\ParliamentMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParliamentMembers extends ListRecords
{
    protected static string $resource = ParliamentMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
