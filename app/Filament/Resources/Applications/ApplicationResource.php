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
          TextInput::make('ai_score')
            ->label('AI Match Score')
            ->disabled()
            ->numeric()
            ->dehydrated()
            ->suffix('%'),
          
          // 2-Column Grid: CV Preview & AI Analysis
          \Filament\Schemas\Components\Grid::make(2)
            ->columnSpanFull()
            ->schema([
              // Left Column: CV Document
              \Filament\Schemas\Components\Section::make('CV Document')
                ->schema([
                  \Filament\Forms\Components\Placeholder::make('cv_preview')
                    ->label('')
                    ->content(fn ($record) => $record?->cv_path 
                      ? new \Illuminate\Support\HtmlString('
                          <div class="space-y-2">
                            <div class="flex items-center justify-between mb-2">
                              <p class="text-sm font-semibold text-gray-700">CV Preview</p>
                              <a href="' . asset('storage/' . $record->cv_path) . '" 
                                 target="_blank" 
                                 class="text-xs text-blue-600 hover:text-blue-700 underline">
                                Open in new tab
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
                ->schema([
                  \Filament\Forms\Components\Placeholder::make('ai_analysis')
                    ->label(false)
                    ->content(fn ($record) => $record?->ai_analysis 
                      ? new \Illuminate\Support\HtmlString('
                          <div class="ai-analysis-container">
                            <!-- Summary Section -->
                            <div class="ai-section ai-section-summary">
                              <h4 class="ai-section-title ai-section-title-summary">Analysis Summary</h4>
                              <p class="ai-section-text">' . ($record->ai_analysis['summary'] ?? '-') . '</p>
                            </div>
                            
                            <!-- Profile Strengths Section -->
                            <div class="ai-section ai-section-strengths">
                              <h4 class="ai-section-title ai-section-title-strengths">Profile Strengths</h4>
                              <ul class="ai-list">
                                ' . (isset($record->ai_analysis['pros']) && is_array($record->ai_analysis['pros']) 
                                  ? implode('', array_map(fn($item) => '<li class="ai-list-item"><span class="ai-list-bullet">•</span><span>' . htmlspecialchars($item) . '</span></li>', $record->ai_analysis['pros']))
                                  : '<li class="ai-no-data">No data</li>') . '
                              </ul>
                            </div>
                            
                            <!-- Profile Weaknesses Section -->
                            <div class="ai-section ai-section-weaknesses">
                              <h4 class="ai-section-title ai-section-title-weaknesses">Profile Weaknesses</h4>
                              <ul class="ai-list">
                                ' . (isset($record->ai_analysis['cons']) && is_array($record->ai_analysis['cons']) 
                                  ? implode('', array_map(fn($item) => '<li class="ai-list-item"><span class="ai-list-bullet">•</span><span>' . htmlspecialchars($item) . '</span></li>', $record->ai_analysis['cons']))
                                  : '<li class="ai-no-data">No data</li>') . '
                              </ul>
                            </div>
                          </div>
                      ')
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
