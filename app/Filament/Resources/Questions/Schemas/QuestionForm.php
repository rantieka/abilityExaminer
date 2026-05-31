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
              ->label('Lowongan Pekerjaan')
              ->relationship('jobVacancy', 'title')
              ->placeholder('Pilih opsi')
              ->required()
              ->searchable()
              ->preload(),
            Select::make('section')
              ->label('Bagian')
              ->placeholder('Pilih opsi')
              ->options([
                'knowledge' => 'Bagian 1: Pengetahuan & Dasar',
                'technical' => 'Bagian 2: Teknis & Analisis'
              ])
              ->required(),
        ]),

        Textarea::make('question_text')
          ->label('Pertanyaan')
          ->required()
          ->rows(3)
          ->columnSpanFull(),

        Section::make('Pilihan Jawaban')
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
                      ->placeholder('Masukkan teks jawaban...')
                      ->columnSpan(9),
                    Actions::make([
                      Action::make('delete')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->label('') 
                        ->tooltip('Hapus Pilihan')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Pilihan Jawaban')
                        ->modalDescription('Apakah Anda yakin ingin menghapus pilihan jawaban ini? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Hapus')
                        ->modalCancelActionLabel('Batal')
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
              ->addActionLabel('Tambah Pilihan')
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
                ->label('Kunci Jawaban (Contoh: A)')
                ->placeholder('A')
                ->required(),

            Select::make('difficulty')
                ->label('Tingkat Kesulitan')
                ->placeholder('Pilih opsi')
                ->options([
                    'easy'   => 'Mudah',
                    'medium' => 'Sedang',
                    'hard'   => 'Sulit',
                ])
                ->required()
                ->default('medium'),

            Select::make('skill_category')
                ->label('Kategori Skill')
                ->placeholder('Pilih opsi')
                ->options([
                    'required'  => 'Wajib',
                    'preferred' => 'Disukai',
                    'bonus'     => 'Bonus',
                ])
                ->required()
                ->default('required'),
          ]),

        Toggle::make('is_active')
            ->label('Status Aktif')
            ->default(true)
            ->columnSpanFull(),
      ]);
  }
}
