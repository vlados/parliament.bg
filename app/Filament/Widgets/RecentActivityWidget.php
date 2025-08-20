<?php

namespace App\Filament\Widgets;

use App\Models\Bill;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;

class RecentActivityWidget extends BaseWidget
{
    protected static ?string $heading = 'Скорошни законопроекти';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Bill::query()->with('committee')->latest('bill_date')->limit(5)
            )
            ->columns([
                TextColumn::make('sign')
                    ->label('Номер')
                    ->badge()
                    ->color('primary')
                    ->size('sm'),

                TextColumn::make('title')
                    ->label('Заглавие')
                    ->limit(60)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 60 ? $state : null;
                    }),

                TextColumn::make('committee.name')
                    ->label('Комисия')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('bill_date')
                    ->label('Дата')
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Преглед')
                    ->url(fn ($record) => route('filament.backoffice.resources.bills.view', $record)),
            ])
            ->paginated(false);
    }
}
