<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // super admin can see all users.
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // hr can see only users with role 'applicant'.
        if ($user->hasRole(['hr', 'spv'])) {
            return $query->whereHas('roles', function ($q) {
                $q->where('name', 'applicant');
            });
        }

        // default (if any other role login), only show their own.
        return $query->where('id', $user->id);
    }

    public static function form(Schema $schema): Schema
    {
      return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
      return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
      return [
        //
      ];
    }

    public static function getPages(): array
    {
      return [
        'index' => ListUsers::route('/'),
        'create' => CreateUser::route('/create'),
        'edit' => EditUser::route('/{record}/edit'),
      ];
    }
}
