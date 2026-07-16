<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use App\Mail\ApplicationAccepted;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;

class BulkEmailQueueWidget extends BaseWidget
{
  protected static ?string $heading = 'Daftar Pelamar Baru';
  protected static ?int $sort = 4;
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
          ->whereIn('status', ['pending', 'reviewed'])
          ->latest()
          ->limit(5)
      )
      ->columns([
        TextColumn::make('full_name')
          ->label('Nama Lengkap')
          ->searchable(),
        TextColumn::make('jobVacancy.title')
          ->label('Posisi'),
        TextColumn::make('status')
          ->label('Status')
          ->badge()
          ->color(fn ($state) => $state === 'reviewed' ? 'info' : 'gray')
          ->formatStateUsing(fn (string $state): string => match ($state) {
            'pending' => 'Menunggu Peninjauan',
            'reviewed' => 'Sudah Ditinjau',
            default => ucfirst($state),
          }),
        TextColumn::make('ai_score')
          ->label('Skor CV')
          ->formatStateUsing(fn ($state) => $state !== null ? $state . '%' : 'Memindai...')
          ->badge()
          ->color(fn ($state) => match(true) {
              $state === null => 'gray',
              $state >= 80 => 'success',
              default => 'warning',
          }),
      ])
      ->actions([
        Action::make('view')
          ->label('Lihat')
          ->icon('heroicon-o-eye')
          ->color('gray')
          ->url(fn (Application $record): string =>
            \App\Filament\Resources\Applications\ApplicationResource::getUrl('view', ['record' => $record])
          ),
      ])
      ->paginated(false);
  }
}
