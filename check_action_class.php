<?php
require __DIR__ . '/vendor/autoload.php';

if (class_exists('Filament\Tables\Actions\Action')) {
    echo "Filament\Tables\Actions\Action EXISTS\n";
} else {
    echo "Filament\Tables\Actions\Action DOES NOT EXIST\n";
}

if (class_exists('Filament\Actions\Action')) {
    echo "Filament\Actions\Action EXISTS\n";
} else {
    echo "Filament\Actions\Action DOES NOT EXIST\n";
}
