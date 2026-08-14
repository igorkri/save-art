<?php

return [
    'model' => [
        'singular' => 'Project',
        'plural' => 'Projects',
    ],

    'tabs' => [
        'main' => 'Main information',
        'general' => 'General',
        'budget' => 'Budget',
        'parameters' => 'Attributes',
        'stages' => 'Stages',
        'bonuses' => 'Bonuses',
    ],

    'sections' => [
        'parameters' => [
            'title' => 'Attributes',
            'description' => 'All attributes of the selected art category are shown automatically.',
        ],
        'status' => 'Status',
        'dates' => 'Dates',
        'stats' => 'Statistics',
    ],

    'fields' => [
        'art_category' => 'Art category',
        'title' => 'Project title',
        'short_description' => 'Short description',
        'tags' => 'Tags',
        'cover' => 'Cover',
        'currency' => 'Currency',
        'budget_goal' => 'Funding goal',
        'estimated_days' => 'Estimated number of days',
        'budget_items' => 'Budget details',
        'budget_item_name' => 'Name',
        'budget_item_amount' => 'Amount',
        'parameter_placeholder' => 'First choose an art category on the "General" tab so the list of attributes appears.',
        'parameter_label' => 'Attribute',
        'parameter_value' => 'Value',
        'stages' => 'Implementation stages',
        'stage_order' => '#',
        'stage_status' => 'Status',
        'stage_title' => 'Stage title',
        'stage_description' => 'Description',
        'stage_days_planned' => 'Days',
        'stage_started_at' => 'Start',
        'stage_completed_at' => 'Completed',
        'bonuses' => 'Bonuses for patrons',
        'bonus_order' => '#',
        'bonus_min_donation' => 'Min. donation',
        'bonus_max_donation' => 'Max. donation',
        'bonus_quantity' => 'Quantity',
        'bonus_title' => 'Bonus title',
        'bonus_description' => 'Bonus description',
        'status_display' => 'Project status',
        'moderation_display' => 'Moderation status',
        'code_display' => 'Project code',
        'announced_at' => 'Announcement date',
        'planned_completion_at' => 'Planned completion',
        'likes_count' => 'Likes',
        'donors_count' => 'Patrons',
        'budget_collected' => 'Collected',
    ],

    'placeholders' => [
        'bonus_quantity' => '∞',
    ],

    'helpers' => [
        'bonus_quantity' => 'Empty = unlimited',
    ],

    'defaults' => [
        'budget_item_name' => 'Untitled',
        'stage_title' => 'New stage',
        'bonus_title' => 'New bonus',
        'status_display' => 'Draft (not saved yet)',
        'code_display' => 'Generated automatically',
        'empty' => '—',
    ],

    'table' => [
        'cover' => 'Cover',
        'title' => 'Title',
        'status' => 'Status',
        'moderation' => 'Moderation',
        'goal' => 'Goal',
        'collected' => 'Collected',
        'created_at' => 'Created',
    ],
];
