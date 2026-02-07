<?php

namespace App\Filament\Resources\Applications;

use App\Filament\Resources\Applications\Pages\CreateApplication;
use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Schemas\ApplicationForm;
use App\Filament\Resources\Applications\Tables\ApplicationsTable;
use App\Models\Application;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;


class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
      return $schema
        ->components([
          // Basic Information (Full Width)
          TextInput::make('job_position')
            ->label('Job Position')
            ->formatStateUsing(fn ($record) => $record->jobVacancy?->title)
            ->disabled()
            ->dehydrated(false),
          TextInput::make('created_at')
            ->label('Applied Date')
            ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d M Y') : '-')
            ->disabled()
            ->dehydrated(false),
          
          TextInput::make('status')
            ->label('Status')
            ->formatStateUsing(fn ($state, $record) => match (true) {
                $state === 'pending' && $record->ai_score === null => 'Scanning CV...',
                $state === 'pending' && $record->ai_score !== null => 'Pending Review',
                $state === 'reviewed' => 'Reviewed',
                $state === 'accepted' => 'Accepted',
                $state === 'rejected' => 'Rejected',
                default => ucfirst($state),
            })
            ->disabled()
            ->dehydrated(false),
          TextInput::make('full_name')
            ->label('Full Name')
            ->disabled()
            ->dehydrated(),
          TextInput::make('email')
            ->label('Email')
            ->disabled()
            ->dehydrated(),
          TextInput::make('phone')
            ->label('Phone')
            ->disabled()
            ->dehydrated(),
          // AI Score is hidden
          // TextInput::make('ai_score')
          //   ->label('AI Match Score')
          //   ->disabled()
          //   ->numeric()
          //   ->dehydrated()
          //   ->suffix('%'),
          
          Textarea::make('rejection_reason')
            ->label('Reason for Rejection')
            ->visible(fn ($record) => $record?->status === 'rejected')
            ->disabled()
            ->dehydrated(false)
            ->columnSpanFull()
            ->rows(3),
          
          // 2-Column Grid: CV Preview & AI Analysis
          \Filament\Schemas\Components\Grid::make(2)
            ->columnSpanFull()
            ->schema([
              // Left Column: CV Document
              \Filament\Schemas\Components\Section::make('CV Document')
                ->schema([
                  \Filament\Forms\Components\Placeholder::make('cv_preview')
                    ->hiddenLabel()
                    ->content(fn ($record) => $record?->cv_path 
                      ? new \Illuminate\Support\HtmlString('
                          <div class="space-y-2">
                            <div class="flex items-center justify-end mb-2">
                              <a href="' . asset('storage/' . $record->cv_path) . '" 
                                 target="_blank" 
                                 class="text-xs text-blue-600 hover:text-blue-700 force-no-underline">
                                 Open in New Tab
                              </a>
                            </div>
                            <iframe 
                              src="' . asset('storage/' . $record->cv_path) . '" 
                              class="w-full border border-gray-300 rounded-lg"
                              style="height: 800px; width: 100%"
                              frameborder="0">
                              <p>Your browser does not support PDFs. 
                                <a href="' . asset('storage/' . $record->cv_path) . '">Download the PDF</a>
                              </p>
                            </iframe>
                          </div>
                      ')
                      : new \Illuminate\Support\HtmlString('<p class="text-gray-500 italic">No CV uploaded</p>')
                    ),
                ])
                ->collapsible()
                ->collapsed(false),
              
              // Right Column: AI Analysis
              \Filament\Schemas\Components\Section::make('AI Analysis')
                ->heading(fn ($record) => $record ? new \Illuminate\Support\HtmlString('
                    <div class="ai-analysis-header">
                        <span>AI Analysis</span>
                        <div class="ai-score-wrapper ' . match(true) {
                            $record->ai_score >= 80 => 'ai-score-badge-green',
                            $record->ai_score >= 50 => 'ai-score-badge-yellow',
                            default => 'ai-score-badge-red',
                        } . '">
                            <span class="ai-score-text">Match Score: ' . ($record->ai_score ?? 0) . '%</span>
                        </div>
                    </div>
                ') : 'AI Analysis')
                ->schema([
                  \Filament\Forms\Components\Placeholder::make('ai_analysis')
                    ->hiddenLabel()
                    ->content(fn ($record) => $record?->ai_analysis 
                      ? new \Illuminate\Support\HtmlString('
                          <div class="ai-analysis-container">
                            <!-- Executive Summary Card -->
                            <div class="exec-summary-card">
                              <div class="card-header">
                                <div class="icon-wrapper icon-blue">
                                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="card-title text-blue-900">Executive Summary</h4>
                                </div>
                              </div>
                              <p class="card-content">' . ($record->ai_analysis['summary'] ?? '-') . '</p>
                            </div>
                            
                            <div class="analysis-grid">
                                <!-- Profile Strengths Card -->
                                <div class="strengths-card">
                                  <div class="card-header">
                                    <div class="icon-wrapper icon-green">
                                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <h4 class="card-title text-green-900">Key Strengths</h4>
                                  </div>
                                  <ul class="list-container">
                                    ' . (isset($record->ai_analysis['pros']) && is_array($record->ai_analysis['pros']) 
                                      ? implode('', array_map(fn($item) => '
                                        <li class="list-item">
                                            <span class="bullet-point bg-green-400"></span>
                                            <span class="list-text">' . htmlspecialchars($item) . '</span>
                                        </li>', $record->ai_analysis['pros']))
                                      : '<li class="no-data">No data</li>') . '
                                  </ul>
                                </div>
                                
                                <!-- Profile Weaknesses Card -->
                                <div class="weaknesses-card">
                                  <div class="card-header">
                                    <div class="icon-wrapper icon-red">
                                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    </div>
                                    <h4 class="card-title text-red-900">Profile Weaknesses</h4>
                                  </div>
                                  <ul class="list-container">
                                    ' . (isset($record->ai_analysis['cons']) && is_array($record->ai_analysis['cons']) 
                                      ? implode('', array_map(fn($item) => '
                                        <li class="list-item">
                                            <span class="bullet-point bg-red-400"></span>
                                            <span class="list-text">' . htmlspecialchars($item) . '</span>
                                        </li>', $record->ai_analysis['cons']))
                                      : '<li class="no-data">No data</li>') . '
                                  </ul>
                                </div>
                            </div>
                          </div>')
                      : new \Illuminate\Support\HtmlString('
                          <div class="flex items-center justify-center h-32 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <p class="text-gray-500 italic">No AI analysis available yet</p>
                          </div>
                      ')
                    ),
                ])
                ->collapsible()
                ->collapsed(false),
            ]),
        ]);
    }

  public static function table(Table $table): Table
  {
    return ApplicationsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      //
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListApplications::route('/'),
      'create' => CreateApplication::route('/create'),
      'view' => Pages\ViewApplication::route('/{record}'),
      'edit' => EditApplication::route('/{record}/edit'),
    ];
  }
}
