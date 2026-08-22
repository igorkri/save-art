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
    'chat' => [
        'general_thread' => 'General',
        'new_thread_label' => 'New project',
        'new_thread_placeholder' => 'Pick a project to contact us about',
        'empty_thread_list' => 'No messages yet',
        'empty_messages' => 'No messages here yet. Write the first one!',
        'input_placeholder' => 'Type a message…',
        'send' => 'Send',
        'no_project' => 'No project',
    ],
];
