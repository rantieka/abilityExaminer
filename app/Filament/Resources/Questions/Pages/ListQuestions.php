<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Jobs\GenerateExamQuestions;
use App\Models\JobVacancy;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

use Livewire\Attributes\Url; // Added import

class ListQuestions extends ListRecords
{
  protected static string $resource = QuestionResource::class;

  #[Url]
  public ?string $job_id = null;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make()->label('Tambah Pertanyaan'),
    ];
  }

  protected function getJobVacancyId(): ?int
  {
    // Filament stores table filters in the query string as tableFilters[filter_name][value]
    $filters = request()->query('tableFilters');
    return $filters['job_vacancy_id']['value'] ?? null;
  }
}
