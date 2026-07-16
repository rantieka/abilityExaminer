<?php

namespace App\Filament\Resources\JobVacancies\Pages;

use App\Filament\Resources\JobVacancies\JobVacancyResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;

class ViewJobVacancy extends ViewRecord
{
  protected static string $resource = JobVacancyResource::class;

  public function getTitle(): string
  {
      return $this->record->title;
  }

  public function getBreadcrumb(): string
  {
      return 'Detail';
  }

  protected function getHeaderActions(): array
  {
    return [
      Action::make('approve')
        ->label('Setujui & Publikasikan')
        ->icon('heroicon-o-check-circle')
        ->color('success')
        ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole(['hr', 'super_admin']))
        ->form([
            DatePicker::make('published_until')
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

          Notification::make()
            ->success()
            ->title('Lowongan kerja berhasil dipublikasikan!')
            ->send();

          if ($record->createdBy && $record->created_by !== auth()->id()) {
              Notification::make()
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
          
          Notification::make()
            ->success()
            ->title('Lowongan kerja berhasil ditolak!')
            ->send();

          if ($record->createdBy && $record->created_by !== auth()->id()) {
              Notification::make()
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
        }),
    ];
  }
}
