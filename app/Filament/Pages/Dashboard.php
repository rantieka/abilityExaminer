<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
  protected static ?string $title = 'Beranda';

  public static function getNavigationLabel(): string
  {
    return 'Beranda';
  }

  public function getColumns(): int | array
  {
    return 2;
  }
}
