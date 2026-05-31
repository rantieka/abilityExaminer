<?php

return [

    'single' => [

        'label' => 'Hapus',

        'modal' => [

            'heading' => 'Hapus :label',

            'actions' => [

                'delete' => [
                    'label' => 'Hapus',
                ],

            ],

        ],

        'notifications' => [

            'deleted' => [
                'title' => 'Berhasil dihapus',
            ],

        ],

    ],

    'multiple' => [

        'label' => 'Hapus yang terpilih',

        'modal' => [

            'heading' => 'Hapus :label yang terpilih',

            'actions' => [

                'delete' => [
                    'label' => 'Hapus',
                ],

            ],

        ],

        'notifications' => [

            'deleted' => [
                'title' => 'Berhasil dihapus',
            ],

            'deleted_partial' => [
                'title' => 'Berhasil menghapus :count dari :total',
            ],

            'deleted_none' => [
                'title' => 'Gagal menghapus',
            ],

        ],

    ],

];
