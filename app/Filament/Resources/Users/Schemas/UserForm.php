<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Schema;

class UserForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->label('Nama')
          ->required(),
        TextInput::make('email')
          ->label('Alamat Email')
          ->email()
          ->required(),
        DateTimePicker::make('email_verified_at')
          ->label('Email Terverifikasi Pada'),
        TextInput::make('password')
          ->label('Password')
          ->password()
          // Only save (dehydrate) if this field is filled. If empty, old password remains safe.
          ->dehydrated(fn (?string $state) => filled($state))
          // Required ONLY when creating a new user.
          ->required(fn (string $operation): bool => $operation === 'create'),
        
        // Input to select Role
        CheckboxList::make('roles')
          ->label('Role')
          ->relationship('roles', 'name')
          ->columns(2),
      ]);
  }
}
