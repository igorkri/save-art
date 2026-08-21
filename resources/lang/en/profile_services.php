<?php

return [
    'model' => [
        'singular' => 'Service',
        'plural' => 'Services',
    ],

    'sections' => [
        'owner' => 'Service owner',
        'main' => 'Main information',
        'price' => 'Price',
        'additional' => 'Additional information',
    ],

    'fields' => [
        'title' => 'Service title',
        'description' => 'Description',
        'location' => 'Location',
        'art_category' => 'Art category',
        'image' => 'Image',
        'currency' => 'Currency',
        'price' => 'Price',
        'price_from' => 'From',
        'negotiable' => 'Price negotiable',
        'options' => 'Options',
        'owner_type' => 'Who provides the service',
        'owner_personal' => 'Personal',
        'owner_team' => 'Team',
        'team' => 'Team',
    ],

    'placeholders' => [
        'image' => 'Upload a service image',
        'title' => 'Enter the full service title',
        'price' => 'Enter the amount',
        'description' => 'Describe the service in detail',
        'option' => 'Option title',
        'location' => 'Enter a location',
    ],

    'actions' => [
        'add_option' => 'Add option',
    ],

    'table' => [
        'image' => 'Image',
        'title' => 'Title',
        'owner' => 'Owner',
        'category' => 'Category',
        'price' => 'Price',
        'price_from' => '"From"',
        'created_at' => 'Created',
    ],
];
