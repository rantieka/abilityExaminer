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
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;

use Filament\Forms\Components\DatePicker;

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
        
        Section::make('Job Details')
          ->schema([
            TextInput::make('required_count')
              ->label('Jumlah Kebutuhan')
              ->numeric()
              ->default(1)
              ->minValue(1)
              ->required(),
            DatePicker::make('published_until')
              ->label('Batas Publikasi')
              ->native(false),
            Toggle::make('is_fulltime')
              ->label('Full Time')
              ->default(true),
            Toggle::make('is_wfo')
              ->label('Work From Office (WFO)')
              ->default(true),
          ])->columns(2),

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
          ->visible(fn() => Auth::user()->hasRole(['hr', 'super_admin', 'spv'])),
        // is_published is auto-set by Approve action, no need for manual toggle
        
        // Rejection Information
        Section::make('Rejection Information')
          ->schema([
            Placeholder::make('rejection_reason')
            ->label('Rejection Reason')
            ->content(fn ($record) => $record?->rejection_reason ?? '-'),
          
            Placeholder::make('rejected_by')
            ->label('Rejected By')
            ->content(fn ($record) => $record?->rejectedBy?->name ?? '-'),
          
            Placeholder::make('rejected_at')
            ->label('Rejected At')
            ->content(fn ($record) => $record?->rejected_at?->format('d M Y, H:i') ?? '-'),
          ])
          ->visible(fn ($record) => $record?->status === 'rejected' && Auth::user()->hasRole(['hr', 'super_admin', 'spv']))
          ->collapsible()
          ->collapsed(false),
      ]);
  }
}
