<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Models\Setting;

use BackedEnum;

class ManageSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?string $title = 'Admin Settings';

    protected string $view = 'filament.pages.manage-settings';
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['hr', 'super_admin']);
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'alamat_kantor' => Setting::get('alamat_kantor'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('alamat_kantor')
                    ->label('Office Address')
                    ->helperText('Address to be displayed on job detail pages.')
                    ->rows(4)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        
        Setting::set('alamat_kantor', $data['alamat_kantor']);

        Notification::make() 
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
