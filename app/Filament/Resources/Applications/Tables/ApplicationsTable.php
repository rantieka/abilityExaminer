<?php

namespace App\Filament\Resources\Applications\Tables;

use App\Mail\ApplicationAccepted;
use App\Mail\ApplicationRejected;
use App\Models\Application; // Added
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class ApplicationsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->poll('5s')
      ->defaultSort('created_at', 'desc')
      ->columns([
        TextColumn::make('no')
          ->label('No.')
          ->rowIndex(),
        TextColumn::make('jobVacancy.title')
          ->label('Posisi Pekerjaan')
          ->searchable(),
        // TextColumn::make('user.name')
        //   ->searchable(),
        TextColumn::make('full_name')
          ->label('Nama Lengkap')
          ->searchable(),
        TextColumn::make('email')
          ->label('Alamat Email')
          ->searchable(),
        TextColumn::make('phone')
          ->label('No. Telepon')
          ->searchable(),
        // TextColumn::make('cv_path')
        //   ->searchable(),
        TextColumn::make('status')
          ->label('Status')
          ->searchable()
          ->badge()
          ->formatStateUsing(fn (string $state, $record): string => match (true) {
            $state === 'pending' && $record->ai_score === null => 'Memindai CV...',
            $state === 'pending' && $record->ai_score !== null => 'Menunggu Peninjauan',
            $state === 'reviewed' => 'Sudah Ditinjau',
            $state === 'accepted' && $record->announcement_status === 'published' => 'Diterima Bekerja',
            $state === 'accepted' && $record->test_completed_at !== null && $record->hrd_decision === 'recommended' && $record->supervisor_decision === 'pending' => 'Peninjauan Supervisor',
            $state === 'accepted' && $record->test_completed_at !== null && in_array($record->supervisor_decision, ['approved', 'rejected']) && $record->announcement_status !== 'published' => 'Siap Diumumkan',
            $state === 'accepted' && $record->test_completed_at !== null => 'Ujian Selesai',
            $state === 'accepted' && $record->test_completed_at === null => 'Ujian Aktif',
            $state === 'rejected' && $record->announcement_status === 'published' => 'Ditolak (Final)',
            $state === 'rejected' && $record->hrd_decision === 'rejected' => 'Ditolak oleh HRD',
            $state === 'rejected' => 'Ditolak',
            default => ucfirst($state),
          })
          ->color(fn (string $state, $record): string => match (true) {
            $state === 'accepted' && $record->announcement_status === 'published' => 'success',
            $state === 'accepted' && $record->test_completed_at !== null && $record->hrd_decision === 'recommended' && $record->supervisor_decision === 'pending' => 'info',
            $state === 'accepted' && $record->test_completed_at !== null && in_array($record->supervisor_decision, ['approved', 'rejected']) && $record->announcement_status !== 'published' => 'warning',
            $state === 'accepted' && $record->test_completed_at !== null => 'primary',
            $state === 'accepted' && $record->test_completed_at === null => 'warning',
            $state === 'rejected' => 'danger',
            $state === 'reviewed' => 'info',
            $state === 'pending' => 'gray',
            default => 'gray',
          }),
        TextColumn::make('ai_score')
          ->label('Skor CV')
          ->numeric()
          ->sortable()
          ->badge()
          ->formatStateUsing(fn ($state, $record) => match (true) {
             is_numeric($state) => $state . '%',
             $record->status === 'pending' && $record->ai_score === null => 'Menghitung...',
             default => '-'
          })
          ->color(fn ($state, $record) => match (true) {
            $record->status === 'pending' && $record->ai_score === null => 'gray',
            $state >= 80 => 'success',
            $state >= 50 => 'warning',
            default => 'danger',
          })
          ->icon(fn ($state, $record) => ($record->status === 'pending' && $record->ai_score === null) ? 'heroicon-m-arrow-path' : null),
        TextColumn::make('test_score')
          ->label('Skor Ujian')
          ->badge()
          ->sortable()
          ->default('-')
          ->formatStateUsing(function ($state, $record) {
              if (is_numeric($state)) {
                  return $state . '/100';
              }
              if ($state === null && $record->status === 'accepted') {
                  return 'Menunggu';
              }
              return $state; // If null, default('-') will take over
          })
          ->color(fn ($state, $record) => match (true) {
              $state === null && $record->status === 'accepted' => 'warning',
              $state === null || $state === '-' => 'gray',
              is_numeric($state) && $state >= 70 => 'success',
              is_numeric($state) && $state >= 40 => 'warning',
              default => 'danger',
          })
          ->icon(fn ($state, $record) => match (true) {
              $state === null && $record->status === 'accepted' => 'heroicon-m-clock',
              default => null,
          }),
        // TextColumn::make('email_type')
        //   ->label('Email Status')
        //   ->badge()
        //   ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'Not Sent')
        //   ->color(fn ($state) => match ($state) {
        //     'accepted' => 'success',
        //     'rejected' => 'danger',
        //     default => 'gray',
        //   })
        //   ->toggleable(),
        TextColumn::make('email_sent_at')
          ->label('Email Dikirim Pada')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->label('Tanggal Melamar')
          ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->locale('id')->translatedFormat('d M Y') : '-')
          ->sortable(),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        //
      ])
      ->recordActions([
        // EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
          
          // Bulk Action: Send Acceptance Emails
          Action::make('bulk_send_accepted')
            ->label('Kirim Undangan Ujian')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Kirim Undangan Ujian ke Kandidat Terpilih')
            ->modalDescription(fn (Collection $records) => "Kirim email undangan ujian ke {$records->count()} kandidat terpilih?")
            ->modalSubmitActionLabel('Kirim Semua Email')
            ->modalCancelActionLabel('Batal')
            ->action(function (Collection $records) {
              $successCount = 0;
              $failCount = 0;
              $skippedCount = 0;

              foreach ($records as $record) {
                // Skip if already processed
                if (in_array($record->status, ['accepted', 'rejected'])) {
                    $skippedCount++;
                    continue;
                }

                try {
                  // Generate Test Token and Expiration
                  $test_token = \Illuminate\Support\Str::random(64);
                  $token_expires_at = now()->addDays(7); // Token valid for 7 days
                  
                  $record->update([
                    'status' => 'accepted',
                    'email_sent_at' => now(),
                    'email_type' => 'accepted',
                    'test_token' => $test_token,
                    'token_expires_at' => $token_expires_at,
                  ]);

                  // Send email after updating record so it has access to the token
                  Mail::to($record->email)->send(new ApplicationAccepted($record));
                  
                  $successCount++;
                } catch (\Exception $e) {
                  $failCount++;
                }
              }

              if ($successCount > 0) {
                Notification::make()
                  ->success()
                  ->title('Email Terkirim')
                  ->body("{$successCount} email undangan berhasil dikirim" . 
                        ($skippedCount > 0 ? ", {$skippedCount} dilewati (sudah diproses)" : "") . 
                        ($failCount > 0 ? ", {$failCount} gagal" : ""))
                  ->send();
              } elseif ($skippedCount > 0 && $failCount === 0) {
                  Notification::make()
                    ->warning()
                    ->title('Tidak Ada Email Terkirim')
                    ->body("Semua {$skippedCount} pelamar yang dipilih sudah diproses.")
                    ->send();
              }

              if ($failCount > 0 && $successCount === 0) {
                Notification::make()
                  ->danger()
                  ->title('Gagal Mengirim Email')
                  ->body("Semua {$failCount} email gagal terkirim")
                  ->send();
              }
            }),

          // Bulk Action: Send Rejection Emails
          Action::make('bulk_send_rejected')
            ->label('Tolak Lamaran')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->form([
              Textarea::make('rejection_reason')
                ->label('Alasan Penolakan (Opsional)')
                ->placeholder('Alasan ini akan dikirim ke semua kandidat terpilih')
                ->rows(3)
                ->maxLength(500),
            ])
            ->requiresConfirmation()
            ->modalHeading('Tolak Lamaran Kandidat Terpilih')
            ->modalDescription(fn (Collection $records) => "Kirim email penolakan ke {$records->count()} kandidat terpilih?")
            ->modalSubmitActionLabel('Tolak & Kirim Semua Email')
            ->modalCancelActionLabel('Batal')
            ->action(function (Collection $records, array $data) {
              $successCount = 0;
              $failCount = 0;
              $skippedCount = 0;

              foreach ($records as $record) {
                // Skip if already processed
                if (in_array($record->status, ['accepted', 'rejected'])) {
                    $skippedCount++;
                    continue;
                }

                try {
                  // Update rejection reason if provided
                  if (!empty($data['rejection_reason'])) {
                    $record->rejection_reason = $data['rejection_reason'];
                    $record->save();
                  }

                  Mail::to($record->email)->send(new ApplicationRejected($record));
                  
                  $record->update([
                    'status' => 'rejected',
                    'email_sent_at' => now(),
                    'email_type' => 'rejected',
                  ]);
                  
                  $successCount++;
                } catch (\Exception $e) {
                  $failCount++;
                }
              }

              if ($successCount > 0) {
                Notification::make()
                  ->success()
                  ->title('Email Terkirim')
                  ->body("{$successCount} email penolakan berhasil dikirim" . 
                        ($skippedCount > 0 ? ", {$skippedCount} dilewati (sudah diproses)" : "") . 
                        ($failCount > 0 ? ", {$failCount} gagal" : ""))
                  ->send();
              } elseif ($skippedCount > 0 && $failCount === 0) {
                  Notification::make()
                    ->warning()
                    ->title('Tidak Ada Email Terkirim')
                    ->body("Semua {$skippedCount} pelamar yang dipilih sudah diproses.")
                    ->send();
              }

              if ($failCount > 0 && $successCount === 0) {
                Notification::make()
                  ->danger()
                  ->title('Gagal Mengirim Email')
                  ->body("Semua {$failCount} email gagal terkirim")
                  ->send();
              }
            }),
        ]),
      ]);
  }
}
