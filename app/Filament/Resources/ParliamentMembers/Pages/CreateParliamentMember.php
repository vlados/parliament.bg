<?php

namespace App\Filament\Resources\ParliamentMembers\Pages;

use App\Filament\Resources\ParliamentMembers\ParliamentMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateParliamentMember extends CreateRecord
{
    protected static string $resource = ParliamentMemberResource::class;
}
