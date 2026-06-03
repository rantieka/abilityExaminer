<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Table;

class ExpiringTokensWidget extends BaseWidget
{
  protected static ?string $heading = 'Daftar Pelamar Menjelang Batas Waktu Ujian';
  protected static ?int $sort = 5;
  protected int | string | array $columnSpan = 'full';

  public static function canView(): bool
  {
    return auth()->check() && auth()->user()->hasRole(['hr', 'super_admin']);
  }

  public function table(Table $table): Table
  {
    return $table
      ->query(
        Application::query()
          ->where('status', 'accepted')
          ->whereNull('test_completed_at')
          ->whereNotNull('test_token')
          ->whereBetween('token_expires_at', [now(), now()->addHours(48)])
          ->orderBy('token_expires_at', 'asc')
          ->limit(5)
      )
      ->columns([
        TextColumn::make('full_name')
          ->label('Nama Lengkap')
          ->searchable(),
        TextColumn::make('email')
          ->label('Email'),
        TextColumn::make('jobVacancy.title')
          ->label('Posisi'),
        TextColumn::make('token_expires_at')
          ->label('Batas Waktu')
          ->dateTime('d M Y, H:i')
          ->badge()
          ->color('danger'),
      ])
      ->paginated(false);
  }
}
