<?php

return [
    'model' => [
        'singular' => 'Team',
        'plural' => 'Teams',
    ],

    'sections' => [
        'main' => 'Main information',
        'members' => 'Members',
    ],

    'fields' => [
        'name_uk' => 'Name (uk)',
        'name_en' => 'Name (en)',
        'website' => 'Website',
        'avatar' => 'Avatar',
        'country_uk' => 'Country (uk)',
        'country_en' => 'Country (en)',
        'city_uk' => 'City (uk)',
        'city_en' => 'City (en)',
        'region_uk' => 'Region (uk)',
        'region_en' => 'Region (en)',
        'zip_uk' => 'Zip (uk)',
        'zip_en' => 'Zip (en)',
        'specialization_uk' => 'Specialization (uk)',
        'specialization_en' => 'Specialization (en)',
        'description_uk' => 'Description (uk)',
        'description_en' => 'Description (en)',
        'member' => 'Member',
    ],

    'actions' => [
        'add_member' => 'Add member',
        'leave' => 'Leave team',
    ],

    'roles' => [
        'owner' => 'Owner',
        'member' => 'Member',
    ],

    'table' => [
        'avatar' => 'Avatar',
        'name' => 'Name',
        'members_count' => 'Members',
        'role' => 'Role',
    ],

    'notifications' => [
        'left' => 'You have left the team',
    ],
];
