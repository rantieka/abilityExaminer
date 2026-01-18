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

  protected function getHeaderActions(): array {
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
      Action::make('reject')
        ->label('Reject')
        ->icon('heroicon-o-x-circle')
        ->color('danger')
        ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole(['hr', 'super_admin']))
        ->form([
          Textarea::make('rejection_reason')
            ->label('Rejection Reason')
            ->placeholder('Please provide a reason for rejecting this job vacancy...')
            ->required()
            ->rows(4)
            ->maxLength(1000),
        ])
        ->modalHeading('Reject Job Vacancy')
        ->modalDescription('Please provide a reason for rejecting this job vacancy.')
        ->modalSubmitActionLabel('Reject')
        ->action(function ($record, array $data) {
          $record->update([
            'status' => 'rejected',
            'is_published' => false,
            'rejection_reason' => $data['rejection_reason'],
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
          ]);
          
          \Filament\Notifications\Notification::make()
            ->success()
            ->title('Job vacancy rejected successfully!')
            ->body("Job vacancy \"{$record->title}\" has been rejected.")
            ->send();
        })
        ->successNotificationTitle('Job vacancy rejected!')
        ->after(fn () => redirect()->route('filament.admin.resources.job-vacancies.index')),
      DeleteAction::make(),
    ];
  }

  protected function getUpdatedNotificationTitle(): ?string {
    return 'Job vacancy successfully updated!';
  }

  protected function getDeletedNotificationTitle(): ?string {
    return 'Job vacancy successfully deleted!';
  }

  protected function getRejectedNotificationTitle(): ?string {
    return 'Job vacancy successfully rejected!';
  }

  protected function getApprovedNotificationTitle(): ?string {
    return 'Job vacancy successfully approved!';
  }
}
