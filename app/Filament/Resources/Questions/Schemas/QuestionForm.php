<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuestionForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Select::make('job_vacancy_id')
          ->relationship('jobVacancy', 'title')
          ->required()
          ->searchable()
          ->preload(),
        TextInput::make('question_text')
          ->required()
          ->maxLength(255),
        KeyValue::make('options')
          ->keyLabel('Option Label (A, B, C...)')
          ->valueLabel('Answer Text')
          ->reorderable(),
        TextInput::make('correct_answer')
          ->required(),
        Select::make('section')
          ->options([
            'knowledge' => 'Part 1: Knowledge',
            'technical' => 'Part 2: Technical'
          ])
          ->required(),
        Toggle::make('is_active')
          ->default(true),
      ]);
  }
}
