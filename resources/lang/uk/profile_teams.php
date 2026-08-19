<?php

return [
    'model' => [
        'singular' => 'Команда',
        'plural' => 'Команди',
    ],

    'sections' => [
        'main' => 'Основна інформація',
        'members' => 'Учасники',
    ],

    'fields' => [
        'name_uk' => 'Назва (укр.)',
        'name_en' => 'Назва (англ.)',
        'website' => 'Веб-сайт',
        'avatar' => 'Аватар',
        'country_uk' => 'Країна (укр.)',
        'country_en' => 'Країна (англ.)',
        'city_uk' => 'Місто (укр.)',
        'city_en' => 'Місто (англ.)',
        'region_uk' => 'Область (укр.)',
        'region_en' => 'Область (англ.)',
        'zip_uk' => 'Індекс (укр.)',
        'zip_en' => 'Індекс (англ.)',
        'specialization_uk' => 'Спеціалізація (укр.)',
        'specialization_en' => 'Спеціалізація (англ.)',
        'description_uk' => 'Опис (укр.)',
        'description_en' => 'Опис (англ.)',
        'member' => 'Учасник',
    ],

    'actions' => [
        'add_member' => 'Додати учасника',
        'leave' => 'Покинути команду',
    ],

    'roles' => [
        'owner' => 'Власник',
        'member' => 'Учасник',
    ],

    'table' => [
        'avatar' => 'Аватар',
        'name' => 'Назва',
        'members_count' => 'Учасників',
        'role' => 'Роль',
    ],

    'notifications' => [
        'left' => 'Ви покинули команду',
    ],
];
