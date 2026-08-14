<?php

return [
    'model' => [
        'singular' => 'Notification',
        'plural' => 'Notifications',
    ],
    'table' => [
        'type' => 'Type',
        'title' => 'Title',
        'message' => 'Message',
        'created_at' => 'Date',
    ],
    'actions' => [
        'mark_as_read' => 'Mark as read',
        'mark_all_as_read' => 'Mark all as read',
        'mark_all_as_read_success' => 'Marked as read: :count',
        'view_project' => 'View project',
    ],
    'bell' => [
        'label' => 'Notifications',
        'empty' => 'No notifications yet',
        'view_all' => 'View all',
        'view_project' => 'View project',
    ],
];
