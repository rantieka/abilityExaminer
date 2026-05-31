<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label('Nama')
          ->searchable(),
        TextColumn::make('email')
          ->label('Alamat Email')
          ->searchable(),
        TextColumn::make('email_verified_at')
          ->label('Verifikasi Email')
          ->dateTime()
          ->sortable(),
        TextColumn::make('created_at')
          ->label('Dibuat Pada')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->label('Diperbarui Pada')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->actions([
          EditAction::make(),
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
