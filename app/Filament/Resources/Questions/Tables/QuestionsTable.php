<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Jobs\GenerateExamQuestions;
use App\Models\JobVacancy;
use Filament\Actions\Action;
use Filament\Actions\BulkAction; 
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class QuestionsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->poll('5s') // Auto-refresh table every 5 seconds
      ->columns([
        TextColumn::make('#')
          ->rowIndex(),  
        TextColumn::make('jobVacancy.title')
          ->label('Lowongan Pekerjaan')
          ->searchable()
          ->sortable()
          ->limit(30),
        TextColumn::make('question_text')
          ->label('Pertanyaan')
          ->limit(50)
          ->searchable(),
        TextColumn::make('section')
          ->label('Bagian')
          ->badge()
          ->formatStateUsing(fn (string $state): string => match ($state) {
              'knowledge' => 'Pengetahuan',
              'technical' => 'Teknis',
              default => $state,
          })
          ->colors([
            'secondary' => 'knowledge', 
            'warning' => 'technical'
          ])
          ->sortable(),
        TextColumn::make('correct_answer')
          ->label('Kunci Jawaban'),
        TextColumn::make('difficulty')
          ->label('Kesulitan')
          ->badge()
          ->formatStateUsing(fn (string $state): string => match ($state) {
              'easy' => 'Mudah',
              'medium' => 'Sedang',
              'hard' => 'Sulit',
              default => $state,
          })
          ->colors([
            'success' => 'easy',
            'warning' => 'medium',
            'danger'  => 'hard',
          ])
          ->sortable(),
        TextColumn::make('skill_category')
          ->label('Kategori Skill')
          ->badge()
          ->formatStateUsing(fn (string $state): string => match ($state) {
              'required' => 'Wajib',
              'preferred' => 'Disukai',
              'bonus' => 'Bonus',
              default => $state,
          })
          ->colors([
            'danger'  => 'required',
            'warning' => 'preferred',
            'success' => 'bonus',
          ])
          ->sortable(),
        ToggleColumn::make('is_active')
          ->label('Status')
          ->afterStateUpdated(function ($state) {
            Notification::make()
              ->title($state ? 'Pertanyaan Diaktifkan' : 'Pertanyaan Dinonaktifkan')
              ->success()
              ->send();
          }),
      ])
      ->filters([
        SelectFilter::make('job_vacancy_id')
          ->relationship('jobVacancy', 'title')
          ->label('Filter Lowongan')
          ->searchable()
          ->default(fn () => request('job_id')), // Auto-select filter from URL
        SelectFilter::make('section')
          ->label('Bagian')
          ->options([
            'knowledge' => 'Pengetahuan & Dasar',
            'technical' => 'Teknis & Studi Kasus',
          ]),
        SelectFilter::make('skill_category')
          ->label('Kategori Skill')
          ->options([
            'required'  => 'Wajib',
            'preferred' => 'Disukai',
            'bonus'     => 'Bonus',
          ]),
      ])
      ->headerActions([
        Action::make('generate')
          ->label('Buat Pertanyaan Ujian')
          ->icon('heroicon-o-sparkles')
          ->color('warning')
          ->visible(true)
          ->requiresConfirmation()
          ->modalHeading('Buat Pertanyaan Ujian?')
          ->modalDescription('Sistem akan membuat pertanyaan ujian (Pengetahuan & Teknis) secara otomatis menggunakan AI. Hasilnya akan ditambahkan ke Lowongan Pekerjaan ini.')
          ->action(function ($livewire) {
            // Read from the sticky property on the Livewire component
            $jobId = $livewire->job_id ?? null;

            $job = $jobId ? JobVacancy::find($jobId) : null;

            if ($job) {
              GenerateExamQuestions::dispatch($job);
              
              Notification::make()
                ->title('Proses Dimulai')
                ->body("Sedang membuat pertanyaan untuk '{$job->title}' di latar belakang...")
                ->success()
                ->send();
            } else {
              Notification::make()
                ->title('Tindakan Diperlukan')
                ->body("Silakan buka halaman ini melalui menu 'Pertanyaan' pada Lowongan Pekerjaan agar sistem mengetahui lowongan target.")
                ->warning()
                ->send();
            }
        }),
      ])
      ->recordActions([
        EditAction::make(),
        DeleteAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          BulkAction::make('approve_all')
            ->label('Setujui yang Dipilih')
            ->icon('heroicon-o-check-circle')
            ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
