<?php

namespace App\Filament\Resources\JobVacancies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use App\Models\JobVacancy;
use Filament\Actions\Action;
use Filament\Tables\Filters\TernaryFilter;

class JobVacanciesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('no')
          ->label('No.')
          ->rowIndex(),
        TextColumn::make('title')
          ->searchable()
          ->description(fn (JobVacancy $record) => $record->employment_type),
        TextColumn::make('slug')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('createdBy.name')
          ->label('Created By')
          ->sortable()
          ->searchable(),
        TextColumn::make('required_count')
          ->label('Quota')
          ->sortable(),
        TextColumn::make('published_until')
          ->date('d M Y')
          ->sortable()
          ->placeholder('No Limit'),
        TextColumn::make('status')
          ->badge()
          ->color(fn (string $state): string => match ($state) {
            'approved' => 'success',
            'draft' => 'gray',
            'pending' => 'warning',
            'rejected' => 'danger',
            default => 'info',
          })
          ->formatStateUsing(fn (string $state): string => ucfirst($state))
          ->searchable()
          ->sortable(),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('archived_at')
            ->label('Archived')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true)
            ->color('danger'),
    ])
    ->filters([
      TernaryFilter::make('archived')
        ->label('Archived Status')
        ->placeholder('All Vacancies')
        ->trueLabel('Archived Only')
        ->falseLabel('Active Only')
        ->queries(
            true: fn ($query) => $query->whereNotNull('archived_at'),
            false: fn ($query) => $query->whereNull('archived_at'),
            blank: fn ($query) => $query,
        )
        ->default(false),
    ])
    ->recordActions([
      ViewAction::make()
        ->extraAttributes(['style' => 'text-decoration: none !important']),
      /* 
      EditAction::make()
        ->extraAttributes(['style' => 'text-decoration: none !important'])
        ->visible(function (JobVacancy $record) {
            $user = auth()->user();
            
            if ($user->hasRole(['hr', 'super_admin'])) {
                return true;
            }
            
            if ($user->hasRole('spv')) {
                return in_array($record->status, ['draft', 'pending']);
            }
            
            return false;
        }),
      */
      Action::make('questions')
        ->label('Questions')
        ->icon('heroicon-o-document-text')
        ->color('info')
        ->url(fn (JobVacancy $record) => \App\Filament\Resources\Questions\QuestionResource::getUrl('index', [
            'job_id' => $record->id,
        ]))
        ->extraAttributes(['style' => 'text-decoration: none !important']),
      Action::make('manage_publication')
        ->label('Publication')
        ->icon('heroicon-o-calendar')
        ->color('info')
        ->visible(fn (JobVacancy $record) => $record->status === 'approved' && auth()->user()->hasRole(['hr', 'super_admin']))
        ->extraAttributes(['style' => 'text-decoration: none !important'])
        ->form([
            \Filament\Forms\Components\DatePicker::make('published_until')
                ->label('Publication Date')
                ->required()
                ->native(false)
                ->default(fn (JobVacancy $record) => $record->published_until)
                ->minDate(now()),
        ])
        ->fillForm(fn (JobVacancy $record) => ['published_until' => $record->published_until])
        ->modalHeading('Publication Date')
        ->action(function (JobVacancy $record, array $data) {
            $record->update([
                'published_until' => $data['published_until'],
            ]);
            
            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Publication date updated!')
                ->send();
        }),
      Action::make('archive')
        ->icon('heroicon-o-archive-box')
        ->color('danger')
        ->requiresConfirmation()
        ->visible(fn (JobVacancy $record) => $record->archived_at === null && $record->status === 'approved' && auth()->user()->hasRole(['hr', 'super_admin', 'spv']))
        ->extraAttributes(['style' => 'text-decoration: none !important'])
        ->action(fn (JobVacancy $record) => $record->update(['archived_at' => now()])),
      Action::make('unarchive')
        ->icon('heroicon-o-arrow-path')
        ->color('success')
        ->requiresConfirmation()
        ->extraAttributes(['style' => 'text-decoration: none !important'])
        ->visible(fn (JobVacancy $record) => $record->archived_at !== null && auth()->user()->hasRole(['hr', 'super_admin', 'spv']))
        ->form([
            \Filament\Forms\Components\DatePicker::make('published_until')
                ->label('New Publication Date')
                ->helperText('Set a new expiry date for this unarchived vacancy.')
                ->required()
                ->native(false)
                ->default(fn (JobVacancy $record) => $record->published_until && $record->published_until >= now() ? $record->published_until : now()->addDays(30))
                ->minDate(now()),
        ])
        ->action(function (JobVacancy $record, array $data) {
            $record->update([
                'archived_at' => null,
                'published_until' => $data['published_until'],
            ]);

            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Job vacancy unarchived and reactivated!')
                ->send();
        }),
      DeleteAction::make()
        ->visible(fn (JobVacancy $record) => in_array($record->status, ['draft', 'rejected', 'pending']) && auth()->user()->hasRole(['hr', 'super_admin'])),
    ]);
    /* 
    ->bulkActions([
      BulkActionGroup::make([
        DeleteBulkAction::make()
          ->visible(fn () => auth()->user()->hasRole(['hr', 'super_admin'])),
      ]),
    ]);
    */
  }
}
