<?php

namespace App\Filament\Resources\JobVacancies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
          ->searchable(),
        TextColumn::make('slug')
          ->searchable(),
        TextColumn::make('createdBy.name')
          ->label('Created By')
          ->sortable()
          ->searchable(),
        TextColumn::make('status')
          ->badge()
          ->color(fn (string $state): string => match ($state) {
            'approved' => 'success',
            'draft' => 'gray',
            'pending' => 'warning',
            'rejected' => 'danger',
            default => 'info',
          })
          ->formatStateUsing(fn (string $state, $record): string => 
            ucfirst($state) . ($record->is_published ? ' (Published)' : ' (Draft)')
          )
          ->searchable()
          ->sortable(),
        TextColumn::make('rejection_reason')
          ->label('Rejection Reason')
          ->limit(50)
          ->tooltip(fn ($record) => $record->rejection_reason)
          ->formatStateUsing(fn ($state, $record) => 
            $record->status === 'rejected' ? ($state ?? '-') : '-'
          )
          ->wrap()
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: false),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
    ])
    ->filters([
        //
    ])
    ->recordActions([
        ViewAction::make()
          ->visible(fn () => auth()->user()->hasRole('spv')),
        EditAction::make()
          ->visible(fn () => auth()->user()->hasRole(['hr', 'super_admin'])),
    ])
    ->toolbarActions([
      BulkActionGroup::make([
        DeleteBulkAction::make(),
      ]),
    ]);
  }
}
