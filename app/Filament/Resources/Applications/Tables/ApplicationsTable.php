<?php

namespace App\Filament\Resources\Applications\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApplicationsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->poll('5s')
      ->columns([
        TextColumn::make('no')
          ->label('No.')
          ->rowIndex(),
        TextColumn::make('jobVacancy.title')
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
          ->color(fn (string $state): string => match ($state) {
            'accepted' => 'success',
            'rejected' => 'danger',
            'reviewed' => 'info',
            'pending' => 'gray',
            default => 'gray',
          }),
        TextColumn::make('ai_score')
          ->numeric()
          ->sortable()
          ->badge()
          ->formatStateUsing(fn ($state) => is_numeric($state) ? $state . '%' : $state)
          ->color(fn ($state) => match (true) {
            $state >= 80 => 'success',
            $state >= 50 => 'warning',
            default => 'danger',
          }),
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
        EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
