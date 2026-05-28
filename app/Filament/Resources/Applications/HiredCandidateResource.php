<?php

namespace App\Filament\Resources\Applications;

use App\Filament\Resources\Applications\Pages\ListHiredCandidates;
use App\Filament\Resources\Applications\Pages\EditHiredCandidate;
use App\Models\Application;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class HiredCandidateResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $slug = 'hired-candidates';

    protected static string | \UnitEnum | null $navigationGroup = 'Recruitment';

    protected static ?string $navigationLabel = 'Hired Candidates';

    protected static ?string $pluralModelLabel = 'Hired Candidates';

    protected static ?string $modelLabel = 'Hired Candidate';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', 'accepted')
            ->where('announcement_status', 'published');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Candidate Profile')
                    ->description('Basic information of the hired candidate.')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Full Name')
                            ->disabled(),
                        TextInput::make('email')
                            ->label('Email')
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('Phone')
                            ->disabled(),
                        TextInput::make('job_vacancy')
                            ->label('Job Position')
                            ->formatStateUsing(fn ($record) => $record->jobVacancy?->title)
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Administrative & Contract Details')
                    ->description('Please complete the candidate\'s administrative and contract details.')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('ktp_number')
                            ->label('KTP (ID Card) Number')
                            ->maxLength(16)
                            ->numeric()
                            ->placeholder('e.g., 3201234567890123'),
                        TextInput::make('npwp_number')
                            ->label('NPWP (Tax ID) Number')
                            ->maxLength(20)
                            ->placeholder('e.g., 12.345.678.9-012.345'),
                        TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->placeholder('e.g., BCA, Mandiri, BNI'),
                        TextInput::make('bank_account_number')
                            ->label('Bank Account Number')
                            ->numeric()
                            ->placeholder('e.g., 1234567890'),
                        FileUpload::make('contract_file_path')
                            ->label('Employment Contract File')
                            ->directory('contracts')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120) // 5MB limit
                            ->columnSpanFull(),
                        Select::make('hired_administrative_status')
                            ->label('Administrative Status')
                            ->options([
                                'pending' => 'Pending Verification',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed / Verified',
                            ])
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),
                TextColumn::make('full_name')
                    ->label('Candidate Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jobVacancy.title')
                    ->label('Job Position')
                    ->searchable(),
                TextColumn::make('hired_administrative_status')
                    ->label('Administrative Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'pending' => 'heroicon-m-clock',
                        'in_progress' => 'heroicon-m-arrow-path',
                        'completed' => 'heroicon-m-check-badge',
                        default => null,
                    }),
                TextColumn::make('announcement_published_at')
                    ->label('Hired Date')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('hired_administrative_status')
                    ->label('Admin Status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHiredCandidates::route('/'),
            'edit' => EditHiredCandidate::route('/{record}/edit'),
        ];
    }
}
