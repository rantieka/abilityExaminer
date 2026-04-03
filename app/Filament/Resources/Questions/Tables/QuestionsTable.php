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
					->label('Job Vacancy')
					->searchable()
					->sortable()
					->limit(30),
				TextColumn::make('question_text')
					->limit(50)
					->searchable(),
				TextColumn::make('section')
					->badge()
					->colors([
						'secondary' => 'knowledge', 
						'warning' => 'technical'
					])
					->sortable(),
				TextColumn::make('correct_answer'),
				ToggleColumn::make('is_active')
					->label('Active')
					->afterStateUpdated(function ($state) {
						Notification::make()
							->title($state ? 'Question Activated' : 'Question Deactivated')
							->success()
							->send();
					}),
			])
				->filters([
					SelectFilter::make('job_vacancy_id')
							->relationship('jobVacancy', 'title')
							->label('Filter by Job')
							->searchable()
							->default(fn () => request('job_id')), // Auto-select filter from URL
					SelectFilter::make('section')
							->options([
								'knowledge' => 'Knowledge & Foundation',
								'technical' => 'Technical & Case Study',
							]),
					])
					->headerActions([
						Action::make('generate')
							->label('Generate Exam Questions')
							->icon('heroicon-o-sparkles')
							->color('warning')
							->visible(true)
							->requiresConfirmation()
							->modalHeading('Generate Exam Questions?')
							->modalDescription('This will  questions (Knowledge & Technical) using AI. The result will be added to this Job Vacancy.')
							->action(function ($livewire) {
								// Read from the sticky property on the Livewire component
								$jobId = $livewire->job_id ?? null;

								$job = $jobId ? JobVacancy::find($jobId) : null;

								if ($job) {
									GenerateExamQuestions::dispatch($job);
									
									Notification::make()
										->title('Generation Started')
										->body("Generating questions for '{$job->title}' in background...")
										->success()
										->send();
								} else {
									Notification::make()
										->title('Action Required')
										->body("Mohon buka halaman ini melalui tombol 'Questions' di menu Job Vacancy agar sistem tahu target lowongannya.")
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
								->label('Approve Selected')
								->icon('heroicon-o-check-circle')
								->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
							DeleteBulkAction::make(),
						]),
					]);
	}
}
