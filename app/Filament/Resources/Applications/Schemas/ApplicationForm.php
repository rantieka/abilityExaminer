<?php

namespace App\Filament\Resources\Applications\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ApplicationForm
{
  public static function configure(Schema $schema): Schema
  {
      return $schema
        ->components([
          // Basic Information (Full Width)
          Select::make('job_vacancy_id')
            ->relationship('jobVacancy', 'title')
            ->required()
            ->disabled()
            ->dehydrated(),
              
          Select::make('user_id')
            ->relationship('user', 'name')
            ->disabled()
            ->dehydrated(),
              
          TextInput::make('full_name')
            ->required()
            ->disabled()
            ->dehydrated(),
              
          TextInput::make('email')
            ->label('Email Address')
            ->email()
            ->required()
            ->disabled()
            ->dehydrated(),
              
          TextInput::make('phone')
            ->tel()
            ->disabled()
            ->dehydrated(),
              
          TextInput::make('ai_score')
            ->label('AI Match Score')
            ->numeric()
            ->disabled()
            ->dehydrated()
            ->suffix('%')
            ->helperText('AI-generated compatibility score'),
          
          TextInput::make('status')
            ->required()
            ->default('pending')
            ->disabled()
              ->dehydrated(),
          
          // 2-Column Grid: CV Preview & AI Analysis
          \Filament\Schemas\Components\Grid::make(2)
            ->schema([
              // Left Column: CV Preview
              \Filament\Schemas\Components\Section::make('CV Document')
                ->schema([
                  \Filament\Forms\Components\Placeholder::make('cv_preview')
                    ->label('')
                    ->content(fn ($record) => $record?->cv_path 
                      ? new \Illuminate\Support\HtmlString('
                        <div class="space-y-2">
                          <p class="text-sm font-medium">CV File:</p>
                          <a href="' . asset('storage/' . $record->cv_path) . '" 
                            target="_blank" 
                            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            📄 View CV (PDF)
                          </a>
                          <p class="text-xs text-gray-500 mt-2">Click to open CV in new tab</p>
                        </div>
                      ')
                      : 'No CV uploaded'
                    ),
                ])
                ->collapsible()
                ->collapsed(false),
              
              // Right Column: AI Analysis
              \Filament\Schemas\Components\Section::make('AI Analysis')
                ->schema([
                  \Filament\Forms\Components\Placeholder::make('ai_analysis_display')
                    ->label('')
                    ->content(fn ($record) => $record?->ai_analysis 
                      ? new \Illuminate\Support\HtmlString('
                        <div class="prose prose-sm max-w-none">
                          <pre class="whitespace-pre-wrap text-sm bg-gray-50 p-4 rounded-lg">' 
                          . htmlspecialchars($record->ai_analysis) . 
                          '</pre>
                        </div>
                      ')
                      : new \Illuminate\Support\HtmlString('
                        <p class="text-gray-500 italic">No AI analysis available yet</p>
                      ')
                    ),
                ])
                ->collapsible()
                ->collapsed(false),
            ]),
        ]);
  }
}
