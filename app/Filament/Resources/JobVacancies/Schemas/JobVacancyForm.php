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
              ->helperText(new \Illuminate\Support\HtmlString('<small style="color: #dc3545;">* <i>Gunakan nama teknologi standar agar akurasi pemindaian AI maksimal</i></small>'))
              ->options(function ($get) {
                  $title = strtolower($get('title') ?? '');
                  if (str_contains($title, 'backend')) {
                      return ['PHP' => 'PHP', 'CodeIgniter' => 'CodeIgniter', 'Python' => 'Python', 'Django' => 'Django', 'Laravel' => 'Laravel'];
                  }
                  if (str_contains($title, 'frontend')) {
                      return ['HTML5' => 'HTML5', 'CSS3' => 'CSS3', 'JavaScript' => 'JavaScript', 'React' => 'React', 'Vue' => 'Vue'];
                  }
                  return ['PHP' => 'PHP', 'JavaScript' => 'JavaScript'];
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

              ->options(function ($get) {
                  $title = strtolower($get('title') ?? '');
                  if (str_contains($title, 'backend')) {
                      return ['Ruby' => 'Ruby', 'Go' => 'Go', 'Redis' => 'Redis'];
                  }
                  if (str_contains($title, 'frontend')) {
                      return ['Angular' => 'Angular', 'Svelte' => 'Svelte', 'Tailwind' => 'Tailwind'];
                  }
                  return ['Docker' => 'Docker', 'AWS' => 'AWS'];
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

              ->options(function ($get) {
                  $title = strtolower($get('title') ?? '');
                  if (str_contains($title, 'backend')) {
                      return ['Git' => 'Git', 'MySQL' => 'MySQL', 'REST API' => 'REST API'];
                  }
                  if (str_contains($title, 'frontend')) {
                      return ['Git' => 'Git', 'Bootstrap' => 'Bootstrap', 'Jquery' => 'Jquery'];
                  }
                  return ['English' => 'English', 'Communication' => 'Communication'];
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
