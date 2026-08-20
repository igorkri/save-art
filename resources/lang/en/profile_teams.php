<?php

return [
    'model' => [
        'singular' => 'Team',
        'plural' => 'Teams',
    ],

    'sections' => [
        'main' => 'Main information',
        'main_description' => 'Cover, name and website of the team',
        'location' => 'Location',
        'location_description' => 'Where the team is based',
        'details' => 'About the team',
        'details_description' => 'Specialization and description of activities',
        'members' => 'Members',
        'members_description' => 'Add members who will join your team',
    ],

    'fields' => [
        'name' => 'Team name',
        'website' => 'Team website',
        'avatar' => 'Team cover',
        'avatar_hint' => 'Recommended ratio 1:1',
        'country' => 'Country',
        'city' => 'City',
        'region' => 'Region',
        'zip' => 'Postal code',
        'specialization' => 'Team specialization',
        'description' => 'About the team',
        'member' => 'Member',
    ],

    'placeholders' => [
        'avatar' => 'Add a cover<br><span>(Required)</span>',
        'name' => 'Please specify the team name',
        'country' => 'Please specify the country',
        'city' => 'Please specify the city',
        'region' => 'Please specify the region',
        'zip' => 'Please specify the postal code',
        'specialization' => 'For example: video shooting',
        'description' => "Tell us about the team's activity",
        'member' => 'Please specify the name or company name',
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
