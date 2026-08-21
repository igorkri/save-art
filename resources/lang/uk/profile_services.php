<?php

return [
    'model' => [
        'singular' => 'Послуга',
        'plural' => 'Послуги',
    ],

    'sections' => [
        'owner' => 'Власник послуги',
        'main' => 'Основна інформація',
        'price' => 'Вартість',
        'additional' => 'Додаткова інформація',
    ],

    'fields' => [
        'title' => 'Назва послуги',
        'description' => 'Опис',
        'location' => 'Локація',
        'art_category' => 'Галузь мистецтва',
        'image' => 'Зображення',
        'currency' => 'Валюта',
        'price' => 'Ціна',
        'price_from' => 'Від',
        'negotiable' => 'Ціна договірна',
        'options' => 'Опції',
        'owner_type' => 'Хто надає послугу',
        'owner_personal' => 'Особисто',
        'owner_team' => 'Команда',
        'team' => 'Команда',
    ],

    'placeholders' => [
        'image' => 'Завантажте зображення послуги',
        'title' => 'Вкажіть повну назву послуги',
        'price' => 'Вкажіть суму',
        'description' => 'Детально опишіть послугу',
        'option' => 'Назва опції',
        'location' => 'Вкажіть локацію',
    ],

    'actions' => [
        'add_option' => 'Додати опцію',
    ],

    'table' => [
        'image' => 'Зображення',
        'title' => 'Назва',
        'owner' => 'Власник',
        'category' => 'Категорія',
        'price' => 'Ціна',
        'price_from' => '«Від»',
        'created_at' => 'Створено',
    ],
];
