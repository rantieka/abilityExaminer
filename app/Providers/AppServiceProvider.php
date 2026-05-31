<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
  * Register any application services.
  */
  public function register(): void
  {
    //
  }

  /**
  * Bootstrap any application services.
  */
  public function boot(): void
  {
    \Illuminate\Support\Facades\Gate::policy(\App\Models\Question::class, \App\Policies\QuestionPolicy::class);

    \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
      $table
        ->emptyStateHeading('Tidak ada data')
        ->searchPlaceholder('Cari');
    });

    \Filament\Actions\ViewAction::configureUsing(function (\Filament\Actions\ViewAction $action): void {
      $action->label('Detail');
    });

    \Filament\Support\Facades\FilamentView::registerRenderHook(
      \Filament\Tables\View\TablesRenderHook::TOOLBAR_COLUMN_MANAGER_TRIGGER_AFTER,
      fn (array $scopes): string => in_array(\App\Filament\Resources\Applications\Pages\ListApplications::class, $scopes) 
          ? \Illuminate\Support\Facades\Blade::render('
              <div class="flex items-center ml-2">
                  <x-filament::icon-button
                      tag="a"
                      href="/admin/export-applications-csv"
                      download
                      rel="external"
                      icon="heroicon-m-arrow-down-tray"
                      color="gray"
                      tooltip="Export CSV"
                  />
              </div>
            ')
          : ''
    );
  }
}
