<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected string $view = 'filament.pages.manage-settings';

    protected static ?string $navigationLabel = 'Decision Rules';

    protected static ?string $title = 'Decision Rules Settings';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['hr', 'super_admin']);
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'c45_ai_threshold' => Setting::get('c45_ai_threshold', '57'),
            'c45_test_threshold' => Setting::get('c45_test_threshold', '63'),
            'c45_leaf1_confidence' => Setting::get('c45_leaf1_confidence', '88.2'),
            'c45_leaf2_confidence' => Setting::get('c45_leaf2_confidence', '79.4'),
            'c45_leaf3_confidence' => Setting::get('c45_leaf3_confidence', '90.6'),
            'c45_confidence_threshold' => Setting::get('c45_confidence_threshold', '80.0'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('C4.5 (J48) Decision Tree Thresholds')
                    ->description('Set numeric score thresholds generated from Weka training J48 model.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('c45_ai_threshold')
                                    ->label('AI Score Threshold (CV Screening)')
                                    ->numeric()
                                    ->required()
                                    ->helperText('Default: 57. Candidates with AI score <= this value will be classified as REJECTED (Rule 1).'),
                                TextInput::make('c45_test_threshold')
                                    ->label('Test Score Threshold (Online Exam)')
                                    ->numeric()
                                    ->required()
                                    ->helperText('Default: 63. Candidates with AI score > threshold and Exam score <= this value will be classified as REJECTED (Rule 2). Otherwise, ACCEPTED (Rule 3).'),
                            ]),
                    ]),

                Section::make('Model Confidence Levels & Alert Threshold (%)')
                    ->description('Specify the historical classification confidence percentages and the warning alert threshold.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('c45_leaf1_confidence')
                                    ->label('Rule 1 Confidence (%)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->helperText('Default: 88.2 (Rule 1 leaf node)'),
                                TextInput::make('c45_leaf2_confidence')
                                    ->label('Rule 2 Confidence (%)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->helperText('Default: 79.4 (Rule 2 leaf node)'),
                                TextInput::make('c45_leaf3_confidence')
                                    ->label('Rule 3 Confidence (%)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->helperText('Default: 90.6 (Rule 3 leaf node)'),
                                TextInput::make('c45_confidence_threshold')
                                    ->label('Confidence Alert Threshold (%)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->helperText('Default: 80.0. Scores below this trigger a "Manual Review Recommended" status.'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Settings Saved')
            ->body('C4.5 decision rules parameters have been updated successfully.')
            ->success()
            ->send();
    }
}
