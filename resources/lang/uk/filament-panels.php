<?php

return [
    'components' => [
        'breadcrumbs' => [
            'home' => 'Головна',
        ],
    ],
    'layout' => [
        'actions' => [
            'logout' => [
                'label' => 'Вийти',
            ],
            'open_database_notifications' => [
                'label' => 'Відкрити сповіщення',
            ],
            'open_user_menu' => [
                'label' => 'Меню користувача',
            ],
            'sidebar' => [
                'collapse' => [
                    'label' => 'Згорнути бічну панель',
                ],
                'expand' => [
                    'label' => 'Розгорнути бічну панель',
                ],
            ],
            'theme_switcher' => [
                'dark' => [
                    'label' => 'Увімкнути темну тему',
                ],
                'light' => [
                    'label' => 'Увімкнути світлу тему',
                ],
                'system' => [
                    'label' => 'Увімкнути системну тему',
                ],
            ],
        ],
    ],
    'pages' => [
        'health_check_results' => [
            'buttons' => [
                'refresh' => [
                    'label' => 'Оновити',
                ],
            ],
            'heading' => 'Стан системи',
            'navigation' => [
                'group' => 'Система',
                'label' => 'Стан системи',
            ],
            'notifications' => [
                'check_results' => 'Результати перевірки від',
            ],
        ],
    ],
    'resources' => [
        'pages' => [
            'create_record' => [
                'breadcrumb' => 'Створити',
                'title' => 'Створити :label',
            ],
            'edit_record' => [
                'breadcrumb' => 'Редагувати',
                'title' => 'Редагувати :label',
            ],
            'list_records' => [
                'title' => ':label',
            ],
            'view_record' => [
                'breadcrumb' => 'Переглянути',
                'title' => 'Переглянути :label',
            ],
        ],
        'relation_managers' => [
            'buttons' => [
                'attach' => [
                    'label' => 'Додати',
                ],
                'create' => [
                    'label' => 'Створити',
                ],
            ],
        ],
    ],
    'global_search' => [
        'field' => [
            'placeholder' => 'Глобальний пошук...',
        ],
        'actions' => [
            'open' => [
                'label' => 'Відкрити глобальний пошук',
            ],
        ],
        'no_results' => 'Результатів не знайдено.',
    ],
];
