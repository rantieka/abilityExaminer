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

    protected static string | \UnitEnum | null $navigationGroup = 'Rekrutmen';

    protected static ?string $navigationLabel = 'Kandidat Diterima';

    protected static ?string $pluralModelLabel = 'Kandidat Diterima';

    protected static ?string $modelLabel = 'Kandidat Diterima';

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
                \Filament\Schemas\Components\Section::make('Profil Kandidat')
                    ->description('Informasi dasar kandidat yang diterima.')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->disabled(),
                        TextInput::make('email')
                            ->label('Email')
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('No. Telepon')
                            ->disabled(),
                        TextInput::make('job_vacancy')
                            ->label('Posisi Pekerjaan')
                            ->formatStateUsing(fn ($record) => $record->jobVacancy?->title)
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Rincian Administrasi & Kontrak')
                    ->description('Silakan lengkapi rincian administrasi dan kontrak kandidat.')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('ktp_number')
                            ->label('Nomor KTP')
                            ->maxLength(16)
                            ->numeric()
                            ->placeholder('Contoh: 3201234567890123'),
                        TextInput::make('npwp_number')
                            ->label('Nomor NPWP')
                            ->maxLength(20)
                            ->placeholder('Contoh: 12.345.678.9-012.345'),
                        TextInput::make('bank_name')
                            ->label('Nama Bank')
                            ->placeholder('Contoh: BCA, Mandiri, BNI'),
                        TextInput::make('bank_account_number')
                            ->label('Nomor Rekening Bank')
                            ->numeric()
                            ->placeholder('Contoh: 1234567890'),
                        FileUpload::make('contract_file_path')
                            ->label('Berkas Kontrak Kerja')
                            ->directory('contracts')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120) // 5MB limit
                            ->columnSpanFull(),
                        Select::make('hired_administrative_status')
                            ->label('Status Administrasi')
                            ->options([
                                'pending' => 'Menunggu Verifikasi',
                                'in_progress' => 'Sedang Diproses',
                                'completed' => 'Selesai / Terverifikasi',
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
                    ->label('Nama Kandidat')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jobVacancy.title')
                    ->label('Posisi Pekerjaan')
                    ->searchable(),
                TextColumn::make('hired_administrative_status')
                    ->label('Status Administrasi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Menunggu',
                        'in_progress' => 'Sedang Diproses',
                        'completed' => 'Selesai',
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('announcement_published_at')
                    ->label('Tanggal Diterima')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->locale('id')->translatedFormat('d M Y') : '-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('hired_administrative_status')
                    ->label('Status Administrasi')
                    ->options([
                        'pending' => 'Menunggu',
                        'in_progress' => 'Sedang Diproses',
                        'completed' => 'Selesai',
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
