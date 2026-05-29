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

  public static function canView(): bool
  {
    return auth()->check() && auth()->user()->hasRole(['hr', 'super_admin']);
  }

  protected function getData(): array
  {
    $suitable = Application::where('ai_score', '>=', 80)->count();
    $neutral = Application::where('ai_score', '>=', 50)->where('ai_score', '<', 80)->count();
    $notSuitable = Application::where('ai_score', '<', 50)->count();
    $scanning = Application::whereNull('ai_score')->count();

    return [
      'datasets' => [
        [
          'label' => 'Distribusi Skor',
          'data' => [$suitable, $neutral, $notSuitable, $scanning],
          'backgroundColor' => ['#4ade80', '#fbbf24', '#f87171', '#94a3b8'],
          'borderColor' => 'transparent',
        ],
      ],
      'labels' => ['Suitable (≥80%)', 'Neutral (50-79%)', 'Not Suitable (<50%)', 'Scanning / No Score'],
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
