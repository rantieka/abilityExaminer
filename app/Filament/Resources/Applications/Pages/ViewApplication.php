<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Mail\ApplicationAccepted;
use App\Mail\ApplicationRejected;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

use Filament\Actions\ActionGroup;

class ViewApplication extends ViewRecord {
  protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array {
    return [
      // Action: Send Initial Test Invitation
      Action::make('send_accepted_email')
        ->label('Send Test Invitation')
        ->icon('heroicon-o-paper-airplane')
        ->color('success')
        ->button()
        ->form([
          TextInput::make('test_token')
            ->label('Test Token')
            ->default(fn () => $this->record->test_token ?? Str::random(64))
            ->required()
            ->suffixAction(
              Action::make('regenerate')
                ->icon('heroicon-m-arrow-path')
                ->action(function ($set) {
                  $set('test_token', Str::random(64));
                })
            ),
          DateTimePicker::make('token_expires_at')
              ->label('Token Expiration')
              ->default(fn () => now()->addDays(7))
              ->required()
              ->native(false)
              ->minDate(now()),
        ])
        ->modalHeading('Send Online Test Invitation')
        ->modalDescription(fn () => "Send an online test invitation email to {$this->record->full_name}. Status will change to 'Accepted'.")
        ->modalSubmitActionLabel('Send Invitation')
        ->before(function (Action $action) {
            $jobVacancy = $this->record->jobVacancy;

            if (! $jobVacancy) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body('Job Vacancy data is missing.')
                    ->send();
                $action->halt();
            }

            $hasQuestions = $jobVacancy->questions()
                ->where('is_active', true)
                ->exists();

            if (! $hasQuestions) {
                Notification::make()
                    ->danger()
                    ->title('Failed to Send Test')
                    ->body('No active test questions available for this vacancy. Please create them first.')
                    ->persistent()
                    ->send();

                $action->halt();
            }
        })
        ->action(function (array $data, ViewApplication $livewire) {
          try {
            $this->record->update([
              'test_token' => $data['test_token'],
              'token_expires_at' => $data['token_expires_at'],
              'status' => 'accepted',
              'email_sent_at' => now(),
              'email_type' => 'accepted',
            ]);

            Mail::to($this->record->email)->send(new ApplicationAccepted($this->record));
            
            $url = route('email.preview.accepted', $this->record->id);
            
              Notification::make()
                ->success()
                ->title('Email Sent')
                ->body(new HtmlString("Test invitation has been sent to {$this->record->full_name}.<br><a href='{$url}' target='_blank' style='font-weight: bold; text-decoration: underline;'>Open Email Preview</a>"))
                ->persistent()
                ->send();

            $livewire->js("window.open('$url', '_blank')");
            
          } catch (\Exception $e) {
            Notification::make()
              ->danger()
              ->title('Failed to Send Email')
              ->body($e->getMessage())
              ->send();
          }
        })
        ->visible(fn () => !in_array($this->record->status, ['accepted', 'rejected']) && auth()->user()->hasRole(['hr', 'super_admin'])),

      // Action: Resend Test Invitation
      Action::make('resend_accepted_email')
        ->label('Resend Test Link')
        ->icon('heroicon-o-arrow-path')
        ->color('info')
        ->button()
        ->form([
          TextInput::make('test_token')
            ->label('Test Token')
            ->default(fn () => $this->record->test_token ?? Str::random(64))
            ->required()
            ->suffixAction(
              Action::make('regenerate_resend')
                ->icon('heroicon-m-arrow-path')
                ->action(function ($set) {
                  $set('test_token', Str::random(64));
                })
            ),
          DateTimePicker::make('token_expires_at')
              ->label('Expiration Date')
              ->default(fn () => $this->record->token_expires_at ?? now()->addDays(7))
              ->required()
              ->native(false)
              ->minDate(now()),
        ])
        ->modalHeading('Resend Test Link')
        ->modalDescription(fn () => "Resend the test access link to {$this->record->full_name}. You can update the token if the previous one has expired.")
        ->modalSubmitActionLabel('Resend Email')
        ->action(function (array $data, ViewApplication $livewire) {
          try {
            $this->record->update([
              'test_token' => $data['test_token'],
              'token_expires_at' => $data['token_expires_at'],
              'email_sent_at' => now(),
            ]);

            Mail::to($this->record->email)->send(new ApplicationAccepted($this->record));
            
            $url = route('email.preview.accepted', $this->record->id);
            
              Notification::make()
                ->success()
                ->title('Email Resent Successfully')
                ->body(new HtmlString("Test link has been resent to {$this->record->full_name}.<br><a href='{$url}' target='_blank' style='font-weight: bold; text-decoration: underline;'>Open Email Preview</a>"))
                ->persistent()
                ->send();

            $livewire->js("window.open('$url', '_blank')");
            
          } catch (\Exception $e) {
            Notification::make()
              ->danger()
              ->title('Failed to Resend Email')
              ->body($e->getMessage())
              ->send();
          }
        })
        ->visible(fn () => $this->record->status === 'accepted' && auth()->user()->hasRole(['hr', 'super_admin'])),

      // Action: Send Rejection Email
      Action::make('send_rejected_email')
        ->label('Reject Application')
        ->icon('heroicon-o-x-circle')
        ->color('danger')
        ->button()
        ->form([
          Textarea::make('rejection_reason')
            ->label('Rejection Reason (Optional)')
            ->placeholder('Example: Qualifications do not meet current needs')
            ->rows(3)
            ->maxLength(500),
        ])
        ->requiresConfirmation()
        ->modalHeading('Reject Application')
        ->modalDescription(fn () => "Reject application for {$this->record->full_name} ({$this->record->email})?")
        ->modalSubmitActionLabel('Reject & Send Email')
        ->action(function (array $data, ViewApplication $livewire) {
          try {
            if (!empty($data['rejection_reason'])) {
              $this->record->rejection_reason = $data['rejection_reason'];
              $this->record->save();
            }

            Mail::to($this->record->email)->send(new ApplicationRejected($this->record));
            
            $this->record->update([
              'status' => 'rejected',
              'email_sent_at' => now(),
              'email_type' => 'rejected',
            ]);

            $url = route('email.preview.rejected', $this->record->id);
            
            Notification::make()
                ->success()
                ->title('Application Rejected')
                ->body(new HtmlString("Rejection email sent to {$this->record->full_name}.<br><a href='{$url}' target='_blank' style='font-weight: bold; text-decoration: underline;'>Open Email Preview</a>"))
                ->persistent()
                ->send();

            $livewire->js("window.open('$url', '_blank')");
            
          } catch (\Exception $e) {
            Notification::make()
              ->danger()
              ->title('Error')
              ->body($e->getMessage())
              ->send();
          }
        })
        ->visible(fn () => $this->record->status !== 'rejected' && auth()->user()->hasRole(['hr', 'super_admin'])),
    ];
    }
}
