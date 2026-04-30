<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
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
      ->columns(1)
      ->components([
        Grid::make(2)->schema([
            Select::make('job_vacancy_id')
              ->relationship('jobVacancy', 'title')
              ->required()
              ->searchable()
              ->preload(),
            Select::make('section')
              ->options([
                'knowledge' => 'Part 1: Knowledge & Foundation',
                'technical' => 'Part 2: Technical & Analysis'
              ])
              ->required(),
        ]),

        Textarea::make('question_text')
          ->required()
          ->rows(3)
          ->columnSpanFull(),

        Section::make('Answer Options')
          ->schema([
            Repeater::make('options')
              ->hiddenLabel()
              ->schema([
                Grid::make(12)->schema([
                    TextInput::make('key')
                      ->hiddenLabel()
                      ->required()
                      ->placeholder('A')
                      ->columnSpan(2),
                    Textarea::make('value')
                      ->hiddenLabel()
                      ->required()
                      ->rows(1)
                      ->placeholder('Enter answer text...')
                      ->columnSpan(9),
                    Actions::make([
                      Action::make('delete')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->label('') 
                        ->tooltip('Delete Option')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Answer Option')
                        ->modalDescription('Are you sure you want to delete this answer option? This action cannot be undone.')
                        ->modalSubmitActionLabel('Delete')
                        ->modalCancelActionLabel('Cancel')
                        ->action(function ($component) {
                            $repeater = $component->getParentRepeater();
                            $index = $component->getParentRepeaterItemIndex();
                            $items = $repeater->getState();
                            
                            array_splice($items, $index, 1);
                            
                            $repeater->state($items);
                            $repeater->partiallyRender();
                        }),
                    ])
                    ->columnSpan(1),
                ]),
              ])
              ->columnSpanFull()
              ->reorderable(false)
              ->addActionLabel('Add Option')
              ->addActionAlignment('end')
              ->deletable(false)
              ->afterStateHydrated(function ($component, $state) {
                  if (is_array($state) && !empty($state)) {
                      $firstItem = reset($state);
                      
                      if (!is_array($firstItem)) {
                          $results = [];
                          foreach ($state as $key => $value) {
                              // Map numeric keys to A, B, C, D for the Admin UI input boxes
                              $letterKey = is_numeric($key) ? chr(65 + (int)$key) : strtoupper($key);
                              $results[] = ['key' => $letterKey, 'value' => $value];
                          }
                          $component->state($results);
                      }
                  }
              })
              ->dehydrateStateUsing(function ($state) {
                  $results = [];
                  foreach ($state as $index => $item) {
                      if (isset($item['key'])) {
                        // Force conversion to A, B, C, D if numeric, or ensure uppercase if letter
                        $key = is_numeric($item['key']) ? chr(65 + (int)$item['key']) : strtoupper(trim($item['key']));
                        $results[$key] = $item['value'];
                      }
                  }
                  return $results;
              }),

            TextInput::make('correct_answer')
                ->label('Correct Answer Label (e.g., A)')
                ->placeholder('A')
                ->required(),

            Select::make('difficulty')
                ->label('Difficulty Level')
                ->options([
                    'easy'   => 'Easy',
                    'medium' => 'Medium',
                    'hard'   => 'Hard',
                ])
                ->required()
                ->default('medium'),

            Select::make('skill_category')
                ->label('Skill Category')
                ->options([
                    'required'  => 'Required Skills',
                    'preferred' => 'Preferred Skills',
                    'bonus'     => 'Bonus Skills',
                ])
                ->required()
                ->default('required'),
          ]),

        Toggle::make('is_active')
            ->label('Is Active')
            ->default(true)
            ->columnSpanFull(),
      ]);
  }
}
