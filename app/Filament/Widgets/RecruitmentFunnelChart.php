<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;

class RecruitmentFunnelChart extends ChartWidget
{
  protected ?string $heading = 'Rekrutmen Funnel';
  protected static ?int $sort = 2;
  protected int | string | array $columnSpan = 1;
  protected string $view = 'filament.widgets.recruitment-funnel-chart';

  // Filter Properties
  public ?string $selectedPosition = '';
  public ?string $selectedMonth = '';

  public static function canView(): bool
  {
    return auth()->check() && auth()->user()->hasRole(['hr', 'super_admin']);
  }

  public function getPositions(): array
  {
    return \App\Models\JobVacancy::pluck('title', 'id')->toArray();
  }

  public function getMonths(): array
  {
    return [
      '01' => 'Januari',
      '02' => 'Februari',
      '03' => 'Maret',
      '04' => 'April',
      '05' => 'Mei',
      '06' => 'Juni',
      '07' => 'Juli',
      '08' => 'Agustus',
      '09' => 'September',
      '10' => 'Oktober',
      '11' => 'November',
      '12' => 'Desember',
    ];
  }

  // Reset cached data when filters change so chart re-fetches
  public function updatedSelectedPosition(): void
  {
    $this->cachedData = null;
    $this->updateChartData();
  }

  public function updatedSelectedMonth(): void
  {
    $this->cachedData = null;
    $this->updateChartData();
  }

  protected function getData(): array
  {
    $query = Application::query();

    if ($this->selectedPosition) {
      $query->where('job_vacancy_id', $this->selectedPosition);
    }

    if ($this->selectedMonth) {
      $query->whereMonth('created_at', $this->selectedMonth);
    }

    // Clone query for each step to calculate correctly
    $applied = (clone $query)->count();

    $passedCv = (clone $query)->where(fn ($q) =>
      $q->whereIn('status', ['accepted', 'rejected'])->orWhere('status', 'reviewed')
    )->count();

    $testCompleted = (clone $query)->whereNotNull('test_completed_at')->count();

    $hired = (clone $query)->where('status', 'accepted')
      ->where('announcement_status', 'published')
      ->count();

    return [
      'datasets' => [
        [
          'label' => 'Jumlah Pelamar',
          'data' => [$applied, $passedCv, $testCompleted, $hired],
          'backgroundColor' => '#d97706',
          'borderColor' => '#d97706',
          'borderRadius' => 6,
        ],
      ],
      'labels' => ['Total Pelamar', 'Lolos CV Screening', 'Selesai Ujian', 'Diterima (Hired)'],
    ];
  }

  protected function getOptions(): array
  {
    return [
      'plugins' => [
        'legend' => [
          'display' => false,
        ],
      ],
      'scales' => [
        'y' => [
          'beginAtZero' => true,
          'ticks' => [
            'stepSize' => 1,
          ],
        ],
      ],
    ];
  }

  protected function getType(): string
  {
    return 'bar';
  }
}
