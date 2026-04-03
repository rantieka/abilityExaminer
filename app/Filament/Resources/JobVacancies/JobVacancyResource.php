<?php

namespace App\Filament\Resources\JobVacancies;

use App\Filament\Resources\JobVacancies\Pages\CreateJobVacancy;
use App\Filament\Resources\JobVacancies\Pages\EditJobVacancy;
use App\Filament\Resources\JobVacancies\Pages\ListJobVacancies;
use App\Filament\Resources\JobVacancies\Pages\ViewJobVacancy;
use App\Filament\Resources\JobVacancies\Schemas\JobVacancyForm;
use App\Filament\Resources\JobVacancies\Tables\JobVacanciesTable;
use App\Models\JobVacancy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JobVacancyResource extends Resource
{
  protected static ?string $model = JobVacancy::class;

  protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

  protected static ?string $recordTitleAttribute = 'title';

  public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
  {
      return false; // Mencegah delete per item
  }

  public static function canDeleteAny(): bool
  {
      return false; // Mencegah bulk delete
  }

  public static function form(Schema $schema): Schema
  {
    return JobVacancyForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return JobVacanciesTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      // RelationManagers\QuestionsRelationManager::class, // Deprecated, moved to dedicated resource
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListJobVacancies::route('/'),
      'create' => CreateJobVacancy::route('/create'),
      'view' => ViewJobVacancy::route('/{record}'),
      'edit' => EditJobVacancy::route('/{record}/edit'),
    ];
  }
}
