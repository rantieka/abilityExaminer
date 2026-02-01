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
        ->label('Approve & Publish')
        ->icon('heroicon-o-check-circle')
        ->color('success')
        ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole(['hr', 'super_admin']))
        ->form([
            \Filament\Forms\Components\DatePicker::make('published_until')
                ->label('Publish Until')
                ->required()
                ->native(false)
                ->minDate(now()),
        ])
        ->modalHeading('Approve & Publish')
        ->modalDescription('Please set the publication expiry date.')
        ->modalSubmitActionLabel('Publish')
        ->action(function ($record, array $data) {
          $record->update([
            'status' => 'approved',
            'is_published' => true,
            'published_until' => $data['published_until'],
          ]);

          // Notification to Admin (Current User)
          \Filament\Notifications\Notification::make()
            ->success()
            ->title('Job vacancy published successfully!')
            ->send();

          // Notification to Requestor (SPV)
          if ($record->createdBy && $record->created_by !== auth()->id()) {
              \Filament\Notifications\Notification::make()
                ->success()
                ->title('Your Job Vacancy Request was Approved!')
                ->body("Job vacancy \"{$record->title}\" has been approved and published.")
                ->actions([
                    \Filament\Actions\Action::make('view')
                        ->button()
                        ->markAsRead()
                        ->url(JobVacancyResource::getUrl('index')),
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
          
          // Notification to Admin
          \Filament\Notifications\Notification::make()
            ->success()
            ->title('Job vacancy rejected successfully!')
            ->body("Job vacancy \"{$record->title}\" has been rejected.")
            ->send();

          // Notification to Requestor (SPV)
          if ($record->createdBy && $record->created_by !== auth()->id()) {
              \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Your Job Vacancy Request was Rejected')
                ->body("Reason: " . $data['rejection_reason'])
                ->actions([
                    \Filament\Actions\Action::make('view')
                        ->button()
                        ->markAsRead()
                        ->url(JobVacancyResource::getUrl('index')),
                ])
                ->sendToDatabase($record->createdBy);
          }
        })
        ->successNotificationTitle('Job vacancy rejected!')
        ->after(fn () => redirect()->route('filament.admin.resources.job-vacancies.index')),
      DeleteAction::make(),
    ];
  }

  // Hide Save/Cancel buttons since HR only approves
  protected function getFormActions(): array
  {
      return [];
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
