<?php

namespace App\Filament\Resources\JobVacancies\Pages;

use App\Filament\Resources\JobVacancies\JobVacancyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;

class EditJobVacancy extends EditRecord
{
  protected static string $resource = JobVacancyResource::class;

  public function mount(int | string $record): void
  {
      parent::mount($record);

      $id = $this->record->id;
      
      // FIX: Check URL inside the array data directly to avoid slash escaping issues with json_encode
      auth()->user()->unreadNotifications->each(function ($n) use ($id) {
          $actions = $n->data['actions'] ?? [];
          foreach ($actions as $action) {
              // Strict check for /edit to ensure we match the specific resource edit link
              if (isset($action['url']) && str_contains($action['url'], "/job-vacancies/{$id}/edit")) {
                  $n->markAsRead();
                  break;
              }
          }
      });
  }

  protected function getHeaderActions(): array {
    return [
      Action::make('approve')
        ->label('Setujui & Publikasikan')
        ->icon('heroicon-o-check-circle')
        ->color('success')
        ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole(['hr', 'super_admin']))
        ->form([
            \Filament\Forms\Components\DatePicker::make('published_until')
                ->label('Publikasikan Hingga')
                ->required()
                ->native(false)
                ->minDate(now()),
        ])
        ->modalHeading('Setujui & Publikasikan Lowongan')
        ->modalDescription('Silakan tentukan batas tanggal kadaluarsa publikasi lowongan.')
        ->modalSubmitActionLabel('Publikasikan')
        ->action(function ($record, array $data) {
          $record->update([
            'status' => 'approved',
            'is_published' => true,
            'published_until' => $data['published_until'],
          ]);

          // Notification to Admin (Current User)
          \Filament\Notifications\Notification::make()
            ->success()
            ->title('Lowongan kerja berhasil dipublikasikan!')
            ->send();

          // Notification to Requestor (SPV)
          if ($record->createdBy && $record->created_by !== auth()->id()) {
              \Filament\Notifications\Notification::make()
                ->success()
                ->title('Lowongan Kerja Disetujui!')
                ->body("Lowongan kerja \"{$record->title}\" kini telah aktif. Silakan lanjutkan untuk membuat pertanyaan ujian.")
                ->actions([
                    \Filament\Actions\Action::make('create_questions')
                        ->label('Buat Pertanyaan')
                        ->button()
                        ->markAsRead()
                        ->url(\App\Filament\Resources\Questions\QuestionResource::getUrl('index', ['job_id' => $record->id])),
                ])
                ->sendToDatabase($record->createdBy);
          }
        }),
      Action::make('reject')
        ->label('Tolak')
        ->icon('heroicon-o-x-circle')
        ->color('danger')
        ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole(['hr', 'super_admin']))
        ->form([
          Textarea::make('rejection_reason')
            ->label('Alasan Penolakan')
            ->placeholder('Berikan alasan penolakan lowongan kerja ini...')
            ->required()
            ->rows(4)
            ->maxLength(1000),
        ])
        ->modalHeading('Tolak Lowongan Kerja')
        ->modalDescription('Silakan berikan alasan penolakan lowongan kerja ini.')
        ->modalSubmitActionLabel('Tolak')
        ->action(function ($record, array $data) {
          $record->update([
            'status' => 'rejected',
            'is_published' => false,
            'rejection_reason' => $data['rejection_reason'],
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
          ]);
          
          // Notification to Admin
          \Filament\Notifications\Notification::make()
            ->success()
            ->title('Lowongan kerja berhasil ditolak!')
            ->body("Lowongan kerja \"{$record->title}\" telah ditolak.")
            ->send();

          // Notification to Requestor (SPV)
          if ($record->createdBy && $record->created_by !== auth()->id()) {
              \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Permintaan Lowongan Kerja Anda Ditolak')
                ->body("Lowongan kerja \"{$record->title}\" telah ditolak.")
                ->actions([
                    \Filament\Actions\Action::make('view')
                        ->label('Lihat')
                        ->button()
                        ->markAsRead()
                        ->url(JobVacancyResource::getUrl('index')),
                ])
                ->sendToDatabase($record->createdBy);
          }
        })
        ->successNotification(null)
        ->after(fn () => redirect()->route('filament.admin.resources.job-vacancies.index')),
    ];
  }

  protected function getUpdatedNotificationTitle(): ?string {
    return 'Lowongan kerja berhasil diperbarui!';
  }

  protected function getDeletedNotificationTitle(): ?string {
    return 'Lowongan kerja berhasil dihapus!';
  }

  protected function getRejectedNotificationTitle(): ?string {
    return 'Lowongan kerja berhasil ditolak!';
  }

  protected function getApprovedNotificationTitle(): ?string {
    return 'Lowongan kerja berhasil disetujui!';
  }
}
