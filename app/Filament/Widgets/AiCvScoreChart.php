<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;

class AiCvScoreChart extends ChartWidget
{
  protected ?string $heading = 'Distribusi Skor CV';
  protected static ?int $sort = 3;
  protected static string $type = 'doughnut';
  protected int | string | array $columnSpan = 1;
  protected ?string $maxHeight = '300px';
  protected string $view = 'filament.widgets.ai-cv-score-chart';

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

    $suitable = (clone $query)->where('ai_score', '>=', 80)->count();
    $neutral = (clone $query)->where('ai_score', '>=', 50)->where('ai_score', '<', 80)->count();
    $notSuitable = (clone $query)->where('ai_score', '<', 50)->count();
    $scanning = (clone $query)->whereNull('ai_score')->count();

    $total = $suitable + $neutral + $notSuitable + $scanning;

    if ($total === 0) {
      return [
        'datasets' => [
          [
            'label' => 'Distribusi Skor',
            'data' => [0, 0, 0, 0, 1],
            'backgroundColor' => ['#4ade80', '#fbbf24', '#f87171', '#94a3b8', '#e2e8f0'],
            'borderColor' => 'transparent',
          ],
        ],
        'labels' => [
          "Sesuai (≥80%) : 0 Pelamar",
          "Netral (50-79%) : 0 Pelamar",
          "Tidak Sesuai (<50%) : 0 Pelamar",
          "Memindai / Tanpa Skor : 0 Pelamar"
        ],
      ];
    }

    return [
      'datasets' => [
        [
          'label' => 'Distribusi Skor',
          'data' => [$suitable, $neutral, $notSuitable, $scanning],
          'backgroundColor' => ['#4ade80', '#fbbf24', '#f87171', '#94a3b8'],
          'borderColor' => 'transparent',
        ],
      ],
      'labels' => ["Sesuai (≥80%) : {$suitable} Pelamar", "Netral (50-79%) : {$neutral} Pelamar", "Tidak Sesuai (<50%) : {$notSuitable} Pelamar", "Memindai / Tanpa Skor : {$scanning} Pelamar"],
    ];
  }

  protected function getOptions(): array
  {
    return [
      'plugins' => [
        'legend' => [
          'position' => 'right',
        ],
      ],
      'cutout' => '70%', // makes the ring thinner/smaller
      'radius' => '80%', // scales down the outer size of the doughnut
    ];
  }

  protected function getType(): string
  {
    return 'doughnut';
  }
}
