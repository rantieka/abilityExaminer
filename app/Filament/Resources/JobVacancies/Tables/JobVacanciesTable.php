<?php

namespace App\Filament\Resources\JobVacancies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use App\Models\JobVacancy;
use Filament\Actions\Action;
use Filament\Tables\Filters\TernaryFilter;

class JobVacanciesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('no')
          ->label('No.')
          ->rowIndex(),
        TextColumn::make('title')
          ->label('Posisi Lowongan')
          ->searchable()
          ->description(fn (JobVacancy $record) => $record->employment_type),
        TextColumn::make('experience_level')
          ->label('Tingkat')
          ->badge()
          ->color(fn (string $state): string => match ($state) {
            'senior' => 'danger',
            'middle' => 'info',
            'junior' => 'success',
            default => 'gray',
          })
          ->formatStateUsing(fn (string $state): string => match ($state) {
            'senior' => 'Senior',
            'middle' => 'Menengah',
            'junior' => 'Junior',
            default => ucfirst($state)
          })
          ->sortable(),
        TextColumn::make('slug')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('createdBy.name')
          ->label('Dibuat Oleh')
          ->sortable()
          ->searchable(),
        TextColumn::make('required_count')
          ->label('Kuota')
          ->sortable(),
        TextColumn::make('published_until')
          ->label('Publikasi Hingga')
          ->date('d M Y')
          ->sortable()
          ->placeholder('Tanpa Batas'),
        TextColumn::make('status')
          ->badge()
          ->color(fn (string $state): string => match ($state) {
            'approved' => 'success',
            'draft' => 'gray',
            'pending' => 'warning',
            'rejected' => 'danger',
            default => 'info',
          })
          ->formatStateUsing(fn (string $state): string => match ($state) {
            'draft' => 'Draft',
            'pending' => 'Menunggu',
            'approved' => 'Aktif',
            'rejected' => 'Ditolak',
            default => ucfirst($state)
          })
          ->searchable()
          ->sortable(),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('archived_at')
            ->label('Diarsipkan')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true)
            ->color('danger'),
      ])
      ->filters([
        TernaryFilter::make('archived')
          ->label('Status')
          ->placeholder('Semua Lowongan')
          ->trueLabel('Arsip')
          ->falseLabel('Aktif')
          ->queries(
              true: fn ($query) => $query->whereNotNull('archived_at'),
              false: fn ($query) => $query->whereNull('archived_at'),
              blank: fn ($query) => $query,
          )
          ->default(false),
      ])
      ->recordActions([
        ViewAction::make()
          ->extraAttributes(['style' => 'text-decoration: none !important']),
        Action::make('questions')
          ->label('Bank Soal')
          ->icon('heroicon-o-document-text')
          ->color('info')
          ->url(fn (JobVacancy $record) => \App\Filament\Resources\Questions\QuestionResource::getUrl('index', [
              'job_id' => $record->id,
          ]))
          ->extraAttributes(['style' => 'text-decoration: none !important']),
        Action::make('manage_publication')
          ->label('Publikasi')
          ->icon('heroicon-o-calendar')
          ->color('info')
          ->visible(fn (JobVacancy $record) => $record->status === 'approved' && auth()->user()->hasRole(['hr', 'super_admin']))
          ->extraAttributes(['style' => 'text-decoration: none !important'])
          ->form([
              \Filament\Forms\Components\DatePicker::make('published_until')
                  ->label('Tanggal Berakhir Tayang')
                  ->required()
                  ->native(false)
                  ->default(fn (JobVacancy $record) => $record->published_until)
                  ->minDate(now()),
          ])
          ->fillForm(fn (JobVacancy $record) => ['published_until' => $record->published_until])
          ->modalHeading('Ubah Batas Publikasi')
          ->modalSubmitActionLabel('Simpan')
          ->modalCancelActionLabel('Batal')
          ->action(function (JobVacancy $record, array $data) {
              $record->update([
                  'published_until' => $data['published_until'],
              ]);
              
              \Filament\Notifications\Notification::make()
                  ->success()
                  ->title('Batas tanggal tayang berhasil diperbarui!')
                  ->send();
          }),
        Action::make('archive')
          ->label('Arsipkan')
          ->icon('heroicon-o-archive-box')
          ->color('danger')
          ->requiresConfirmation()
          ->visible(fn (JobVacancy $record) => $record->archived_at === null && $record->status === 'approved' && auth()->user()->hasRole(['hr', 'super_admin', 'spv']))
          ->extraAttributes(['style' => 'text-decoration: none !important'])
          ->action(fn (JobVacancy $record) => $record->update(['archived_at' => now()])),
        Action::make('unarchive')
          ->label('Aktifkan Kembali')
          ->icon('heroicon-o-arrow-path')
          ->color('success')
          ->requiresConfirmation()
          ->extraAttributes(['style' => 'text-decoration: none !important'])
          ->visible(fn (JobVacancy $record) => $record->archived_at !== null && auth()->user()->hasRole(['hr', 'super_admin', 'spv']))
          ->form([
              \Filament\Forms\Components\DatePicker::make('published_until')
                  ->label('Tanggal Berakhir Tayang Baru')
                  ->helperText('Tentukan batas tanggal kedaluwarsa baru untuk lowongan yang dipulihkan.')
                  ->required()
                  ->native(false)
                  ->default(fn (JobVacancy $record) => $record->published_until && $record->published_until >= now() ? $record->published_until : now()->addDays(30))
                  ->minDate(now()),
          ])
          ->action(function (JobVacancy $record, array $data) {
              $record->update([
                  'archived_at' => null,
                  'published_until' => $data['published_until'],
              ]);

              \Filament\Notifications\Notification::make()
                  ->success()
                  ->title('Lowongan berhasil dipulihkan dan diaktifkan kembali!')
                  ->send();
          }),
        DeleteAction::make()
          ->visible(fn (JobVacancy $record) => in_array($record->status, ['draft', 'rejected', 'pending']) && auth()->user()->hasRole(['hr', 'super_admin'])),
      ]);
  }
}
