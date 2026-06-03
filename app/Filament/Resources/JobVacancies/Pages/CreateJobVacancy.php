<?php

namespace App\Filament\Resources\JobVacancies\Pages;

use App\Filament\Resources\JobVacancies\JobVacancyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobVacancy extends CreateRecord
{
  protected static string $resource = JobVacancyResource::class;

  protected function mutateFormDataBeforeCreate(array $data): array {
    $data['created_by'] = auth()->id();
    return $data;
  }

  protected function getCreatedNotificationTitle(): ?string {
    return 'Lowongan kerja berhasil dibuat!';
  }

  protected function afterCreate(): void
  {
      $recipient = \App\Models\User::role(['hr', 'super_admin'])->get();
      \Illuminate\Support\Facades\Log::info('Creating Job Vacancy Notification', [
          'creator' => auth()->id(),
          'recipients_count' => $recipient->count(),
          'recipient_ids' => $recipient->pluck('id'),
      ]);
      
      \Filament\Notifications\Notification::make()
          ->info()
          ->title('Permintaan Lowongan Kerja Baru')
          ->body("Lowongan kerja baru \"{$this->record->title}\" telah diajukan oleh " . auth()->user()->name)
          ->actions([
              \Filament\Actions\Action::make('view')
                  ->label('Lihat')
                  ->button()
                  ->markAsRead()
                  ->url(JobVacancyResource::getUrl('edit', ['record' => $this->record]), shouldOpenInNewTab: true),
          ])
          ->sendToDatabase($recipient);
  }
}
