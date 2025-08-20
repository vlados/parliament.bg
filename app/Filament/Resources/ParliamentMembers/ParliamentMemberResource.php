<?php

namespace App\Filament\Resources\ParliamentMembers;

use App\Filament\Resources\ParliamentMembers\Pages\ListParliamentMembers;
use App\Filament\Resources\ParliamentMembers\Pages\ViewParliamentMember;
use App\Filament\Resources\ParliamentMembers\Schemas\ParliamentMemberForm;
use App\Filament\Resources\ParliamentMembers\Tables\ParliamentMembersTable;
use App\Models\ParliamentMember;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParliamentMemberResource extends Resource
{
    protected static ?string $model = ParliamentMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    
    protected static ?string $navigationLabel = 'Народни представители';
    
    protected static UnitEnum|string|null $navigationGroup = 'Парламент';
    
    protected static ?int $navigationSort = 1;
    
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
            RelationManagers\CommitteesRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => ListParliamentMembers::route('/'),
            'view' => ViewParliamentMember::route('/{record}'),
        ];
    }
}
