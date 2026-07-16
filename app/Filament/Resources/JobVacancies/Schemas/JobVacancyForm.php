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
        Section::make('Posisi Jabatan')
          ->columnSpanFull()
          ->columns(2)
          ->disabled(fn ($record) => $record?->status === 'approved')
          ->schema([

            \Filament\Forms\Components\TextInput::make('title')
              ->label('Nama Posisi/Jabatan')
              ->required()
              ->placeholder('Contoh: Backend Developer')
              ->datalist([
                  'Frontend Developer',
                  'Backend Developer',
                  'Fullstack Developer',
                  'Mobile Developer'
              ])
              ->lazy()
              ->helperText(new \Illuminate\Support\HtmlString('<small style="color: #dc3545;">* <i>Gunakan nama posisi standar agar akurasi pemindaian AI maksimal</i></small>'))
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
              ->label('Tingkat Pengalaman')
              ->placeholder('Pilih tingkat pengalaman')
              ->required()
              ->options([
                  'junior' => 'Junior / Lulusan Baru',
                  'middle' => 'Tingkat Menengah',
                  'senior' => 'Senior',
              ])
              ->default('junior')
              ->columnSpanFull(),
          ]),

        // SECTION 2: DETAIL PEKERJAAN
        Section::make('Rincian Pekerjaan')
          ->columnSpanFull()
          ->columns(2)
          ->disabled(fn ($record) => $record?->status === 'approved')
          ->schema([
            TextInput::make('department')
              ->label('Departemen')
              ->default('IT')
              ->disabled()
              ->dehydrated()
              ->required(),

            Select::make('employment_type')
              ->label('Jenis Pekerjaan')
              ->placeholder('Pilih jenis pekerjaan')
              ->options([
                  'Full Time' => 'Penuh Waktu (Full Time)',
                  'Contract' => 'Kontrak (Contract)',
              ])
              ->required(),
            TextInput::make('work_arrangement')
              ->label('Sistem Kerja')
              ->default('WFO')
              ->disabled()
              ->dehydrated()
              ->required(),
            TextInput::make('required_count')
              ->label('Kuota Lowongan')
              ->numeric()
              ->default(1)
              ->minValue(1)
              ->required(),
            DatePicker::make('published_until')
                  ->label('Publikasi Hingga')
                  ->native(false)
                  ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord && Auth::user()->hasRole(['hr', 'super_admin']))
                  ->required(fn ($get) => $get('status') === 'approved'),
          ]),

        // SECTION 3: DESKRIPSI & KUALIFIKASI
        Section::make('Deskripsi & Kualifikasi Umum')
            ->columnSpanFull()
            ->columns(2)
            ->disabled(fn ($record) => $record?->status === 'approved')
            ->schema([
                Textarea::make('description')
                  ->label('Deskripsi Pekerjaan')
                  ->required()
                  ->rows(10)
                  ->columnSpanFull(),
                RichEditor::make('qualifications')
                  ->label('Kualifikasi Umum')
                  ->columnSpanFull(),
            ]),

        // SECTION: SKILLS
        Section::make('Keahlian Teknis')
          ->columnSpanFull()
          ->columns(3)
          ->disabled(fn ($record) => $record?->status === 'approved')
          ->schema([
            Select::make('required_skills')
              ->label('Keahlian Wajib')
              ->placeholder('Pilih keahlian wajib')
              ->multiple()
              ->searchable()
              ->searchPrompt('Mulai ketik untuk mencari...')
              ->searchingMessage('Sedang mencari...')
              ->noSearchResultsMessage('Tidak ada hasil ditemukan')
              ->required()
              ->createOptionForm([
                  TextInput::make('name')
                      ->label('Nama Keahlian')
                      ->required(),
              ])
              ->createOptionUsing(function (array $data): string {
                return $data['name'];
              })
              ->createOptionAction(function ($action) {
                return $action->extraModalFooterActions([]);
              })
              ->helperText(new \Illuminate\Support\HtmlString('<small style="color: #dc3545;">* <i>Gunakan nama teknologi standar agar akurasi pemindaian AI maksimal</i></small>'))
              ->validationMessages(['in' => 'Keahlian wajib yang dipilih tidak valid.'])
              ->options(function ($get, $record) {
                  $title = strtolower($get('title') ?? '');
                  if (str_contains($title, 'backend')) {
                      $options = ['PHP' => 'PHP', 'CodeIgniter' => 'CodeIgniter', 'Python' => 'Python', 'Django' => 'Django', 'Laravel' => 'Laravel'];
                  } elseif (str_contains($title, 'frontend')) {
                      $options = ['HTML5' => 'HTML5', 'CSS3' => 'CSS3', 'JavaScript' => 'JavaScript', 'React' => 'React', 'Vue' => 'Vue'];
                  } else {
                      $options = ['PHP' => 'PHP', 'JavaScript' => 'JavaScript'];
                  }
                  
                  $selected = $get('required_skills') ?? [];
                  $requestInput = collect(request()->all())->dot();
                  $matchingKeys = $requestInput->keys()->filter(fn ($key) => str_contains($key, 'required_skills'));
                  if ($matchingKeys->isNotEmpty()) {
                      $selected = $matchingKeys->map(fn ($key) => $requestInput->get($key))->toArray();
                  }

                  if ($record && is_array($record->required_skills)) {
                      $selected = array_merge($selected, $record->required_skills);
                  }
                  foreach ($selected as $skill) {
                      if ($skill && !isset($options[$skill])) {
                          $options[$skill] = $skill;
                      }
                  }
                  return $options;
              }),
            Select::make('preferred_skills')
              ->label('Keahlian yang Diutamakan')
              ->placeholder('Pilih keahlian yang diutamakan')
              ->multiple()
              ->searchable()
              ->searchPrompt('Mulai ketik untuk mencari...')
              ->searchingMessage('Sedang mencari...')
              ->noSearchResultsMessage('Tidak ada hasil ditemukan')
              ->createOptionForm([
                  TextInput::make('name')
                      ->label('Nama Keahlian')
                      ->required(),
              ])
              ->createOptionUsing(function (array $data): string {
                  return $data['name'];
              })
              ->createOptionAction(function ($action) {
                  return $action->extraModalFooterActions([]);
              })

              ->validationMessages(['in' => 'Keahlian yang diutamakan yang dipilih tidak valid.'])
              ->options(function ($get, $record) {
                  $title = strtolower($get('title') ?? '');
                  if (str_contains($title, 'backend')) {
                      $options = ['Ruby' => 'Ruby', 'Go' => 'Go', 'Redis' => 'Redis'];
                  } elseif (str_contains($title, 'frontend')) {
                      $options = ['Angular' => 'Angular', 'Svelte' => 'Svelte', 'Tailwind' => 'Tailwind'];
                  } else {
                      $options = ['Docker' => 'Docker', 'AWS' => 'AWS'];
                  }
                  
                  $selected = $get('preferred_skills') ?? [];
                  $requestInput = collect(request()->all())->dot();
                  $matchingKeys = $requestInput->keys()->filter(fn ($key) => str_contains($key, 'preferred_skills'));
                  if ($matchingKeys->isNotEmpty()) {
                      $selected = $matchingKeys->map(fn ($key) => $requestInput->get($key))->toArray();
                  }

                  if ($record && is_array($record->preferred_skills)) {
                      $selected = array_merge($selected, $record->preferred_skills);
                  }
                  foreach ($selected as $skill) {
                      if ($skill && !isset($options[$skill])) {
                          $options[$skill] = $skill;
                      }
                  }
                  return $options;
              }),
            Select::make('bonus_skills')
              ->label('Keahlian Tambahan')
              ->placeholder('Pilih keahlian tambahan')
              ->multiple()
              ->searchable()
              ->searchPrompt('Mulai ketik untuk mencari...')
              ->searchingMessage('Sedang mencari...')
              ->noSearchResultsMessage('Tidak ada hasil ditemukan')
              ->createOptionForm([
                  TextInput::make('name')
                      ->label('Nama Keahlian')
                      ->required(),
              ])
              ->createOptionUsing(function (array $data): string {
                  return $data['name'];
              })
              ->createOptionAction(function ($action) {
                  return $action->extraModalFooterActions([]);
              })

              ->validationMessages(['in' => 'Keahlian tambahan yang dipilih tidak valid.'])
              ->options(function ($get, $record) {
                  $title = strtolower($get('title') ?? '');
                  if (str_contains($title, 'backend')) {
                      $options = ['Git' => 'Git', 'MySQL' => 'MySQL', 'REST API' => 'REST API'];
                  } elseif (str_contains($title, 'frontend')) {
                      $options = ['Git' => 'Git', 'Bootstrap' => 'Bootstrap', 'Jquery' => 'Jquery'];
                  } else {
                      $options = ['English' => 'English', 'Communication' => 'Communication'];
                  }
                  
                  $selected = $get('bonus_skills') ?? [];
                  $requestInput = collect(request()->all())->dot();
                  $matchingKeys = $requestInput->keys()->filter(fn ($key) => str_contains($key, 'bonus_skills'));
                  if ($matchingKeys->isNotEmpty()) {
                      $selected = $matchingKeys->map(fn ($key) => $requestInput->get($key))->toArray();
                  }

                  if ($record && is_array($record->bonus_skills)) {
                      $selected = array_merge($selected, $record->bonus_skills);
                  }
                  foreach ($selected as $skill) {
                      if ($skill && !isset($options[$skill])) {
                          $options[$skill] = $skill;
                      }
                  }
                  return $options;
              }),
          ]),
         Select::make('status')
          ->placeholder('Pilih status')
          ->options([
              'draft' => 'Draft',
              'pending' => 'Menunggu Persetujuan',
              'approved' => 'Disetujui',
              'rejected' => 'Ditolak',
          ])
          ->default(fn () => Auth::user()->hasRole(['hr', 'super_admin']) ? 'approved' : 'pending')
          ->disabled(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord || Auth::user()->hasRole('spv'))
          ->dehydrated()
          ->required()
          ->visible(fn() => Auth::user()->hasRole(['hr', 'super_admin', 'spv'])),
        
        // Informasi Penolakan
        Section::make('Informasi Penolakan')
          ->schema([
            Placeholder::make('rejection_reason')
            ->label('Alasan Penolakan')
            ->content(fn ($record) => $record?->rejection_reason ?? '-'),
          
            Placeholder::make('rejected_by')
            ->label('Ditolak Oleh')
            ->content(fn ($record) => $record?->rejectedBy?->name ?? '-'),
          
            Placeholder::make('rejected_at')
            ->label('Ditolak Pada')
            ->content(fn ($record) => $record?->rejected_at?->locale('id')->translatedFormat('d M Y, H:i') ?? '-'),
          ])
          ->visible(fn ($record) => $record?->status === 'rejected' && Auth::user()->hasRole(['hr', 'super_admin', 'spv']))
          ->collapsible()
          ->collapsed(false),
      ]);
  }
}
