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
use Filament\Forms\Components\Select;

class JobVacancyForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        // SECTION 1: POSISI
        Section::make('Position')
          ->columnSpanFull()
          ->columns(2)
          ->schema([
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
          ]),

        // SECTION 2: DETAIL PEKERJAAN
        Section::make('Job Details')
          ->columnSpanFull()
          ->columns(2)
          ->schema([
            TextInput::make('department')
              ->label('Department')
              ->default('IT')
              ->disabled()
              ->dehydrated()
              ->required(),

            Select::make('employment_type')
              ->label('Employment Type')
              ->options([
                  'Full Time' => 'Full Time',
                  'Part Time' => 'Part Time',
                  'Contract' => 'Contract',
                  'Internship' => 'Internship',
              ])
              ->required(),
            Select::make('work_arrangement')
              ->label('Work Arrangement')
              ->options([
                  'WFO' => 'WFO',
                  'WFH' => 'WFH',
                  'Hybrid' => 'Hybrid',
              ])
              ->required(),
            TextInput::make('required_count')
              ->label('Vacancies Needed')
              ->numeric()
              ->default(1)
              ->minValue(1)
              ->required(),
            DatePicker::make('published_until')
                  ->label('Publish Until')
                  ->native(false)
                  ->visible(fn () => Auth::user()->hasRole(['hr', 'super_admin']))
                  ->required(fn ($get) => $get('status') === 'approved'),
            RichEditor::make('location')
              ->label('Office Location')
              ->default(fn () => \App\Models\Setting::get('office_address'))
              ->required()
              ->columnSpanFull()
              ->visible(fn () => Auth::user()->hasRole(['hr', 'super_admin'])),
          ]),

        // SECTION 3: DESKRIPSI & KUALIFIKASI
        Section::make('Description & Qualifications')
            ->columnSpanFull()
            ->schema([
                Textarea::make('description')
                  ->label('Description')
                  ->required()
                  ->rows(10)
                  ->columnSpanFull(),
                RichEditor::make('qualifications')
                  ->columnSpanFull(),
            ]),
        Select::make('status')
          ->options([
              'draft' => 'Draft',
              'pending' => 'Pending Approval',
              'approved' => 'Approved (Publish)',
              'rejected' => 'Rejected',
          ])
          ->default(fn () => Auth::user()->hasRole(['hr', 'super_admin']) ? 'approved' : 'pending')
          ->disabled(fn () => !Auth::user()->hasRole(['hr', 'super_admin'])) // SPV cannot change status directly
          ->dehydrated()
          ->required()
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
