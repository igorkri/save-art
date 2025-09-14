<?php
return [
    'title' => 'Сповіщення',
    'modal' => [
        'heading' => 'Сповіщення',
        'actions' => [
            'clear' => [
                'label' => 'Очистити',
            ],
            'mark_all_as_read' => [
                'label' => 'Позначити всі як прочитані',
            ],
        ],
        'empty' => [
            'heading' => 'Сповіщень немає',
            'description' => 'Повертайтесь пізніше',
        ],
    ],
    'columns' => [
        'text' => [
            'actions' => [
                'collapse_list' => 'Показати на :count менше',
                'expand_list' => 'Показати ще :count',
            ],
            'more_list_items' => 'і ще :count',
        ],
    ],
    'fields' => [
        'bulk_select_page' => [
            'label' => 'Вибрати/зняти вибір усіх елементів для масових дій.',
        ],
        'bulk_select_record' => [
            'label' => 'Вибрати/зняти вибір елемента :key для масових дій.',
        ],
        'bulk_select_group' => [
            'label' => 'Вибрати/зняти вибір групи :title для масових дій.',
        ],
        'search' => [
            'label' => 'Пошук',
            'placeholder' => 'Пошук',
            'indicator' => 'Пошук',
        ],
    ],
    'summary' => [
        'heading' => 'Підсумок',
        'subheadings' => [
            'all' => 'Всі :label',
            'group' => ':group підсумок',
            'page' => 'Ця сторінка',
        ],
        'summarizers' => [
            'average' => [
                'label' => 'Середнє',
            ],
            'count' => [
                'label' => 'Кількість',
            ],
            'sum' => [
                'label' => 'Сума',
            ],
        ],
    ],
    'actions' => [
        'disable_reordering' => [
            'label' => 'Завершити зміну порядку записів',
        ],
        'enable_reordering' => [
            'label' => 'Змінити порядок записів',
        ],
        'filter' => [
            'label' => 'Фільтр',
        ],
        'group' => [
            'label' => 'Група',
        ],
        'open_bulk_actions' => [
            'label' => 'Відкрити дії',
        ],
        'toggle_columns' => [
            'label' => 'Переключити колонки',
        ],
        'archive' => [
            'label' => 'Архівувати',
        ],
        'mark_as_read' => [
            'label' => 'Позначити як прочитане',
        ],
    ],
    'empty' => [
        'heading' => 'Записів не знайдено',
        'description' => 'Створіть :model, щоб розпочати роботу.',
    ],
    'filters' => [
        'actions' => [
            'apply' => [
                'label' => 'Застосувати фільтри',
            ],
            'remove' => [
                'label' => 'Видалити фільтр',
            ],
            'remove_all' => [
                'label' => 'Видалити всі фільтри',
            ],
            'reset' => [
                'label' => 'Скинути',
            ],
        ],
        'heading' => 'Фільтри',
        'indicator' => 'Активні фільтри',
        'multi_select' => [
            'placeholder' => 'Всі',
        ],
        'select' => [
            'placeholder' => 'Всі',
        ],
        'trashed' => [
            'label' => 'Видалені записи',
            'only_trashed' => 'Тільки видалені записи',
            'with_trashed' => 'З видаленими записами',
            'without_trashed' => 'Без видалених записів',
        ],
    ],
    'grouping' => [
        'fields' => [
            'group' => [
                'label' => 'Групувати за',
                'placeholder' => 'Групувати за',
            ],
            'direction' => [
                'label' => 'Напрямок групування',
                'options' => [
                    'asc' => 'За зростанням',
                    'desc' => 'За спаданням',
                ],
            ],
        ],
    ],
    'reorder_indicator' => 'Перетягніть записи в порядку.',
    'selection_indicator' => [
        'selected_count' => 'Вибрано 1 запис|Вибрано :count записів',
        'actions' => [
            'select_all' => [
                'label' => 'Вибрати всі :count',
            ],
            'deselect_all' => [
                'label' => 'Зняти вибір з усіх',
            ],
        ],
    ],
    'sorting' => [
        'fields' => [
            'column' => [
                'label' => 'Сортувати за',
            ],
            'direction' => [
                'label' => 'Напрямок сортування',
                'options' => [
                    'asc' => 'За зростанням',
                    'desc' => 'За спаданням',
                ],
            ],
        ],
    ],
];
