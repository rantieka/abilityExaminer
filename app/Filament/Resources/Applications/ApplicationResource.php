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

    protected static string | \UnitEnum | null $navigationGroup = 'Rekrutmen';

    protected static ?string $navigationLabel = 'Pelamar';

    protected static ?string $modelLabel = 'Pelamar';

    protected static ?string $pluralModelLabel = 'Pelamar';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
      return $schema
        ->components([
          // Basic Information (Full Width)
          // Using Filament\Schemas\Components\Tabs because Filament\Forms\Components\Tabs is not found
          // (Assuming this is a custom or specific version of Filament where Schemas namespace is used for Tabs)
          \Filament\Schemas\Components\Tabs::make('Detail Pelamar')
            ->columnSpanFull()
            ->tabs([
                // Tab 1: Applicant Profile
                \Filament\Schemas\Components\Tabs\Tab::make('Profil Pelamar')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('job_position')
                            ->label('Posisi Pekerjaan')
                            ->formatStateUsing(fn ($record) => $record->jobVacancy?->title)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('created_at')
                            ->label('Tanggal Melamar')
                            ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->locale('id')->translatedFormat('d M Y') : '-')
                            ->disabled()
                            ->dehydrated(false),
                        
                        TextInput::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn ($state, $record) => match (true) {
                                $state === 'pending' && $record->ai_score === null => 'Memindai CV...',
                                $state === 'pending' && $record->ai_score !== null => 'Menunggu Peninjauan',
                                $state === 'reviewed' => 'Sudah Ditinjau',
                                $state === 'accepted' && $record->announcement_status === 'published' => 'Diterima Kerja',
                                $state === 'accepted' && $record->test_completed_at !== null && $record->hrd_decision === 'recommended' && $record->supervisor_decision === 'pending' => 'Peninjauan SPV',
                                $state === 'accepted' && $record->test_completed_at !== null && in_array($record->supervisor_decision, ['approved', 'rejected']) && $record->announcement_status !== 'published' => 'Siap Diumumkan',
                                $state === 'accepted' && $record->test_completed_at !== null => 'Ujian Selesai',
                                $state === 'accepted' && $record->test_completed_at === null => 'Ujian Aktif',
                                $state === 'rejected' && $record->announcement_status === 'published' => 'Ditolak (Final)',
                                $state === 'rejected' && $record->hrd_decision === 'rejected' => 'Ditolak oleh HRD',
                                $state === 'rejected' => 'Ditolak',
                                default => ucfirst($state),
                            })
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('email')
                            ->label('Alamat Email')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('phone')
                            ->label('No. Telepon')
                            ->disabled()
                            ->dehydrated(),
                        
                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->visible(fn ($record) => $record?->status === 'rejected')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->rows(3),
                    ]),

                // Tab 2: CV Screening
                \Filament\Schemas\Components\Tabs\Tab::make('Skrining CV')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                // Left Column: CV Document
                                \Filament\Schemas\Components\Section::make('Dokumen CV')
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
                                                    Buka di Tab Baru
                                                </a>
                                                </div>
                                                <iframe 
                                                src="' . asset('storage/' . $record->cv_path) . '" 
                                                class="w-full border border-gray-300 rounded-lg"
                                                style="height: 800px; width: 100%"
                                                frameborder="0">
                                                <p>Browser Anda tidak mendukung pratinjau PDF. 
                                                    <a href="' . asset('storage/' . $record->cv_path) . '">Unduh file PDF</a>
                                                </p>
                                                </iframe>
                                            </div>
                                        ')
                                        : new \Illuminate\Support\HtmlString('<p class="text-gray-500 italic">Tidak ada CV yang diunggah</p>')
                                        ),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false),
                                
                                // Right Column: AI Analysis
                                \Filament\Schemas\Components\Section::make('Analisis AI')
                                    ->heading(fn ($record) => $record ? new \Illuminate\Support\HtmlString('
                                        <div class="ai-analysis-header">
                                            <span>Analisis AI</span>
                                            <div class="ai-score-wrapper ' . match(true) {
                                                $record->ai_score >= 80 => 'ai-score-badge-green',
                                                $record->ai_score >= 50 => 'ai-score-badge-yellow',
                                                default => 'ai-score-badge-red',
                                            } . '">
                                                <span class="ai-score-text">Skor Kesesuaian: ' . ($record->ai_score ?? 0) . '%</span>
                                            </div>
                                        </div>
                                    ') : 'Analisis AI')
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
                                                            <h3 class="text-sm font-bold text-red-800">Skrining AI Gagal</h3>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-center h-24 bg-gray-50 rounded-lg border border-gray-200">
                                                    <p class="text-xs text-gray-500 italic">Data analisis tidak tersedia karena kesalahan sistem. Silakan tinjau CV secara manual.</p>
                                                </div>
                                                ' : '
                                                <!-- Executive Summary Card -->
                                                <div class="exec-summary-card">
                                                <div class="card-header">
                                                    <div class="icon-wrapper icon-blue">
                                                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    </div>
                                                    <div>
                                                        <h4 class="card-title text-blue-900">Ringkasan Eksekutif</h4>
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
                                                        <h4 class="card-title text-green-900">Kelebihan Utama</h4>
                                                    </div>
                                                    <ul class="list-container">
                                                        ' . (isset($record->ai_analysis['pros']) && is_array($record->ai_analysis['pros']) 
                                                        ? implode('', array_map(fn($item) => '
                                                            <li class="list-item">
                                                                 <span class="bullet-point bg-green-400"></span>
                                                                 <span class="list-text">' . htmlspecialchars($item) . '</span>
                                                            </li>', $record->ai_analysis['pros']))
                                                        : '<li class="no-data">Tidak ada data</li>') . '
                                                    </ul>
                                                    </div>
                                                    
                                                    <!-- Profile Weaknesses Card -->
                                                    <div class="weaknesses-card">
                                                    <div class="card-header">
                                                        <div class="icon-wrapper icon-red">
                                                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                        </div>
                                                        <h4 class="card-title text-red-900">Kelemahan Profil</h4>
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
                                                                <span class="list-text italic text-gray-500">Tidak ada kelemahan menonjol yang teridentifikasi.</span>
                                                            </li>') . '
                                                    </ul>
                                                    </div>
                                                </div>') . '
                                            </div>')
                                        : new \Illuminate\Support\HtmlString('
                                            <div class="flex items-center justify-center h-32 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                                <p class="text-gray-500 italic">Analisis AI belum tersedia</p>
                                            </div>
                                        ')
                                        ),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false),
                            ]),
                    ]),

                // Tab 3: Generate Token
                \Filament\Schemas\Components\Tabs\Tab::make('Buat Token')
                  ->icon('heroicon-o-key')
                  ->visible(fn () => auth()->user()->hasRole(['hr', 'super_admin']))
                  ->schema([
                    \Filament\Schemas\Components\Section::make('Konfigurasi Akses')
                      ->description('Kelola token akses ujian dan masa berlaku untuk pelamar.')
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
                                  ->label('Masa Berlaku Token')
                                  ->native(false)
                                  ->default(fn() => now()->addDays(7))
                                  ->requiredWith('test_token')
                                  ->minDate(now()),
                              ]),
                        
                        \Filament\Forms\Components\TextInput::make('test_link_preview')
                          ->label('Pratinjau Link Ujian')
                          ->readOnly()
                          ->dehydrated(false)
                          ->formatStateUsing(function ($get) {
                            $token = $get('test_token');
                            if (!$token) return 'Belum ada token yang dibuat.';
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
                                        \Filament\Actions\Action::make('create_questions')
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
              \Filament\Schemas\Components\Tabs\Tab::make('Hasil Penilaian')
                ->icon('heroicon-o-academic-cap')
                ->schema([
                  \Filament\Schemas\Components\Section::make('Hasil Ujian')
                    ->schema([
                      \Filament\Forms\Components\ViewField::make('test_details')
                        ->view('filament.forms.components.test-score-details'),
                    ]),
                ]),

              \Filament\Schemas\Components\Tabs\Tab::make('Seleksi & Persetujuan')
                ->icon('heroicon-o-check-badge')
                ->schema([
                  \Filament\Schemas\Components\Section::make('Rekomendasi Keputusan')
                    ->description('Rekomendasi prediktif berdasarkan skor kandidat')
                    ->schema([
                      \Filament\Forms\Components\ViewField::make('c45_selection_summary')
                        ->view('filament.forms.components.c45-selection-summary'),
                    ])
                    ->columnSpanFull(),

                  \Filament\Schemas\Components\Section::make('Keputusan Seleksi HRD')
                    ->description('Rekomendasi Rekruter HRD')
                    ->visible(fn ($record) => $record?->test_completed_at !== null)
                    ->schema([
                      \Filament\Forms\Components\Placeholder::make('hrd_decision_summary')
                        ->label('Rekomendasi HRD')
                        ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                          $record->hrd_decision === 'recommended' 
                            ? '<span style="color: #16a34a; font-weight: bold; font-size: 0.875rem;">Direkomendasikan untuk Diterima</span>'
                            : '<span style="color: #dc2626; font-weight: bold; font-size: 0.875rem;">Ditolak</span>'
                        ))
                        ->visible(fn ($record) => $record?->hrd_decision !== 'pending'),

                      \Filament\Forms\Components\Placeholder::make('hrd_notes_summary')
                        ->label('Catatan Seleksi HRD')
                        ->content(fn ($record) => $record->hrd_notes ?: '-')
                        ->visible(fn ($record) => $record?->hrd_decision !== 'pending'),

                      \Filament\Schemas\Components\Actions::make([
                        \Filament\Actions\Action::make('recommend_hrd')
                          ->label('Rekomendasikan untuk Diterima')
                          ->color('success')
                          ->requiresConfirmation()
                          ->modalIcon('heroicon-o-check-circle')
                          ->modalIconColor('success')
                          ->modalHeading('Rekomendasikan Kandidat')
                          ->modalDescription('Apakah Anda yakin ingin merekomendasikan kandidat ini kepada Supervisor?')
                          ->form([
                            \Filament\Forms\Components\Textarea::make('notes')
                              ->label('Catatan Seleksi HRD')
                              ->placeholder('Berikan alasan utama untuk rekomendasi ini...')
                              ->rows(3)
                              ->required(),
                          ])
                          ->action(function ($record, array $data, $livewire) {
                            $record->update([
                              'hrd_decision' => 'recommended',
                              'hrd_notes' => $data['notes'],
                              'hrd_decided_at' => now(),
                            ]);

                            // Send notification to Supervisor (Job Vacancy creator)
                            $recipient = $record->jobVacancy?->createdBy;
                            if ($recipient) {
                              try {
                                \Filament\Notifications\Notification::make()
                                  ->info()
                                  ->title('Candidate Recommended for Review')
                                  ->body("HRD has recommended candidate '{$record->full_name}' for position '{$record->jobVacancy->title}'. Please review and make the final decision.")
                                  ->actions([
                                    \Filament\Actions\Action::make('view_candidate')
                                      ->label('Review Candidate')
                                      ->button()
                                      ->url(\App\Filament\Resources\Applications\ApplicationResource::getUrl('view', ['record' => $record->id])),
                                  ])
                                  ->sendToDatabase($recipient);
                              } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to notify supervisor: " . $e->getMessage());
                              }
                            }

                            \Filament\Notifications\Notification::make()
                              ->success()
                              ->title('Kandidat Direkomendasikan')
                              ->body('Kandidat telah berhasil direkomendasikan kepada Supervisor.')
                              ->send();

                            $livewire->js("window.location.reload();");
                          }),

                        \Filament\Actions\Action::make('reject_hrd')
                          ->label('Tolak Kandidat')
                          ->color('danger')
                          ->requiresConfirmation()
                          ->modalIcon('heroicon-o-x-circle')
                          ->modalIconColor('danger')
                          ->modalHeading('Tolak Kandidat')
                          ->modalDescription('Apakah Anda yakin ingin menolak kandidat ini? Ini akan mengakhiri proses rekrutmen mereka.')
                          ->form([
                            \Filament\Forms\Components\Textarea::make('notes')
                              ->label('Alasan Penolakan / Catatan')
                              ->placeholder('Berikan alasan utama untuk penolakan ini...')
                              ->rows(3)
                              ->required(),
                          ])
                          ->action(function ($record, array $data, $livewire) {
                            $record->update([
                              'hrd_decision' => 'rejected',
                              'hrd_notes' => $data['notes'],
                              'hrd_decided_at' => now(),
                              'status' => 'rejected',
                              'rejection_reason' => $data['notes'],
                            ]);

                            // Send rejection email immediately
                            try {
                              \Illuminate\Support\Facades\Mail::to($record->email)
                                ->send(new \App\Mail\SelectionResultRejected($record));
                            } catch (\Throwable $e) {
                              \Illuminate\Support\Facades\Log::error("Email failed to send for HRD rejection: " . $e->getMessage());
                            }

                            $url = route('email.preview.selection_rejected', $record->id);

                            \Filament\Notifications\Notification::make()
                              ->success()
                              ->title('Kandidat Ditolak')
                              ->body(new \Illuminate\Support\HtmlString("Kandidat telah ditolak oleh HRD dan diberi tahu melalui email.<br><a href='{$url}' target='_blank' style='font-weight: bold; text-decoration: underline;'>Buka Pratinjau Email</a>"))
                              ->persistent()
                              ->send();

                            $livewire->js("window.location.reload();");
                          }),
                      ])
                      ->visible(fn ($record) => $record?->hrd_decision === 'pending' && auth()->user()->hasRole(['hr', 'super_admin']))
                      ->columnSpanFull()
                    ])
                    ->columns(2),

                  \Filament\Schemas\Components\Section::make('Persetujuan Akhir Supervisor')
                    ->description('Persetujuan Teknis / Manajemen')
                    ->visible(fn ($record) => $record?->hrd_decision === 'recommended')
                    ->schema([
                      \Filament\Forms\Components\Placeholder::make('supervisor_decision_summary')
                        ->label('Keputusan Supervisor')
                        ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                          $record->supervisor_decision === 'approved' 
                            ? '<span style="color: #2563eb; font-weight: bold; font-size: 0.875rem;">Disetujui untuk Diterima</span>'
                            : '<span style="color: #dc2626; font-weight: bold; font-size: 0.875rem;">Ditolak / Tidak Disetujui</span>'
                        ))
                        ->visible(fn ($record) => $record?->supervisor_decision !== 'pending'),

                      \Filament\Forms\Components\Placeholder::make('supervisor_notes_summary')
                        ->label('Catatan Seleksi Supervisor')
                        ->content(fn ($record) => $record->supervisor_notes ?: '-')
                        ->visible(fn ($record) => $record?->supervisor_decision !== 'pending'),

                      \Filament\Schemas\Components\Actions::make([
                        \Filament\Actions\Action::make('approve_spv')
                          ->label('Setujui untuk Diterima')
                          ->color('success')
                          ->requiresConfirmation()
                          ->modalIcon('heroicon-o-check-circle')
                          ->modalIconColor('success')
                          ->modalHeading('Setujui Kandidat untuk Diterima')
                          ->modalDescription('Apakah Anda yakin ingin menyetujui kandidat ini? Ini akan memberikan wewenang kepada HRD untuk menerbitkan surat penawaran kerja (offering letter) resmi.')
                          ->form([
                            \Filament\Forms\Components\Textarea::make('notes')
                              ->label('Catatan Seleksi Supervisor')
                              ->placeholder('Berikan umpan balik teknis atau alasan untuk persetujuan penerimaan...')
                              ->rows(3)
                              ->required(),
                          ])
                          ->action(function ($record, array $data, $livewire) {
                            $record->update([
                              'supervisor_decision' => 'approved',
                              'supervisor_notes' => $data['notes'],
                              'supervisor_decided_at' => now(),
                            ]);

                            // Notify HRD users
                            $hrUsers = \App\Models\User::role(['hr', 'super_admin'])->get();
                            foreach ($hrUsers as $hrUser) {
                              try {
                                \Filament\Notifications\Notification::make()
                                  ->success()
                                  ->title('Candidate Approved by Supervisor')
                                  ->body("Supervisor has approved candidate '{$record->full_name}' for '{$record->jobVacancy->title}'. Ready for announcement.")
                                  ->actions([
                                    \Filament\Actions\Action::make('view_candidate')
                                      ->label('Review & Publish')
                                      ->button()
                                      ->url(\App\Filament\Resources\Applications\ApplicationResource::getUrl('view', ['record' => $record->id])),
                                  ])
                                  ->sendToDatabase($hrUser);
                              } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to notify HRD of SPV approval: " . $e->getMessage());
                              }
                            }

                            \Filament\Notifications\Notification::make()
                              ->success()
                              ->title('Penerimaan Disetujui')
                              ->body('Persetujuan seleksi akhir telah berhasil diproses.')
                              ->send();

                            $livewire->js("window.location.reload();");
                          }),

                        \Filament\Actions\Action::make('reject_spv')
                          ->label('Tolak / Jangan Setujui')
                          ->color('danger')
                          ->requiresConfirmation()
                          ->modalIcon('heroicon-o-x-circle')
                          ->modalIconColor('danger')
                          ->modalHeading('Tolak Kandidat')
                          ->modalDescription('Apakah Anda yakin ingin menolak dan tidak menyetujui kandidat ini?')
                          ->form([
                            \Filament\Forms\Components\Textarea::make('notes')
                              ->label('Alasan Penolakan / Catatan')
                              ->placeholder('Berikan alasan teknis untuk penolakan...')
                              ->rows(3)
                              ->required(),
                          ])
                          ->action(function ($record, array $data, $livewire) {
                            $record->update([
                              'supervisor_decision' => 'rejected',
                              'supervisor_notes' => $data['notes'],
                              'supervisor_decided_at' => now(),
                            ]);

                            // Notify HRD users
                            $hrUsers = \App\Models\User::role(['hr', 'super_admin'])->get();
                            foreach ($hrUsers as $hrUser) {
                              try {
                                \Filament\Notifications\Notification::make()
                                  ->danger()
                                  ->title('Candidate Rejected by Supervisor')
                                  ->body("Supervisor has rejected candidate '{$record->full_name}' for '{$record->jobVacancy->title}'. Ready for announcement.")
                                  ->actions([
                                    \Filament\Actions\Action::make('view_candidate')
                                      ->label('Review & Publish')
                                      ->button()
                                      ->url(\App\Filament\Resources\Applications\ApplicationResource::getUrl('view', ['record' => $record->id])),
                                  ])
                                  ->sendToDatabase($hrUser);
                              } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to notify HRD of SPV rejection: " . $e->getMessage());
                              }
                            }

                            \Filament\Notifications\Notification::make()
                              ->success()
                              ->title('Penerimaan Ditolak')
                              ->body('Kandidat telah ditolak oleh Supervisor.')
                              ->send();

                            $livewire->js("window.location.reload();");
                          }),
                      ])
                      ->visible(fn ($record) => $record?->supervisor_decision === 'pending' && auth()->user()->hasRole(['spv', 'super_admin']))
                      ->columnSpanFull()
                    ])
                    ->columns(2),

                  \Filament\Schemas\Components\Section::make('Pengumuman Seleksi')
                    ->description('Pengumuman Resmi & Email Pelamar')
                    ->visible(fn ($record) => in_array($record?->supervisor_decision, ['approved', 'rejected']) && auth()->user()->hasRole(['hr', 'super_admin']))
                    ->schema([
                      \Filament\Forms\Components\Placeholder::make('announcement_info')
                        ->hiddenLabel()
                        ->content(fn ($record) => new \Illuminate\Support\HtmlString('
                          <div class="p-4 rounded-lg bg-blue-50 border border-blue-200">
                            <h4 class="text-xs font-bold text-blue-900 mb-1">Siap untuk Diumumkan</h4>
                            <p class="text-xs text-blue-700">
                              Supervisor telah memutuskan untuk <strong>' . ($record->supervisor_decision === 'approved' ? 'MENYETUJUI' : 'MENOLAK') . '</strong> kandidat ini.
                              HRD sekarang dapat melihat pratinjau pemberitahuan email di bawah ini dan memicu pengumuman resmi untuk mengirimkannya.
                            </p>
                          </div>
                        ')),

                      \Filament\Schemas\Components\Actions::make([
                        \Filament\Actions\Action::make('publish_announcement')
                          ->label('Publikasikan Pengumuman & Kirim Email')
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
                              ->title('Pengumuman Dipublikasikan')
                              ->body(new \Illuminate\Support\HtmlString("Hasil seleksi telah dipublikasikan dan email dikirim ke {$record->email}.<br><a href='{$url}' target='_blank' style='font-weight: bold; text-decoration: underline;'>Buka Pratinjau Email</a>"))
                              ->persistent()
                              ->send();
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
