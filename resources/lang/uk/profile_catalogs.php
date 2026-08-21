<?php

return [
    'model' => [
        'singular' => 'Каталог',
        'plural' => 'Каталоги',
    ],

    'sections' => [
        'main' => 'Основна інформація',
    ],

    'fields' => [
        'title_uk' => 'Назва',
        'title_en' => 'Назва (англ.)',
        'art_category' => 'Галузь мистецтва',
        'published_at' => 'Дата публікації',
        'is_primary' => 'Основний каталог',
        'image' => 'Обкладинка',
        'pdf_file' => 'PDF-файл каталогу',
    ],

    'actions' => [
        'set_primary' => 'Зробити основним',
    ],

    'table' => [
        'image' => 'Обкладинка',
        'title' => 'Назва',
        'category' => 'Категорія',
        'published_at' => 'Опубліковано',
        'likes_count' => 'Лайків',
        'is_primary' => 'Основний',
    ],
];
