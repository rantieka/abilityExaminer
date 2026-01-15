<?php

namespace App\Filament\Resources\JobVacancies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Set as FormSet;
use Illuminate\Support\Str;
use Filament\Forms\Components\RichEditor;
use Illuminate\Support\Facades\Auth;

class JobVacancyForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
          TextInput::make('title')
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function ($set, $state) {
              if ($state) {
                $set('slug', Str::slug($state));
              }
            }),
          TextInput::make('slug')
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true)
            ->disabled()
            ->dehydrated(), 
          // TextInput::make('created_by')
          //   ->required()
          //   ->numeric(),
          Textarea::make('description')
            ->required()
            ->columnSpanFull(),
          RichEditor::make('qualifications')
            ->columnSpanFull(),
          TextInput::make('status')
            ->default('pending')
            ->disabled()
            ->dehydrated()
            ->visible(fn() => Auth::user()->hasRole(['hr', 'super_admin'])),
          // is_published is auto-set by Approve action, no need for manual toggle
      ]);
  }
}
