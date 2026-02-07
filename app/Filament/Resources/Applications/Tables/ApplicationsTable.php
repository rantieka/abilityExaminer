<?php

namespace App\Filament\Resources\Applications\Tables;

use App\Mail\ApplicationAccepted;
use App\Mail\ApplicationRejected;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class ApplicationsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->poll('5s')
      ->defaultSort('created_at', 'desc')
      ->columns([
        TextColumn::make('no')
          ->label('No.')
          ->rowIndex(),
        TextColumn::make('jobVacancy.title')
          ->label('Job Position')
          ->searchable(),
        // TextColumn::make('user.name')
        //   ->searchable(),
        TextColumn::make('full_name')
          ->searchable(),
        TextColumn::make('email')
          ->label('Email address')
          ->searchable(),
        TextColumn::make('phone')
          ->searchable(),
        // TextColumn::make('cv_path')
        //   ->searchable(),
        TextColumn::make('status')
          ->searchable()
          ->badge()
          ->formatStateUsing(fn (string $state, $record): string => match (true) {
            $state === 'pending' && $record->ai_score === null => 'Scanning CV...',
            $state === 'pending' && $record->ai_score !== null => 'Pending Review',
            $state === 'reviewed' => 'Reviewed',
            $state === 'accepted' => 'Accepted',
            $state === 'rejected' => 'Rejected',
            default => ucfirst($state),
          })
          ->color(fn (string $state, $record): string => match (true) {
            $state === 'accepted' => 'success',
            $state === 'rejected' => 'danger',
            $state === 'reviewed' => 'info',
            $state === 'pending' => 'warning', // Pending Review
            default => 'gray',
          })
          ->icon(fn (string $state, $record): ?string => match (true) {
            $state === 'pending' && $record->ai_score === null => 'heroicon-m-arrow-path', // Loading only if no score
            $state === 'reviewed' => 'heroicon-m-check',
            $state === 'accepted' => 'heroicon-m-check-circle',
            $state === 'rejected' => 'heroicon-m-x-circle',
            default => null,
          }),
        TextColumn::make('ai_score')
          ->numeric()
          ->sortable()
          ->badge()
          ->formatStateUsing(fn ($state, $record) => match (true) {
             is_numeric($state) => $state . '%',
             $record->status === 'pending' && $record->ai_score === null => 'Calculating...',
             default => '-'
          })
          ->color(fn ($state, $record) => match (true) {
            $record->status === 'pending' && $record->ai_score === null => 'gray',
            $state >= 80 => 'success',
            $state >= 50 => 'warning',
            default => 'danger',
          })
          ->icon(fn ($state, $record) => ($record->status === 'pending' && $record->ai_score === null) ? 'heroicon-m-arrow-path' : null),
        // TextColumn::make('email_type')
        //   ->label('Email Status')
        //   ->badge()
        //   ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'Not Sent')
        //   ->color(fn ($state) => match ($state) {
        //     'accepted' => 'success',
        //     'rejected' => 'danger',
        //     default => 'gray',
        //   })
        //   ->toggleable(),
        TextColumn::make('email_sent_at')
          ->label('Email Sent At')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->label('Apply Date')
          // ->dateTime('d M Y, H:i')
          ->dateTime('d M Y')
          ->sortable(),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        //
      ])
      ->recordActions([
        // EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
          
          // Bulk Action: Send Acceptance Emails
          Action::make('bulk_send_accepted')
            ->label('Send Acceptance Emails (Bulk)')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Send Acceptance Emails to Selected Candidates')
            ->modalDescription(fn (Collection $records) => "Send acceptance emails to {$records->count()} candidates?")
            ->modalSubmitActionLabel('Send All Emails')
            ->action(function (Collection $records) {
              $successCount = 0;
              $failCount = 0;
              $skippedCount = 0;

              foreach ($records as $record) {
                // Skip if already processed
                if (in_array($record->status, ['accepted', 'rejected'])) {
                    $skippedCount++;
                    continue;
                }

                try {
                  Mail::to($record->email)->send(new ApplicationAccepted($record));
                  
                  $record->update([
                    'status' => 'accepted',
                    'email_sent_at' => now(),
                    'email_type' => 'accepted',
                  ]);
                  
                  $successCount++;
                } catch (\Exception $e) {
                  $failCount++;
                }
              }

              if ($successCount > 0) {
                Notification::make()
                  ->success()
                  ->title('Emails Sent')
                  ->body("{$successCount} acceptance emails successfully sent" . 
                        ($skippedCount > 0 ? ", {$skippedCount} skipped (already processed)" : "") . 
                        ($failCount > 0 ? ", {$failCount} failed" : ""))
                  ->send();
              } elseif ($skippedCount > 0 && $failCount === 0) {
                  Notification::make()
                    ->warning()
                    ->title('No Emails Sent')
                    ->body("All {$skippedCount} selected applications were already processed.")
                    ->send();
              }

              if ($failCount > 0 && $successCount === 0) {
                Notification::make()
                  ->danger()
                  ->title('Failed to Send Emails')
                  ->body("All {$failCount} emails failed to send")
                  ->send();
              }
            }),

          // Bulk Action: Send Rejection Emails
          Action::make('bulk_send_rejected')
            ->label('Send Rejection Emails (Bulk)')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->form([
              Textarea::make('rejection_reason')
                ->label('Rejection Reason (Optional)')
                ->placeholder('This reason will be sent to all selected candidates')
                ->rows(3)
                ->maxLength(500),
            ])
            ->requiresConfirmation()
            ->modalHeading('Send Rejection Emails to Selected Candidates')
            ->modalDescription(fn (Collection $records) => "Send rejection emails to {$records->count()} candidates?")
            ->modalSubmitActionLabel('Send All Emails')
            ->action(function (Collection $records, array $data) {
              $successCount = 0;
              $failCount = 0;
              $skippedCount = 0;

              foreach ($records as $record) {
                // Skip if already processed
                if (in_array($record->status, ['accepted', 'rejected'])) {
                    $skippedCount++;
                    continue;
                }

                try {
                  // Update rejection reason if provided
                  if (!empty($data['rejection_reason'])) {
                    $record->rejection_reason = $data['rejection_reason'];
                    $record->save();
                  }

                  Mail::to($record->email)->send(new ApplicationRejected($record));
                  
                  $record->update([
                    'status' => 'rejected',
                    'email_sent_at' => now(),
                    'email_type' => 'rejected',
                  ]);
                  
                  $successCount++;
                } catch (\Exception $e) {
                  $failCount++;
                }
              }

              if ($successCount > 0) {
                Notification::make()
                  ->success()
                  ->title('Emails Sent')
                  ->body("{$successCount} rejection emails successfully sent" . 
                        ($skippedCount > 0 ? ", {$skippedCount} skipped (already processed)" : "") . 
                        ($failCount > 0 ? ", {$failCount} failed" : ""))
                  ->send();
              } elseif ($skippedCount > 0 && $failCount === 0) {
                  Notification::make()
                    ->warning()
                    ->title('No Emails Sent')
                    ->body("All {$skippedCount} selected applications were already processed.")
                    ->send();
              }

              if ($failCount > 0 && $successCount === 0) {
                Notification::make()
                  ->danger()
                  ->title('Failed to Send Emails')
                  ->body("All {$failCount} emails failed to send")
                  ->send();
              }
            }),
        ]),
      ]);
  }
}
