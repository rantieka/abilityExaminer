<?php

namespace App\Filament\Resources\JobVacancies\Pages;

use App\Filament\Resources\JobVacancies\JobVacancyResource;
use Filament\Resources\Pages\ViewRecord;

class ViewJobVacancy extends ViewRecord
{
  protected static string $resource = JobVacancyResource::class;

  protected function getHeaderActions(): array
  {
    return [
      // Tidak ada action untuk SPV - hanya view
      // HR/Super Admin bisa punya action di sini jika perlu
    ];
  }
}
