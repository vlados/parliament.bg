<?php

namespace App\Filament\Resources\Committees;

use App\Filament\Resources\Committees\Pages\ListCommittees;
use App\Filament\Resources\Committees\Pages\ViewCommittee;
use App\Filament\Resources\Committees\Schemas\CommitteeForm;
use App\Filament\Resources\Committees\Tables\CommitteesTable;
use App\Models\Committee;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommitteeResource extends Resource
{
    protected static ?string $model = Committee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Комисии';

    protected static UnitEnum|string|null $navigationGroup = 'Парламент';
    
    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Комисия';

    protected static ?string $pluralModelLabel = 'Комисии';

    public static function form(Schema $schema): Schema
    {
        return CommitteeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommitteesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BillsRelationManager::class,
            RelationManagers\TranscriptsRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => ListCommittees::route('/'),
            'view' => ViewCommittee::route('/{record}'),
        ];
    }
}
