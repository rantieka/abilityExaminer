<?php

namespace App\Filament\Resources\JobVacancies\RelationManagers;

use App\Jobs\GenerateExamQuestions;
use App\Models\Question;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class QuestionsRelationManager extends RelationManager
{
  protected static string $relationship = 'questions';

  public function isReadOnly(): bool
  {
    return false;
  }

  public function form(Schema $schema): Schema
  {
    return \App\Filament\Resources\Questions\Schemas\QuestionForm::configure($schema);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('question_text')
      ->columns([
        TextColumn::make('question_text')->limit(50)->searchable(),
        // Fixed BadgeColumn -> TextColumn with badge()
        TextColumn::make('section')
          ->badge()
          ->colors([
            'secondary' => 'knowledge', 
            'warning' => 'technical'
          ]),
        TextColumn::make('correct_answer'),
        ToggleColumn::make('is_active')->label('Active'),
      ])
      ->filters([
        //
      ])
      ->headerActions([
        CreateAction::make(),
        // Tombol Generate Soal Background
        Action::make('generate')
          ->label('Generate 35 Exam Questions')
          ->icon('heroicon-o-cloud-arrow-down')
          ->color('primary')
          ->action(fn($livewire) => $this->dispatchGeneration($livewire->ownerRecord)),
      ])
      ->recordActions([
        EditAction::make(),
        DeleteAction::make(),
      ])
      ->bulkActions([
        BulkAction::make('approve_all')
          ->label('Approve Selected')
          ->icon('heroicon-o-check-circle')
          ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
        DeleteBulkAction::make(),
      ]);
    }

    public function dispatchGeneration($job)
    {
      // Dispatch Job to Background
      GenerateExamQuestions::dispatch($job);

      Notification::make()
        ->title('Generation Started')
        ->body('Request to generate questions (Knowledge & Technical) is being processed in the background. Please wait.')
        ->success()
        ->send();
    }
}
