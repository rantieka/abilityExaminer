<?php

return [

    'column_manager' => [

        'heading' => 'Columns',

        'actions' => [

            'apply' => [
                'label' => 'Terapkan',
            ],

            'reset' => [
                'label' => 'Reset',
            ],

        ],

    ],

    'columns' => [

        'actions' => [
            'label' => 'Action|Actions',
        ],

        'select' => [

            'loading_message' => 'Loading...',

            'no_options_message' => 'No options available.',

            'no_search_results_message' => 'No options match your search.',

            'placeholder' => 'Select an option',

            'searching_message' => 'Searching...',

            'search_prompt' => 'Start typing to search...',

        ],

        'text' => [

            'actions' => [
                'collapse_list' => 'Show :count less',
                'expand_list' => 'Show :count more',
            ],

            'more_list_items' => 'and :count more',

        ],

    ],

    'fields' => [

        'bulk_select_page' => [
            'label' => 'Select/deselect all items for bulk actions.',
        ],

        'bulk_select_record' => [
            'label' => 'Select/deselect item :key for bulk actions.',
        ],

        'bulk_select_group' => [
            'label' => 'Select/deselect group :title for bulk actions.',
        ],

        'search' => [
            'label' => 'Cari',
            'placeholder' => 'Cari',
            'indicator' => 'Cari',
        ],

    ],

    'summary' => [

        'heading' => 'Summary',

        'subheadings' => [
            'all' => 'All :label',
            'group' => ':group summary',
            'page' => 'This page',
        ],

        'summarizers' => [

            'average' => [
                'label' => 'Average',
            ],

            'count' => [
                'label' => 'Count',
            ],

            'sum' => [
                'label' => 'Sum',
            ],

        ],

    ],

    'actions' => [

        'disable_reordering' => [
            'label' => 'Finish reordering records',
        ],

        'enable_reordering' => [
            'label' => 'Reorder records',
        ],

        'filter' => [
            'label' => 'Filter',
        ],

        'group' => [
            'label' => 'Group',
        ],

        'open_bulk_actions' => [
            'label' => 'Pilihan',
        ],

        'column_manager' => [
            'label' => 'Column manager',
        ],

    ],

    'empty' => [

        'heading' => 'No :model',

        'description' => 'Create a :model to get started.',

    ],

    'filters' => [

        'actions' => [

            'apply' => [
                'label' => 'Terapkan',
            ],

            'remove' => [
                'label' => 'Remove filter',
            ],

            'remove_all' => [
                'label' => 'Remove all filters',
                'tooltip' => 'Remove all filters',
            ],

            'reset' => [
                'label' => 'Reset',
            ],

        ],

        'heading' => 'Filter',

        'indicator' => 'Filter',

    ],

    'selection_indicator' => [

        'selected_count' => '1 data terpilih|:count data terpilih',

        'actions' => [

            'select_all' => [
                'label' => 'Pilih semua :count',
            ],

            'deselect_all' => [
                'label' => 'Batal pilih semua',
            ],

        ],

    ],

    'default_model_label' => 'data',

];
