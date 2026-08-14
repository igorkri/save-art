<?php

return [
    'model' => [
        'singular' => 'Message',
        'plural' => 'Messages',
    ],
    'table' => [
        'direction' => 'From',
        'subject' => 'Subject',
        'content' => 'Message',
        'project' => 'Project',
        'created_at' => 'Date',
    ],
    'direction' => [
        'you' => 'You',
        'admin' => 'Administration',
        'system' => 'System',
    ],
    'form' => [
        'project' => 'Project (optional)',
        'subject' => 'Subject',
        'content' => 'Message text',
    ],
    'actions' => [
        'compose' => 'Write a message',
        'reply' => 'Reply',
        'reply_success' => 'Reply sent',
        'mark_as_read' => 'Mark as read',
    ],
];
