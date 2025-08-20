<?php

namespace App\Filament\Widgets;

use App\Models\ParliamentMember;
use App\Models\Committee;
use App\Models\Bill;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ParliamentOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $activeCommittees = Committee::where(function ($query) {
            $query->whereNull('date_to')->orWhere('date_to', '>', now());
        })->count();
        
        $recentBills = Bill::where('bill_date', '>=', now()->subDays(30))->count();
        
        return [
            Stat::make('Народни представители', ParliamentMember::count())
                ->description('Общ брой депутати')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('Активни комисии', $activeCommittees)
                ->description('Комисии в действие')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),
                
            Stat::make('Общо комисии', Committee::count())
                ->description('Включително завършили')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('gray'),
                
            Stat::make('Законопроекти', Bill::count())
                ->description('Общо регистрирани')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
                
            Stat::make('Нови за месеца', $recentBills)
                ->description('Последните 30 дни')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),
        ];
    }
    
    protected function getColumns(): int
    {
        return 3;
    }
}