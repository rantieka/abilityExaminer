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
        ->label('Approve')
        ->icon('heroicon-o-check-circle')
        ->color('success')
        ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole(['hr', 'super_admin']))
        ->form([
            DatePicker::make('published_until')
                ->label('Publish Until')
                ->required()
                ->native(false)
                ->minDate(now()),
        ])
        ->modalHeading('Approve')
        ->modalDescription('Please set the publication expiry date.')
        ->modalSubmitActionLabel('Approve')
        ->action(function ($record, array $data) {
          $record->update([
            'status' => 'approved',
            'is_published' => true,
            'published_until' => $data['published_until'],
          ]);

          Notification::make()
            ->success()
            ->title('Job vacancy published successfully!')
            ->send();

          if ($record->createdBy && $record->created_by !== auth()->id()) {
              Notification::make()
                ->success()
                ->title('Job Vacancy Approved!')
                ->body("Job vacancy \"{$record->title}\" is now active. Please proceed to create the test questions.")
                ->actions([
                    \Filament\Actions\Action::make('create_questions')
                        ->label('Create Questions')
                        ->button()
                        ->markAsRead()
                        ->url(\App\Filament\Resources\Questions\QuestionResource::getUrl('index', ['job_id' => $record->id])),
                ])
                ->sendToDatabase($record->createdBy);
          }
        }),
      Action::make('reject')
        ->label('Reject')
        ->icon('heroicon-o-x-circle')
        ->color('danger')
        ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole(['hr', 'super_admin']))
        ->form([
          Textarea::make('rejection_reason')
            ->label('Rejection Reason')
            ->placeholder('Please provide a reason for rejecting... ')
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
          
          Notification::make()
            ->success()
            ->title('Job vacancy rejected successfully!')
            ->send();

          if ($record->createdBy && $record->created_by !== auth()->id()) {
              Notification::make()
                ->danger()
                ->title('Your Job Vacancy Request was Rejected')
                ->body("Job vacancy \"{$record->title}\" has been rejected.")
                ->actions([
                    \Filament\Actions\Action::make('view')
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
