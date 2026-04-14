<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApplicationStatsWidget extends BaseWidget
{
  protected function getStats(): array
  {
    return [
      Stat::make('Total Applicants', Application::count())
        ->description('Total applicants uploaded their CV')
        ->descriptionIcon('heroicon-m-document-text')
        ->color('primary'),
    ];
  }
}
