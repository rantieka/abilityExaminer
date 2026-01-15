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
          ->required(),
        TextInput::make('email')
          ->label('Email address')
          ->email()
          ->required(),
        DateTimePicker::make('email_verified_at'),
        TextInput::make('password')
          ->password()
          // Only save (dehydrate) if this field is filled. If empty, old password remains safe.
          ->dehydrated(fn (?string $state) => filled($state))
          // Required ONLY when creating a new user.
          ->required(fn (string $operation): bool => $operation === 'create'),
        
        // Input to select Role
        CheckboxList::make('roles')
          ->relationship('roles', 'name')
          ->columns(2)
          ->helperText('Select only one role!'),
      ]);
  }
}
