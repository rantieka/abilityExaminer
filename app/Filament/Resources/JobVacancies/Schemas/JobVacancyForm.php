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
use Filament\Forms\Components\TagsInput;

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
          ->disabled(fn ($record) => $record?->status === 'approved')
          ->schema([

            \Filament\Forms\Components\Select::make('title')
              ->label('Position Title')
              ->required()
              ->options([
                  'Frontend Developer' => 'Frontend Developer',
                  'Backend Developer' => 'Backend Developer',
              ])
              ->live()
              ->afterStateUpdated(function ($set, $state) {
                if ($state) {
                  $set('slug', \Illuminate\Support\Str::slug($state) . '-' . uniqid());
                }
              }),
            \Filament\Forms\Components\TextInput::make('slug')
              ->required()
              ->maxLength(255)
              ->unique(ignoreRecord: true),
            \Filament\Forms\Components\Select::make('experience_level')
              ->label('Experience Level')
              ->required()
              ->options([
                  'junior' => 'Junior / Fresh Graduate',
                  'middle' => 'Mid Level ',
                  'senior' => 'Senior',
              ])
              ->default('junior')
              ->columnSpanFull(),
          ]),

        // SECTION 2: DETAIL PEKERJAAN
        Section::make('Job Details')
          ->columnSpanFull()
          ->columns(2)
          ->disabled(fn ($record) => $record?->status === 'approved')
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
                  'Contract' => 'Contract',
              ])
              ->required(),
            TextInput::make('work_arrangement')
              ->label('Work Arrangement')
              ->default('WFO')
              ->disabled()
              ->dehydrated()
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
                  ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord && Auth::user()->hasRole(['hr', 'super_admin']))
                  ->required(fn ($get) => $get('status') === 'approved'),
          ]),

        // SECTION 3: DESKRIPSI & KUALIFIKASI
        Section::make('Description & General Qualifications')
            ->columnSpanFull()
            ->disabled(fn ($record) => $record?->status === 'approved')
            ->schema([
                Textarea::make('description')
                  ->label('Description')
                  ->required()
                  ->rows(10)
                  ->columnSpanFull(),
                RichEditor::make('qualifications')
                  ->label('General Qualifications')
                  ->columnSpanFull(),
            ]),

        // SECTION: SKILLS
        Section::make('Technical Skills')
          ->columnSpanFull()
          ->columns(3)
          ->disabled(fn ($record) => $record?->status === 'approved')
          ->schema([
            Select::make('required_skills')
              ->label('Required Skills')
              ->multiple()
              ->required()
              ->live()
              ->options(fn ($get) => match ($get('title')) {
                  'Backend Developer' => [
                      'PHP' => 'PHP',
                      'CodeIgniter' => 'CodeIgniter',
                      'Python' => 'Python',
                      'Django' => 'Django',
                  ],
                  'Frontend Developer' => [
                      'JavaScript' => 'JavaScript',
                      'HTML' => 'HTML',
                      'CSS' => 'CSS',
                  ],
                  default => [],
              })
              ->placeholder('Select required skills...'),
            Select::make('preferred_skills')
              ->label('Preferred Skills')
              ->multiple()
              ->live()
              ->options(fn ($get) => match ($get('title')) {
                  'Backend Developer' => [
                      'Ruby' => 'Ruby',
                      'Ruby on Rails' => 'Ruby on Rails',
                  ],
                  'Frontend Developer' => [
                      'React' => 'React',
                      'Vue' => 'Vue',
                      'Angular' => 'Angular',
                  ],
                  default => [],
              })
              ->placeholder('Select preferred skills...'),
            Select::make('bonus_skills')
              ->label('Bonus Skills')
              ->multiple()
              ->live()
              ->options(fn ($get) => match ($get('title')) {
                  'Backend Developer' => [
                      'Git' => 'Git',
                      'MySQL' => 'MySQL',
                      'REST API' => 'REST API',
                      'Postman' => 'Postman',
                  ],
                  'Frontend Developer' => [
                      'Git' => 'Git',
                      'Responsive Design' => 'Responsive Design',
                      'API Integration' => 'API Integration',
                      'Bootstrap' => 'Bootstrap',
                      'Jquery' => 'Jquery',
                  ],
                  default => [],
              })
              ->placeholder('Select bonus skills...'),
          ]),
        Select::make('status')
          ->options([
              'draft' => 'Draft',
              'pending' => 'Pending Approval',
              'approved' => 'Approved (Publish)',
              'rejected' => 'Rejected',
          ])
          ->default(fn () => Auth::user()->hasRole(['hr', 'super_admin']) ? 'approved' : 'pending')
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
