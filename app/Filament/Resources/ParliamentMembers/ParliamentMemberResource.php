<?php

namespace App\Filament\Resources\ParliamentMembers;

use App\Filament\Resources\ParliamentMembers\Pages\CreateParliamentMember;
use App\Filament\Resources\ParliamentMembers\Pages\EditParliamentMember;
use App\Filament\Resources\ParliamentMembers\Pages\ListParliamentMembers;
use App\Filament\Resources\ParliamentMembers\Schemas\ParliamentMemberForm;
use App\Filament\Resources\ParliamentMembers\Tables\ParliamentMembersTable;
use App\Models\ParliamentMember;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParliamentMemberResource extends Resource
{
    protected static ?string $model = ParliamentMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    
    protected static ?string $navigationLabel = 'Народни представители';
    
    protected static ?string $modelLabel = 'Народен представител';
    
    protected static ?string $pluralModelLabel = 'Народни представители';

    public static function form(Schema $schema): Schema
    {
        return ParliamentMemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParliamentMembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParliamentMembers::route('/'),
        ];
    }
}
