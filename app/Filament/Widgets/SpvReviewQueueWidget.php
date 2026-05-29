<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use App\Models\JobVacancy;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class SpvReviewQueueWidget extends BaseWidget
{
  protected static ?int $sort = 2;
  protected int | string | array $columnSpan = 'full';
  protected static ?string $heading = 'Daftar Pelamar Perlu Direview';

  public static function canView(): bool
  {
    return auth()->check() && auth()->user()->hasRole('spv');
  }

  public function table(Table $table): Table
  {
    $vacancyIds = JobVacancy::where('created_by', auth()->id())
      ->pluck('id')
      ->toArray();

    return $table
      ->query(
        Application::query()
          ->whereIn('job_vacancy_id', $vacancyIds)
          ->whereNotNull('test_completed_at')
          ->whereNull('supervisor_decision')
          ->with('jobVacancy')
          ->latest('test_completed_at')
      )
      ->columns([
        TextColumn::make('full_name')
          ->label('Nama Kandidat')
          ->searchable()
          ->sortable()
          ->weight('semibold')
          ->url(fn (Application $record): string =>
            \App\Filament\Resources\Applications\ApplicationResource::getUrl('view', ['record' => $record])
          )
          ->openUrlInNewTab(),

        TextColumn::make('jobVacancy.title')
          ->label('Posisi')
          ->badge()
          ->color('primary')
          ->searchable(),

        TextColumn::make('ai_score')
          ->label('Skor CV (AI)')
          ->suffix('%')
          ->badge()
          ->color(fn ($state) => match(true) {
            $state === null => 'gray',
            $state >= 80   => 'success',
            $state >= 50   => 'warning',
            default        => 'danger',
          })
          ->formatStateUsing(fn ($state) => $state !== null ? $state : '-')
          ->sortable(),

        TextColumn::make('test_score')
          ->label('Skor Ujian')
          ->badge()
          ->color(fn ($state) => match(true) {
            $state === null => 'gray',
            $state >= 80   => 'success',
            $state >= 60   => 'warning',
            default        => 'danger',
          })
          ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1) . '%' : '-')
          ->sortable(),

        TextColumn::make('c45_decision')
          ->label('Rekomendasi C4.5')
          ->badge()
          ->color(fn ($state) => match($state) {
            'hire'   => 'success',
            'reject' => 'danger',
            default  => 'gray',
          })
          ->formatStateUsing(fn ($state) => match($state) {
            'hire'   => '✓ Disarankan Diterima',
            'reject' => '✗ Disarankan Ditolak',
            default  => 'Belum Ada',
          }),

        TextColumn::make('test_completed_at')
          ->label('Selesai Tes')
          ->since()
          ->sortable()
          ->tooltip(fn ($state) => $state?->format('d M Y H:i')),
      ])
      ->actions([
        Action::make('approve')
          ->label('Setujui')
          ->icon('heroicon-o-check-circle')
          ->color('success')
          ->requiresConfirmation()
          ->modalHeading('Setujui Kandidat')
          ->modalDescription(fn (Application $record) => "Apakah Anda yakin ingin menyetujui {$record->full_name}?")
          ->modalSubmitActionLabel('Ya, Setujui')
          ->form([
            Textarea::make('supervisor_notes')
              ->label('Catatan (Opsional)')
              ->placeholder('Tambahkan catatan keputusan Anda...')
              ->rows(3),
          ])
          ->action(function (Application $record, array $data): void {
            $record->update([
              'supervisor_decision'   => 'approved',
              'supervisor_notes'      => $data['supervisor_notes'] ?? null,
              'supervisor_decided_at' => now(),
            ]);

            Notification::make()
              ->success()
              ->title('Kandidat Disetujui')
              ->body("{$record->full_name} telah disetujui.")
              ->send();
          }),

        Action::make('reject')
          ->label('Tolak')
          ->icon('heroicon-o-x-circle')
          ->color('danger')
          ->requiresConfirmation()
          ->modalHeading('Tolak Kandidat')
          ->modalDescription(fn (Application $record) => "Apakah Anda yakin ingin menolak {$record->full_name}?")
          ->modalSubmitActionLabel('Ya, Tolak')
          ->form([
            Textarea::make('supervisor_notes')
              ->label('Alasan Penolakan (Opsional)')
              ->placeholder('Berikan alasan penolakan...')
              ->rows(3),
          ])
          ->action(function (Application $record, array $data): void {
            $record->update([
              'supervisor_decision'   => 'rejected',
              'supervisor_notes'      => $data['supervisor_notes'] ?? null,
              'supervisor_decided_at' => now(),
            ]);

            Notification::make()
              ->danger()
              ->title('Kandidat Ditolak')
              ->body("{$record->full_name} telah ditolak.")
              ->send();
          }),

        Action::make('view_detail')
          ->label('Detail')
          ->icon('heroicon-o-eye')
          ->color('gray')
          ->url(fn (Application $record): string =>
            \App\Filament\Resources\Applications\ApplicationResource::getUrl('view', ['record' => $record])
          )
          ->openUrlInNewTab(),
      ])
      ->emptyStateHeading('Tidak ada data')
      ->paginated([5, 10, 25])
      ->defaultPaginationPageOption(5);
  }
}
