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
    return 'Job vacancy successfully created!';
  }
}
