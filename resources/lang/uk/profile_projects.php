<?php

return [
    'model' => [
        'singular' => 'Проєкт',
        'plural' => 'Проєкти',
    ],

    'tabs' => [
        'main' => 'Основна інформація',
        'general' => 'Загальне',
        'budget' => 'Бюджет',
        'parameters' => 'Характеристики',
        'stages' => 'Етапи',
        'bonuses' => 'Бонуси',
    ],

    'sections' => [
        'parameters' => [
            'title' => 'Характеристики',
            'description' => 'Автоматично показуються всі характеристики обраної галузі мистецтва.',
        ],
        'status' => 'Статус',
        'dates' => 'Дати',
        'stats' => 'Статистика',
    ],

    'fields' => [
        'art_category' => 'Галузь мистецтва',
        'title' => 'Назва проєкту',
        'short_description' => 'Короткий опис',
        'tags' => 'Теги',
        'cover' => 'Обкладинка',
        'currency' => 'Валюта',
        'budget_goal' => 'Ціль збору',
        'estimated_days' => 'Орієнтовна кількість днів',
        'budget_items' => 'Деталі бюджету',
        'budget_item_name' => 'Назва',
        'budget_item_amount' => 'Сума',
        'parameter_placeholder' => 'Спочатку оберіть галузь мистецтва на вкладці «Загальне», щоб з’явився список характеристик.',
        'parameter_label' => 'Характеристика',
        'parameter_value' => 'Значення',
        'stages' => 'Етапи реалізації',
        'stage_order' => '№',
        'stage_status' => 'Статус',
        'stage_title' => 'Назва етапу',
        'stage_description' => 'Опис',
        'stage_days_planned' => 'Днів',
        'stage_started_at' => 'Початок',
        'stage_completed_at' => 'Завершено',
        'bonuses' => 'Бонуси для меценатів',
        'bonus_order' => '№',
        'bonus_min_donation' => 'Мін. донат',
        'bonus_max_donation' => 'Макс. донат',
        'bonus_quantity' => 'Кількість',
        'bonus_title' => 'Назва бонусу',
        'bonus_description' => 'Опис бонусу',
        'status_display' => 'Статус проєкту',
        'moderation_display' => 'Статус модерації',
        'code_display' => 'Код проєкту',
        'announced_at' => 'Дата оголошення',
        'planned_completion_at' => 'Планове завершення',
        'likes_count' => 'Лайків',
        'donors_count' => 'Меценатів',
        'budget_collected' => 'Зібрано',
    ],

    'placeholders' => [
        'bonus_quantity' => '∞',
    ],

    'helpers' => [
        'bonus_quantity' => 'Порожнє = необмежено',
    ],

    'defaults' => [
        'budget_item_name' => 'Без назви',
        'stage_title' => 'Новий етап',
        'bonus_title' => 'Новий бонус',
        'status_display' => 'Чернетка (ще не збережено)',
        'code_display' => 'Генерується автоматично',
        'empty' => '—',
    ],

    'table' => [
        'cover' => 'Обкладинка',
        'title' => 'Назва',
        'status' => 'Статус',
        'moderation' => 'Модерація',
        'goal' => 'Ціль',
        'collected' => 'Зібрано',
        'created_at' => 'Створено',
    ],
];
