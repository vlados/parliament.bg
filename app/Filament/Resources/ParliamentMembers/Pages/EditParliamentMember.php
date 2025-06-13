<?php

namespace App\Filament\Resources\ParliamentMembers\Pages;

use App\Filament\Resources\ParliamentMembers\ParliamentMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditParliamentMember extends EditRecord
{
    protected static string $resource = ParliamentMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
