<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Mail\ApplicationAccepted;
use App\Mail\ApplicationRejected;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditApplication extends EditRecord
{
  protected static string $resource = ApplicationResource::class;

  protected function getHeaderActions(): array
  {
    return [
      DeleteAction::make(),
      
      // Action: Send Acceptance Email
      Action::make('send_accepted_email')
        ->label('Send Acceptance Email')
        ->icon('heroicon-o-check-circle')
        ->color('success')
        ->requiresConfirmation()
        ->modalHeading('Send Acceptance Email')
        ->modalDescription(fn () => "Send acceptance email to {$this->record->full_name} ({$this->record->email})?")
        ->modalSubmitActionLabel('Send Email')
        ->action(function () {
          try {
            Mail::to($this->record->email)->send(new ApplicationAccepted($this->record));
            
            $this->record->update([
              'status' => 'accepted',
              'email_sent_at' => now(),
              'email_type' => 'accepted',
            ]);

            Notification::make()
              ->success()
              ->title('Email Terkirim')
              ->body("Email penerimaan berhasil dikirim ke {$this->record->full_name}")
              ->send();
              
            // Redirect to list after sending
            return redirect()->to(ApplicationResource::getUrl('index'));
          } catch (\Exception $e) {
            Notification::make()
              ->danger()
              ->title('Gagal Mengirim Email')
              ->body($e->getMessage())
              ->send();
          }
        })
        ->visible(fn () => $this->record->status !== 'accepted'),

      // Action: Send Rejection Email
      Action::make('send_rejected_email')
        ->label('Send Rejection Email')
        ->icon('heroicon-o-x-circle')
        ->color('danger')
        ->form([
          Textarea::make('rejection_reason')
            ->label('Rejection Reason (Optional)')
            ->placeholder('Example: Qualifications do not meet current needs')
            ->rows(3)
            ->maxLength(500),
        ])
        ->requiresConfirmation()
        ->modalHeading('Send Rejection Email')
        ->modalDescription(fn () => "Send rejection email to {$this->record->full_name} ({$this->record->email})?")
        ->modalSubmitActionLabel('Send Email')
        ->action(function (array $data) {
          try {
            // Update rejection reason if provided
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

            Notification::make()
              ->success()
              ->title('Email Sent')
              ->body("Rejection email successfully sent to {$this->record->full_name}")
              ->send();
              
            // Redirect to list after sending
            return redirect()->to(ApplicationResource::getUrl('index'));
          } catch (\Exception $e) {
            Notification::make()
              ->danger()
              ->title('Failed to Send Email')
              ->body($e->getMessage())
              ->send();
          }
        })
        ->visible(fn () => $this->record->status !== 'rejected'),
    ];
  }

  // Hide Save/Cancel buttons
  protected function getFormActions(): array
  {
      return [];
  }
}
