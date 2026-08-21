<?php

return [
    'model' => [
        'singular' => 'Catalog',
        'plural' => 'Catalogs',
    ],

    'sections' => [
        'main' => 'Main information',
    ],

    'fields' => [
        'title_uk' => 'Title',
        'title_en' => 'Title (en)',
        'art_category' => 'Art category',
        'published_at' => 'Published at',
        'is_primary' => 'Primary catalog',
        'image' => 'Cover image',
        'pdf_file' => 'Catalog PDF file',
    ],

    'actions' => [
        'set_primary' => 'Set as primary',
    ],

    'table' => [
        'image' => 'Cover',
        'title' => 'Title',
        'category' => 'Category',
        'published_at' => 'Published',
        'likes_count' => 'Likes',
        'is_primary' => 'Primary',
    ],
];
