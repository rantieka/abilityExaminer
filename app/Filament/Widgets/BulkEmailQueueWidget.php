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
  protected static ?string $heading = 'Daftar Pelamar Lolos Seleksi CV';
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
          ->where('ai_score', '>=', 50)
          ->orderBy('ai_score', 'desc')
      )
      ->columns([
        TextColumn::make('full_name')
          ->label('Nama Lengkap')
          ->searchable(),
        TextColumn::make('jobVacancy.title')
          ->label('Posisi'),
        TextColumn::make('ai_score')
          ->label('CV Score')
          ->formatStateUsing(fn ($state) => $state . '%')
          ->badge()
          ->color(fn ($state) => $state >= 80 ? 'success' : 'warning'),
        TextColumn::make('status')
          ->label('Status')
          ->badge()
          ->color(fn ($state) => $state === 'reviewed' ? 'info' : 'gray'),
      ])
      ->actions([
        Action::make('invite')
          ->label('Kirim Undangan')
          ->button()
          ->color('success')
          ->icon('heroicon-m-paper-airplane')
          ->requiresConfirmation()
          ->modalHeading('Kirim Undangan Ujian')
          ->modalDescription(fn ($record) => "Kirim email undangan ujian online ke {$record->full_name}?")
          ->action(function (Application $record) {
            try {
              $test_token = \Illuminate\Support\Str::random(64);
              $token_expires_at = now()->addDays(7);
              
              $record->update([
                'status' => 'accepted',
                'email_sent_at' => now(),
                'email_type' => 'accepted',
                'test_token' => $test_token,
                'token_expires_at' => $token_expires_at,
              ]);

              Mail::to($record->email)->send(new ApplicationAccepted($record));

              Notification::make()
                ->success()
                ->title('Email Terkirim')
                ->body("Undangan tes berhasil dikirim ke {$record->full_name}.")
                ->send();
            } catch (\Exception $e) {
              Notification::make()
                ->danger()
                ->title('Gagal Mengirim Email')
                ->body($e->getMessage())
                ->send();
            }
          }),
      ]);
  }
}
