<?php

return [
    'model' => [
        'singular' => 'Donation',
        'plural' => 'Donations',
    ],
    'table' => [
        'direction' => 'Direction',
        'project' => 'Project',
        'platform' => 'Platform',
        'counterparty' => 'From/to',
        'amount' => 'Amount',
        'status' => 'Status',
        'anonymous' => 'Anonymous',
        'paid_at' => 'Paid at',
        'created_at' => 'Created at',
    ],
    'direction' => [
        'received' => 'Received',
        'made' => 'You donated',
    ],
    'anonymous' => [
        'yes' => 'Anonymous',
        'no' => 'Public',
    ],
];
