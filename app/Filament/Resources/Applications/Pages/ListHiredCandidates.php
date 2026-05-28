<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\HiredCandidateResource;
use Filament\Resources\Pages\ListRecords;

class ListHiredCandidates extends ListRecords
{
    protected static string $resource = HiredCandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
