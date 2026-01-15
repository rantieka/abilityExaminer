<?php

namespace App\Filament\Resources\JobVacancies\Pages;

use App\Filament\Resources\JobVacancies\JobVacancyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;

class EditJobVacancy extends EditRecord
{
  protected static string $resource = JobVacancyResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Action::make('approve')
        ->label('Approve & Publish')
        ->icon('heroicon-o-check-circle')
        ->color('success')
        ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole(['hr', 'super_admin']))
        ->requiresConfirmation()
        ->action(function ($record) {
          $record->update([
            'status' => 'approved',
            'is_published' => true,
          ]);
        }),
      DeleteAction::make(),
    ];
  }
}
