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

    protected static string | \UnitEnum | null $navigationGroup = 'Recruitment';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
      return $schema
        ->components([
          // Basic Information (Full Width)
          // Using Filament\Schemas\Components\Tabs because Filament\Forms\Components\Tabs is not found
          // (Assuming this is a custom or specific version of Filament where Schemas namespace is used for Tabs)
          \Filament\Schemas\Components\Tabs::make('Application Details')
            ->columnSpanFull()
            ->tabs([
                // Tab 1: Applicant Profile
                \Filament\Schemas\Components\Tabs\Tab::make('Applicant Profile')
                    ->icon('heroicon-o-user')
                    ->schema([
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
                        
                        Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->visible(fn ($record) => $record?->status === 'rejected')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->rows(3),
                    ]),

                // Tab 2: CV Screening
                \Filament\Schemas\Components\Tabs\Tab::make('CV Screening')
                    ->icon('heroicon-o-document-text')
                    ->schema([
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
                                                ' . (str_contains(strtolower($record->ai_analysis['summary'] ?? ''), 'failed') || str_contains(strtolower($record->ai_analysis['summary'] ?? ''), 'gagal') || (empty($record->ai_analysis['pros']) && empty($record->ai_analysis['cons'])) ? '
                                                <!-- Error State -->
                                                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg shadow-sm mb-4">
                                                    <div class="flex items-center">
                                                        <div class="ml-3">
                                                            <h3 class="text-sm font-bold text-red-800">AI Screening Failed</h3>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-center h-24 bg-gray-50 rounded-lg border border-gray-200">
                                                    <p class="text-xs text-gray-500 italic">No analysis data available due to system error. Please review CV manually.</p>
                                                </div>
                                                ' : '
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
                                                        ' . (isset($record->ai_analysis['cons']) && count($record->ai_analysis['cons']) > 0 
                                                        ? implode('', array_map(fn($item) => '
                                                            <li class="list-item">
                                                                <span class="bullet-point bg-red-400"></span>
                                                                <span class="list-text">' . htmlspecialchars($item) . '</span>
                                                            </li>', $record->ai_analysis['cons']))
                                                        : '
                                                            <li class="list-item">
                                                                <span class="bullet-point bg-gray-400"></span>
                                                                <span class="list-text italic text-gray-500">No prominent weaknesses identified.</span>
                                                            </li>') . '
                                                    </ul>
                                                    </div>
                                                </div>') . '
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
                    ]),

                // Tab 3: Generate Token
                \Filament\Schemas\Components\Tabs\Tab::make('Generate Token')
                  ->icon('heroicon-o-key')
                  ->visible(fn () => auth()->user()->hasRole(['hr', 'super_admin']))
                  ->schema([
                    \Filament\Schemas\Components\Section::make('Access Configuration')
                      ->description('Manage test access token and expiration for this applicant.')
                      ->schema([
                        \Filament\Schemas\Components\Grid::make(1)
                          ->schema([
                            \Filament\Schemas\Components\Grid::make(2)
                              ->schema([
                                \Filament\Forms\Components\TextInput::make('test_token')
                                  ->label('Token Tes')
                                  ->readOnly()
                                  ->placeholder('Klik generate untuk membuat token')
                                  ->suffixActions([
                                    \Filament\Actions\Action::make('generate')
                                      ->icon('heroicon-m-arrow-path')
                                      ->tooltip('Buat Token Baru')
                                      ->action(function ($record, $set) {
                                        $token = \Illuminate\Support\Str::random(64);
                                        $expires = now()->addDays(7);
                                        $link = route('test.access', ['token' => $token]);
                                                              
                                        // Update Form State
                                        $set('test_token', $token);
                                        $set('token_expires_at', $expires);
                                        $set('test_link_preview', $link);

                                        // Save to Database immediately if record exists
                                        if ($record) {
                                          $record->update([
                                            'test_token' => $token,
                                            'token_expires_at' => $expires
                                          ]);
                                                                  
                                          \Filament\Notifications\Notification::make()
                                            ->title('Token Saved Successfully')
                                            ->success()
                                            ->send();
                                        }
                                      }),
                                    \Filament\Actions\Action::make('copy')
                                      ->icon('heroicon-m-clipboard')
                                      ->tooltip('Salin ke Clipboard')
                                      ->action(function ($livewire, $state) {
                                        if ($state) {
                                          $livewire->js("window.navigator.clipboard.writeText('{$state}'); new FilamentNotification().title('Berhasil disalin').success().send()");
                                        }
                                      }),
                                  ]),
                                \Filament\Forms\Components\DateTimePicker::make('token_expires_at')
                                  ->label('Token Expiration')
                                  ->native(false)
                                  ->default(fn() => now()->addDays(7))
                                  ->requiredWith('test_token')
                                  ->minDate(now()),
                              ]),
                        
                        \Filament\Forms\Components\TextInput::make('test_link_preview')
                          ->label('Test Link Preview')
                          ->readOnly()
                          ->dehydrated(false)
                          ->formatStateUsing(function ($get) {
                            $token = $get('test_token');
                            if (!$token) return 'No token generated yet.';
                            return route('test.access', ['token' => $token]);
                          })
                          ->suffixActions([
                            \Filament\Actions\Action::make('copyLink')
                              ->icon('heroicon-m-clipboard')
                              ->tooltip('Copy Link')
                              ->action(function ($livewire, $state) {
                                if ($state) {
                                  $livewire->js("window.navigator.clipboard.writeText('{$state}'); new FilamentNotification().title('Link Copied').success().send()");
                                }
                              })
                          ]),
                        
                        \Filament\Forms\Components\Placeholder::make('missing_questions_warning')
                          ->hiddenLabel()
                          ->content(new \Illuminate\Support\HtmlString('<span class="text-warning-600 font-medium">⚠️ Warning: No exam questions available for this job vacancy. Test token cannot be generated.</span>'))
                          ->visible(function ($record) {
                              $jobVacancy = $record?->jobVacancy;
                              return $jobVacancy && 
                                           !in_array($record?->status, ['accepted', 'rejected']) &&
                                           !$jobVacancy->questions()->where('is_active', true)->exists();
                          })
                          ->hintActions([
                              \Filament\Actions\Action::make('remind_supervisor_fix')
                                ->label('Remind Supervisor')
                                ->icon('heroicon-o-bell-alert')
                                ->color('warning')
                                ->requiresConfirmation() // button() dihapus karena tidak disupport di hint actions
                                ->modalHeading('Remind Supervisor')
                                ->modalDescription(fn ($record) => "Send a notification to the supervisor ({$record->jobVacancy->createdBy->name}) to create test questions for this Job Vacancy?")
                                ->action(function ($record) {
                                  $recipient = $record->jobVacancy->createdBy;
                                        
                                  if ($recipient) {
                                    \Filament\Notifications\Notification::make()
                                      ->warning()
                                      ->title('Action Required: Missing Test Questions')
                                      ->body("HR is trying to generate a test token for '{$record->jobVacancy->title}' but no test questions are available. Please create them immediately.")
                                      ->actions([
                                        \Filament\Notifications\Actions\Action::make('create_questions')
                                          ->label('Create Questions')
                                          ->button()
                                          ->url(\App\Filament\Resources\Questions\QuestionResource::getUrl('index', ['job_id' => $record->jobVacancy->id])),
                                      ])
                                      ->sendToDatabase($recipient);

                                    \Filament\Notifications\Notification::make()
                                      ->success()
                                      ->title('Reminder Sent')
                                      ->body("Notification sent to {$recipient->name}.")
                                      ->send();
                                  }
                                }),
                          ]),
                  ]),
                ]),
              ]),
                // Tab 4: Assessment Results
              \Filament\Schemas\Components\Tabs\Tab::make('Assessment Results')
                ->icon('heroicon-o-academic-cap')
                ->schema([
                  \Filament\Schemas\Components\Section::make('Test Results')
                    ->schema([
                      \Filament\Forms\Components\ViewField::make('test_details')
                        ->view('filament.forms.components.test-score-details'),
                    ]),
                ]),

              \Filament\Schemas\Components\Tabs\Tab::make('Selection & Approvals')
                ->icon('heroicon-o-check-badge')
                ->schema([
                  \Filament\Schemas\Components\Section::make('C4.5 Algorithm Recommendation')
                    ->description('Machine Learning Predictive Classification')
                    ->schema([
                      \Filament\Forms\Components\ViewField::make('c45_selection_summary')
                        ->view('filament.forms.components.c45-selection-summary'),
                    ])
                    ->columnSpanFull(),

                  \Filament\Schemas\Components\Section::make('HRD Selection Decision')
                    ->description('Step 1: HRD Recruiter Recommendation')
                    ->visible(fn ($record) => $record?->test_completed_at !== null)
                    ->schema([
                      \Filament\Forms\Components\Placeholder::make('hrd_decision_summary')
                        ->label('HRD Recommendation')
                        ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                          $record->hrd_decision === 'recommended' 
                            ? '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">🟢 Recommended for Hire</span>'
                            : '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-green-200">🔴 Rejected</span>'
                        ))
                        ->visible(fn ($record) => $record?->hrd_decision !== 'pending'),

                      \Filament\Forms\Components\Placeholder::make('hrd_notes_summary')
                        ->label('HRD Selection Notes')
                        ->content(fn ($record) => $record->hrd_notes ?: '-')
                        ->visible(fn ($record) => $record?->hrd_decision !== 'pending'),

                      \Filament\Schemas\Components\Actions::make([
                        \Filament\Actions\Action::make('recommend_hrd')
                          ->label('Recommend for Hire')
                          ->color('success')
                          ->icon('heroicon-m-check')
                          ->requiresConfirmation()
                          ->modalHeading('Recommend Candidate')
                          ->modalDescription('Are you sure you want to recommend this candidate to the Supervisor?')
                          ->form([
                            \Filament\Forms\Components\Textarea::make('notes')
                              ->label('HRD Selection Notes')
                              ->placeholder('Provide key reasoning for this recommendation...')
                              ->rows(3)
                              ->required(),
                          ])
                          ->action(function ($record, array $data) {
                            $record->update([
                              'hrd_decision' => 'recommended',
                              'hrd_notes' => $data['notes'],
                              'hrd_decided_at' => now(),
                            ]);
                            \Filament\Notifications\Notification::make()
                              ->success()
                              ->title('Candidate Recommended')
                              ->body('Candidate has been successfully recommended to the Supervisor.')
                              ->send();
                          }),

                        \Filament\Actions\Action::make('reject_hrd')
                          ->label('Reject Candidate')
                          ->color('danger')
                          ->icon('heroicon-m-x-mark')
                          ->requiresConfirmation()
                          ->modalHeading('Reject Candidate')
                          ->modalDescription('Are you sure you want to reject this candidate? This will end their recruitment process.')
                          ->form([
                            \Filament\Forms\Components\Textarea::make('notes')
                              ->label('Rejection Reason / Notes')
                              ->placeholder('Provide key reasoning for this rejection...')
                              ->rows(3)
                              ->required(),
                          ])
                          ->action(function ($record, array $data) {
                            $record->update([
                              'hrd_decision' => 'rejected',
                              'hrd_notes' => $data['notes'],
                              'hrd_decided_at' => now(),
                              'status' => 'rejected',
                            ]);
                            \Filament\Notifications\Notification::make()
                              ->success()
                              ->title('Candidate Rejected')
                              ->body('Candidate has been rejected by HRD.')
                              ->send();
                          }),
                      ])
                      ->visible(fn ($record) => $record?->hrd_decision === 'pending' && auth()->user()->hasRole(['hr', 'super_admin']))
                      ->columnSpanFull()
                    ])
                    ->columns(2),

                  \Filament\Schemas\Components\Section::make('Supervisor Final Approval')
                    ->description('Step 2: Technical / Management Approval')
                    ->visible(fn ($record) => $record?->hrd_decision === 'recommended')
                    ->schema([
                      \Filament\Forms\Components\Placeholder::make('supervisor_decision_summary')
                        ->label('Supervisor Decision')
                        ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                          $record->supervisor_decision === 'approved' 
                            ? '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">🔵 Approved for Hire</span>'
                            : '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">🔴 Disapproved / Rejected</span>'
                        ))
                        ->visible(fn ($record) => $record?->supervisor_decision !== 'pending'),

                      \Filament\Forms\Components\Placeholder::make('supervisor_notes_summary')
                        ->label('Supervisor Selection Notes')
                        ->content(fn ($record) => $record->supervisor_notes ?: '-')
                        ->visible(fn ($record) => $record?->supervisor_decision !== 'pending'),

                      \Filament\Schemas\Components\Actions::make([
                        \Filament\Actions\Action::make('approve_spv')
                          ->label('Approve for Hire')
                          ->color('primary')
                          ->icon('heroicon-m-check-badge')
                          ->requiresConfirmation()
                          ->modalHeading('Approve Candidate for Hire')
                          ->modalDescription('Are you sure you want to approve this candidate? This will authorize HRD to issue an official offering letter.')
                          ->form([
                            \Filament\Forms\Components\Textarea::make('notes')
                              ->label('Supervisor Selection Notes')
                              ->placeholder('Provide technical feedback or reasoning for hiring approval...')
                              ->rows(3)
                              ->required(),
                          ])
                          ->action(function ($record, array $data) {
                            $record->update([
                              'supervisor_decision' => 'approved',
                              'supervisor_notes' => $data['notes'],
                              'supervisor_decided_at' => now(),
                            ]);
                            \Filament\Notifications\Notification::make()
                              ->success()
                              ->title('Hiring Approved')
                              ->body('Final selection approval has been processed successfully.')
                              ->send();
                          }),

                        \Filament\Actions\Action::make('reject_spv')
                          ->label('Disapprove / Reject')
                          ->color('danger')
                          ->icon('heroicon-m-x-circle')
                          ->requiresConfirmation()
                          ->modalHeading('Disapprove Candidate')
                          ->modalDescription('Are you sure you want to disapprove and reject this candidate?')
                          ->form([
                            \Filament\Forms\Components\Textarea::make('notes')
                              ->label('Disapproval Reason / Notes')
                              ->placeholder('Provide technical reasoning for disapproval...')
                              ->rows(3)
                              ->required(),
                          ])
                          ->action(function ($record, array $data) {
                            $record->update([
                              'supervisor_decision' => 'rejected',
                              'supervisor_notes' => $data['notes'],
                              'supervisor_decided_at' => now(),
                            ]);
                            \Filament\Notifications\Notification::make()
                              ->success()
                              ->title('Hiring Disapproved')
                              ->body('Candidate has been disapproved by Supervisor.')
                              ->send();
                          }),
                      ])
                      ->visible(fn ($record) => $record?->supervisor_decision === 'pending' && auth()->user()->hasRole(['spv', 'super_admin']))
                      ->columnSpanFull()
                    ])
                    ->columns(2),

                  \Filament\Schemas\Components\Section::make('Selection Announcement')
                    ->description('Step 3: Official Announcement & Applicant Email')
                    ->visible(fn ($record) => in_array($record?->supervisor_decision, ['approved', 'rejected']))
                    ->schema([
                      \Filament\Forms\Components\Placeholder::make('announcement_info')
                        ->hiddenLabel()
                        ->content(fn ($record) => new \Illuminate\Support\HtmlString('
                          <div class="p-4 rounded-lg bg-blue-50 border border-blue-200">
                            <h4 class="text-xs font-bold text-blue-900 mb-1">Ready for Announcement</h4>
                            <p class="text-xs text-blue-700">
                              Supervisor has decided to <strong>' . strtoupper($record->supervisor_decision) . '</strong> this candidate.
                              HRD can now preview the email notification below and trigger the official announcement to send it.
                            </p>
                          </div>
                        ')),

                      \Filament\Forms\Components\Placeholder::make('email_preview')
                        ->label('Live Email Notification Preview')
                        ->content(function ($record) {
                          $isApproved = $record->supervisor_decision === 'approved';
                          $subject = $isApproved 
                            ? '🎉 Job Offer: ' . ($record->jobVacancy?->title ?? 'Position') . ' at ' . config('app.name', 'AbilityExaminer')
                            : 'Application Status Update: ' . ($record->jobVacancy?->title ?? 'Position') . ' at ' . config('app.name', 'AbilityExaminer');
                            
                          $candidateName = $record->full_name;
                          $jobTitle = $record->jobVacancy?->title ?? 'Position';
                          $department = $record->jobVacancy?->department ?? 'Engineering';
                          $supervisorNotes = $record->supervisor_notes;
                          $appName = config('app.name', 'AbilityExaminer');
                          
                          $body = '';
                          if ($isApproved) {
                            $body = '
                              <p>Dear ' . e($candidateName) . ',</p>
                              <p>We are absolutely thrilled to extend our official <strong>Job Offer</strong> for the position of <strong>' . e($jobTitle) . '</strong> at ' . e($appName) . '!</p>
                              <p>Following your outstanding performance in both the CV screening and the Technical Online Exam, our HRD and Technical Management have unanimously approved your selection for this role.</p>
                              
                              <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin: 16px 0;">
                                <h4 style="margin: 0 0 8px 0; font-size: 0.85rem; font-weight: 700; color: #1e293b;">Position Details:</h4>
                                <ul style="margin: 0; padding-left: 18px; font-size: 0.8rem; color: #475569; list-style: disc;">
                                  <li><strong>Role:</strong> ' . e($jobTitle) . '</li>
                                  <li><strong>Department:</strong> ' . e($department) . '</li>
                                  <li><strong>Status:</strong> Full-time / Contract (Based on Position Details)</li>
                                </ul>
                              </div>';
                              
                            if ($supervisorNotes) {
                              $body .= '
                                <div style="border-left: 4px solid #3b82f6; padding-left: 12px; margin: 16px 0; font-style: italic; color: #475569; background-color: #eff6ff; padding-top: 8px; padding-bottom: 8px; border-radius: 0 6px 6px 0;">
                                  <span style="font-size: 0.7rem; font-weight: 700; display: block; color: #3b82f6; text-transform: uppercase; font-style: normal; margin-bottom: 4px; tracking-wider">Feedback from the Selection Committee:</span>
                                  "' . e($supervisorNotes) . '"
                                </div>';
                            }
                            
                            $body .= '
                              <h4 style="margin: 16px 0 8px 0; font-size: 0.85rem; font-weight: 700; color: #1e293b;">Next Steps:</h4>
                              <p>Our HR representative will be in touch with you shortly via phone or email to discuss:</p>
                              <ol style="margin: 0; padding-left: 18px; font-size: 0.8rem; color: #475569; list-style: decimal;">
                                <li>Compensation, benefits, and standard employment contract details.</li>
                                <li>Necessary documents for onboarding.</li>
                                <li>Your official starting date.</li>
                              </ol>
                              <p style="margin-top: 16px;">Once again, congratulations on this spectacular achievement! We are extremely excited to have you join our team.</p>';
                          } else {
                            $body = '
                              <p>Dear ' . e($candidateName) . ',</p>
                              <p>Thank you very much for your interest in the <strong>' . e($jobTitle) . '</strong> position at ' . e($appName) . ' and for taking the time to complete our online assessment stage.</p>
                              <p>We want to express our appreciation for the effort you put into the Technical Assessment Exam. Our selection committee has carefully reviewed your profile, exam breakdown, and performance metrics.</p>
                              <p>Unfortunately, we regret to inform you that we will not be proceeding with your candidacy for this specific position at this time.</p>
                              <p>Although you were not selected for this particular role, our team was impressed by your technical efforts. We will securely retain your resume in our talent database and may contact you should a future position align with your skills.</p>
                              <p>We wish you the very best of luck with your job search and all your future professional endeavors.</p>';
                          }
                          
                          return new \Illuminate\Support\HtmlString('
                            <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-sm font-sans" style="max-width: 600px; margin-top: 12px;">
                              <!-- Header Info -->
                              <div class="bg-slate-50 border-b border-slate-200 p-4 space-y-1 text-xs" style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 16px;">
                                <div style="display: flex; margin-bottom: 4px;"><span style="font-weight: 600; color: #64748b; width: 64px;">To:</span> <span style="color: #0f172a; font-weight: 600;">' . e($candidateName) . ' &lt;' . e($record->email) . '&gt;</span></div>
                                <div style="display: flex; margin-bottom: 4px;"><span style="font-weight: 600; color: #64748b; width: 64px;">Subject:</span> <span style="color: #0f172a; font-weight: 700;">' . e($subject) . '</span></div>
                                <div style="display: flex;"><span style="font-weight: 600; color: #64748b; width: 64px;">From:</span> <span style="color: #475569; font-weight: 500;">Recruitment Team &lt;' . config('mail.from.address', 'noreply@company.com') . '&gt;</span></div>
                              </div>
                              <!-- Body Content -->
                              <div style="padding: 24px; font-size: 0.85rem; color: #334155; line-height: 1.6; background-color: white;">
                                ' . $body . '
                                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 24px 0 16px 0;" />
                                <p style="font-size: 0.8rem; color: #64748b; margin: 0;">Best regards,</p>
                                <p style="font-size: 0.85rem; font-weight: 700; color: #1e293b; margin: 2px 0 0 0;">The Recruitment Team</p>
                                <p style="font-size: 0.8rem; color: #64748b; margin: 0;">' . e($appName) . '</p>
                                
                                <div style="margin-top: 24px; padding-top: 12px; border-top: 1px dashed #e2e8f0; font-size: 0.75rem; color: #94a3b8; font-style: italic;">
                                  This email was officially processed and released by the Human Resource Department on ' . now()->format('d F Y, H:i') . ' (WIB).
                                </div>
                              </div>
                            </div>
                          ');
                        })
                        ->columnSpanFull(),

                      \Filament\Schemas\Components\Actions::make([
                        \Filament\Actions\Action::make('publish_announcement')
                          ->label('Publish Announcement & Send Email')
                          ->color('success')
                          ->icon('heroicon-m-paper-airplane')
                          ->visible(fn ($record) => $record?->announcement_status !== 'published' && auth()->user()->hasRole(['hr', 'super_admin']))
                          ->requiresConfirmation()
                          ->action(function ($record, $livewire) {
                            $finalStatus = $record->supervisor_decision === 'approved' ? 'accepted' : 'rejected';
                            
                            $record->update([
                              'status' => $finalStatus,
                              'announcement_status' => 'published',
                              'announcement_published_at' => now(),
                            ]);

                            // Dispatch Email
                            try {
                              if ($finalStatus === 'accepted') {
                                \Illuminate\Support\Facades\Mail::to($record->email)
                                  ->send(new \App\Mail\SelectionResultHired($record));
                              } else {
                                \Illuminate\Support\Facades\Mail::to($record->email)
                                  ->send(new \App\Mail\SelectionResultRejected($record));
                              }
                            } catch (\Throwable $e) {
                              \Illuminate\Support\Facades\Log::error("Email failed to send for selection result: " . $e->getMessage());
                            }

                            $url = $finalStatus === 'accepted'
                              ? route('email.preview.hired', $record->id)
                              : route('email.preview.selection_rejected', $record->id);

                            \Filament\Notifications\Notification::make()
                              ->success()
                              ->title('Announcement Published')
                              ->body(new \Illuminate\Support\HtmlString("Selection result published and email sent to {$record->email}.<br><a href='{$url}' target='_blank' style='font-weight: bold; text-decoration: underline;'>Open Email Preview</a>"))
                              ->persistent()
                              ->send();

                            $livewire->js("window.open('{$url}', '_blank')");
                          }),
                      ])->columnSpanFull()
                    ])
                ]),
            ])
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
