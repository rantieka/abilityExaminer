<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApplicationStatsWidget extends BaseWidget
{
  protected static ?int $sort = 1;

  protected ?string $pollingInterval = '20s';

  public static function canView(): bool
  {
    return auth()->check() && auth()->user()->hasRole(['hr', 'super_admin']);
  }

  protected function getStats(): array
  {
    // Total Active Applicants (not rejected & not published hired)
    $activeCount = Application::where('status', '!=', 'rejected')
      ->where(fn ($q) => $q->whereNull('announcement_status')->orWhere('announcement_status', '!=', 'published'))
      ->count();

    // Scanning CV (Pending & AI Score is null)
    $scanningCount = Application::where('status', 'pending')
      ->whereNull('ai_score')
      ->count();

    // Active Test (Accepted & Test not completed yet)
    $activeTestCount = Application::where('status', 'accepted')
      ->whereNull('test_completed_at')
      ->count();

    // Ready to Announce (Accepted, Test completed, Supervisor decided, Announcement not published)
    $readyAnnounceCount = Application::where('status', 'accepted')
      ->whereNotNull('test_completed_at')
      ->whereIn('supervisor_decision', ['approved', 'rejected'])
      ->where(fn ($q) => $q->whereNull('announcement_status')->orWhere('announcement_status', '!=', 'published'))
      ->count();

    return [
      Stat::make('Total Pelamar Aktif', $activeCount)
        ->icon('heroicon-o-users')
        ->extraAttributes(['class' => 'stat-card-custom stat-primary']),

      Stat::make('Scanning CV (Pending)', $scanningCount)
        ->icon('heroicon-o-arrow-path')
        ->extraAttributes(['class' => 'stat-card-custom stat-gray']),

      Stat::make('Aktif Ujian', $activeTestCount)
        ->icon('heroicon-o-academic-cap')
        ->extraAttributes(['class' => 'stat-card-custom stat-warning']),

      Stat::make('Siap Diumumkan', $readyAnnounceCount)
        ->icon('heroicon-o-bell')
        ->extraAttributes(['class' => 'stat-card-custom stat-success']),
    ];
  }
}
