<?php
// Simulate the options closure logic
$defaults = [
    'IT' => 'IT',
    'HR' => 'HR',
    'Finance' => 'Finance',
    'Operations' => 'Operations',
    'Sales' => 'Sales',
    'Marketing' => 'Marketing',
];

$existing = \App\Models\JobVacancy::query()
    ->whereNotNull('department')
    ->distinct()
    ->pluck('department', 'department')
    ->toArray();

$merged = array_merge($defaults, $existing);

echo "Defaults:\n";
print_r($defaults);
echo "\nExisting from DB:\n";
print_r($existing);
echo "\nMerged Options:\n";
print_r($merged);
