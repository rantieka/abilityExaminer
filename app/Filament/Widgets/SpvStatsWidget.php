<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use App\Models\JobVacancy;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SpvStatsWidget extends BaseWidget
{
  protected static ?int $sort = 1;

  protected ?string $pollingInterval = '20s';

  public static function canView(): bool
  {
    return auth()->check() && auth()->user()->hasRole('spv');
  }

  /**
   * Get the IDs of job vacancies owned by the current SPV.
   */
  private function spvVacancyIds(): array
  {
    return JobVacancy::where('created_by', auth()->id())
      ->pluck('id')
      ->toArray();
  }

  protected function getStats(): array
  {
    $vacancyIds = $this->spvVacancyIds();

    // Perlu Direview: Selesai tes, belum ada keputusan SPV
    $needReview = Application::whereIn('job_vacancy_id', $vacancyIds)
      ->whereNotNull('test_completed_at')
      ->whereNull('supervisor_decision')
      ->count();

    // Rata-rata Nilai Ujian dari divisi SPV
    $avgScore = Application::whereIn('job_vacancy_id', $vacancyIds)
      ->whereNotNull('test_score')
      ->avg('test_score');

    // Kandidat Disetujui bulan ini
    $approvedThisMonth = Application::whereIn('job_vacancy_id', $vacancyIds)
      ->where('supervisor_decision', 'approved')
      ->whereMonth('supervisor_decided_at', now()->month)
      ->whereYear('supervisor_decided_at', now()->year)
      ->count();

    return [
      Stat::make('Perlu Direview', $needReview)
        ->icon('heroicon-o-clock')
        ->extraAttributes(['class' => 'stat-card-custom stat-warning']),

      Stat::make('Rata-rata Nilai Ujian', $avgScore ? number_format($avgScore, 1) : '-')
        ->icon('heroicon-o-academic-cap')
        ->extraAttributes(['class' => 'stat-card-custom stat-primary']),

      Stat::make('Disetujui Bulan Ini', $approvedThisMonth)
        ->icon('heroicon-o-check-badge')
        ->extraAttributes(['class' => 'stat-card-custom stat-success']),
    ];
  }
}
