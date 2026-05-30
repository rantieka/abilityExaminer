<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\HiredCandidateResource;
use Filament\Resources\Pages\EditRecord;

class EditHiredCandidate extends EditRecord
{
    protected static string $resource = HiredCandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Ubah Data Administrasi Kandidat';
    }

    public function getBreadcrumbs(): array
    {
        return [
            HiredCandidateResource::getUrl() => 'Kandidat Diterima',
            'Ubah Data Administrasi',
        ];
    }
}
