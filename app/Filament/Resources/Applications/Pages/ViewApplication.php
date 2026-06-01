<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Mail\ApplicationAccepted;
use App\Mail\ApplicationRejected;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

use Filament\Actions\ActionGroup;

class ViewApplication extends ViewRecord {
  protected static string $resource = ApplicationResource::class;

  public function getTitle(): string
  {
      return $this->record->full_name;
  }

  public function getBreadcrumb(): string
  {
      return 'Detail';
  }

    protected function getHeaderActions(): array {
    return [
      // Action: Send Initial Test Invitation
      Action::make('send_accepted_email')
        ->label('Kirim Undangan Ujian')
        ->icon('heroicon-o-paper-airplane')
        ->color('success')
        ->button()
        ->form([
          TextInput::make('test_token')
            ->label('Token Ujian')
            ->default(fn () => $this->record->test_token ?? Str::random(64))
            ->required()
            ->suffixAction(
              Action::make('regenerate')
                ->icon('heroicon-m-arrow-path')
                ->action(function ($set) {
                  $set('test_token', Str::random(64));
                })
            ),
          DateTimePicker::make('token_expires_at')
              ->label('Masa Berlaku Token')
              ->default(fn () => now()->addDays(7))
              ->required()
              ->native(false)
              ->minDate(now()),
        ])
        ->modalHeading('Kirim Undangan Ujian Online')
        ->modalDescription(fn () => "Kirim email undangan ujian online ke {$this->record->full_name}. Status pelamar akan berubah menjadi 'Diterima' (untuk ujian).")
        ->modalSubmitActionLabel('Kirim Undangan')
        ->modalCancelActionLabel('Batal')
        ->before(function (Action $action) {
            $jobVacancy = $this->record->jobVacancy;

            if (! $jobVacancy) {
                Notification::make()
                    ->danger()
                    ->title('Kesalahan')
                    ->body('Data Lowongan Pekerjaan tidak ditemukan.')
                    ->send();
                $action->halt();
            }

            $hasQuestions = $jobVacancy->questions()
                ->where('is_active', true)
                ->exists();

            if (! $hasQuestions) {
                Notification::make()
                    ->danger()
                    ->title('Gagal Mengirim Ujian')
                    ->body('Tidak ada pertanyaan ujian aktif untuk lowongan ini. Silakan buat pertanyaan terlebih dahulu.')
                    ->persistent()
                    ->send();

                $action->halt();
            }
        })
        ->action(function (array $data, ViewApplication $livewire) {
          try {
            $this->record->update([
              'test_token' => $data['test_token'],
              'token_expires_at' => $data['token_expires_at'],
              'status' => 'accepted',
              'email_sent_at' => now(),
              'email_type' => 'accepted',
            ]);

            Mail::to($this->record->email)->send(new ApplicationAccepted($this->record));
            
            $url = route('email.preview.accepted', $this->record->id);
            
              Notification::make()
                ->success()
                ->title('Email Terkirim')
                ->body(new HtmlString("Undangan ujian telah dikirim ke {$this->record->full_name}.<br><a href='{$url}' target='_blank' style='font-weight: bold; text-decoration: underline;'>Buka Pratinjau Email</a>"))
                ->persistent()
                ->send();

            $livewire->js("window.open('$url', '_blank')");
            
          } catch (\Exception $e) {
            Notification::make()
              ->danger()
              ->title('Gagal Mengirim Email')
              ->body($e->getMessage())
              ->send();
          }
        })
        ->visible(fn () => !in_array($this->record->status, ['accepted', 'rejected']) && auth()->user()->hasRole(['hr', 'super_admin'])),

      // Action: Resend Test Invitation
      Action::make('resend_accepted_email')
        ->label('Kirim Ulang Link Ujian')
        ->icon('heroicon-o-arrow-path')
        ->color('info')
        ->button()
        ->form([
          TextInput::make('test_token')
            ->label('Token Ujian')
            ->default(fn () => $this->record->test_token ?? Str::random(64))
            ->required()
            ->suffixAction(
              Action::make('regenerate_resend')
                ->icon('heroicon-m-arrow-path')
                ->action(function ($set) {
                  $set('test_token', Str::random(64));
                })
            ),
          DateTimePicker::make('token_expires_at')
              ->label('Tanggal Kedaluwarsa')
              ->default(fn () => $this->record->token_expires_at ?? now()->addDays(7))
              ->required()
              ->native(false)
              ->minDate(now()),
        ])
        ->modalHeading('Kirim Ulang Link Ujian')
        ->modalDescription(fn () => "Kirim ulang tautan akses ujian ke {$this->record->full_name}. Anda dapat memperbarui token jika token sebelumnya telah kedaluwarsa.")
        ->modalSubmitActionLabel('Kirim Ulang Email')
        ->modalCancelActionLabel('Batal')
        ->action(function (array $data, ViewApplication $livewire) {
          try {
            $this->record->update([
              'test_token' => $data['test_token'],
              'token_expires_at' => $data['token_expires_at'],
              'email_sent_at' => now(),
            ]);

            Mail::to($this->record->email)->send(new ApplicationAccepted($this->record));
            
            $url = route('email.preview.accepted', $this->record->id);
            
              Notification::make()
                ->success()
                ->title('Email Berhasil Dikirim Ulang')
                ->body(new HtmlString("Tautan ujian telah dikirim ulang ke {$this->record->full_name}.<br><a href='{$url}' target='_blank' style='font-weight: bold; text-decoration: underline;'>Buka Pratinjau Email</a>"))
                ->persistent()
                ->send();

            $livewire->js("window.open('$url', '_blank')");
            
          } catch (\Exception $e) {
            Notification::make()
              ->danger()
              ->title('Gagal Mengirim Ulang Email')
              ->body($e->getMessage())
              ->send();
          }
        })
        ->visible(fn () => $this->record->status === 'accepted' && $this->record->test_completed_at === null && auth()->user()->hasRole(['hr', 'super_admin'])),

      // Action: Send Rejection Email
      Action::make('send_rejected_email')
        ->label('Tolak Lamaran')
        ->icon('heroicon-o-x-circle')
        ->color('danger')
        ->button()
        ->form([
          Textarea::make('rejection_reason')
            ->label('Alasan Penolakan (Opsional)')
            ->placeholder('Contoh: Kualifikasi belum memenuhi kebutuhan saat ini')
            ->rows(3)
            ->maxLength(500),
        ])
        ->requiresConfirmation()
        ->modalHeading('Tolak Lamaran')
        ->modalDescription(fn () => "Tolak lamaran untuk {$this->record->full_name} ({$this->record->email})?")
        ->modalSubmitActionLabel('Tolak & Kirim Email')
        ->modalCancelActionLabel('Batal')
        ->action(function (array $data, ViewApplication $livewire) {
          try {
            if (!empty($data['rejection_reason'])) {
              $this->record->rejection_reason = $data['rejection_reason'];
              $this->record->save();
            }

            Mail::to($this->record->email)->send(new ApplicationRejected($this->record));
            
            $this->record->update([
              'status' => 'rejected',
              'email_sent_at' => now(),
              'email_type' => 'rejected',
            ]);

            $url = route('email.preview.rejected', $this->record->id);
            
            Notification::make()
                ->success()
                ->title('Lamaran Ditolak')
                ->body(new HtmlString("Email penolakan telah dikirim ke {$this->record->full_name}.<br><a href='{$url}' target='_blank' style='font-weight: bold; text-decoration: underline;'>Buka Pratinjau Email</a>"))
                ->persistent()
                ->send();

            $livewire->js("window.open('$url', '_blank')");
            
          } catch (\Exception $e) {
            Notification::make()
              ->danger()
              ->title('Kesalahan')
              ->body($e->getMessage())
              ->send();
          }
        })
        ->visible(fn () => $this->record->status !== 'rejected' && $this->record->test_completed_at === null && auth()->user()->hasRole(['hr', 'super_admin'])),
    ];
    }
}
