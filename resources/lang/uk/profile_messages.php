<?php

return [
    'model' => [
        'singular' => 'Повідомлення',
        'plural' => 'Повідомлення',
    ],
    'table' => [
        'direction' => 'Від кого',
        'subject' => 'Тема',
        'content' => 'Текст',
        'project' => 'Проєкт',
        'created_at' => 'Дата',
    ],
    'direction' => [
        'you' => 'Ви',
        'admin' => 'Адміністрація',
        'system' => 'Система',
    ],
    'form' => [
        'project' => 'Проєкт (опціонально)',
        'subject' => 'Тема',
        'content' => 'Текст повідомлення',
    ],
    'actions' => [
        'compose' => 'Написати повідомлення',
        'reply' => 'Відповісти',
        'reply_success' => 'Відповідь надіслано',
        'mark_as_read' => 'Прочитано',
    ],
    'chat' => [
        'general_thread' => 'Загальні',
        'new_thread_label' => 'Новий проєкт',
        'new_thread_placeholder' => 'Обрати проєкт для звернення',
        'empty_thread_list' => 'Повідомлень ще немає',
        'empty_messages' => 'Тут ще немає повідомлень. Напишіть перше!',
        'input_placeholder' => 'Введіть повідомлення…',
        'send' => 'Надіслати',
        'no_project' => 'Без проєкту',
    ],
];
