<?php

return [
    'title' => 'My Profile',
    'saved_notification' => 'Profile saved',

    'tabs' => [
        'personal' => 'Personal profile',
        'legal' => 'Legal profile',
        'social' => 'Social media',
        'documents' => 'Documents',
    ],

    'sections' => [
        'account' => [
            'title' => 'Account details',
            'description' => 'The email and password used to sign in to your account.',
        ],
        'avatar' => [
            'title' => 'Avatar and name',
            'description' => 'This information is shown on your public page.',
        ],
        'address' => [
            'title' => 'Delivery address',
            'description' => 'Used to deliver patron rewards.',
        ],
        'about' => [
            'title' => 'About you',
            'description' => 'Tell us about yourself — visitors of your public page will see this text.',
        ],
        'legal_details' => [
            'title' => 'Legal profile',
            'description' => 'Fill this in if you receive payments as a legal entity or sole proprietor. Turn off the switch below if this isn\'t needed right now.',
        ],
        'legal_company' => [
            'title' => 'Company',
            'description' => 'Company details and logo used on documents.',
        ],
        'legal_contacts' => [
            'title' => 'Contacts and address',
            'description' => 'Used for communication and on company documents.',
        ],
        'social_links' => [
            'title' => 'Links',
            'description' => 'Add links to your profiles and website. All fields are optional — fill in only the ones you have.',
        ],
        'documents' => [
            'title' => 'Profile documents',
            'description' => 'Uploaded documents are stored in your profile and can be used for electronic signing.',
        ],
    ],

    'fields' => [
        'profile_type' => 'Profile type',
        'phone' => 'Phone',
        'full_name' => 'Full name',
        'profession' => 'Profession',
        'tags' => 'Tags',
        'avatar' => 'Avatar',
        'country' => 'Country',
        'region' => 'Region',
        'city' => 'City',
        'postal_code' => 'Postal code',
        'description' => 'Description / bio',
        'legal_active' => 'Active legal profile',
        'currency' => 'Primary currency',
        'logo' => 'Logo',
        'company_name' => 'Company name',
        'edrpou' => 'EDRPOU',
        'authorized_person' => 'Authorized person',
        'legal_phone' => 'Phone',
        'email' => 'Email',
        'legal_address' => 'Address',
        'files' => 'Files',
    ],

    'placeholders' => [
        'phone' => '+380 XX XXX XX XX',
        'full_name' => 'E.g. Olena Kovalenko',
        'profession' => 'E.g. Painter, illustrator',
        'tags' => 'painting, illustration, watercolor',
        'region' => 'Kyiv region',
        'city' => 'Kyiv',
        'postal_code' => '01001',
        'description' => 'Tell us about your creative path, style, and sources of inspiration...',
        'company_name' => 'E.g. Art Studio LLC',
        'edrpou' => '12345678',
        'authorized_person' => 'E.g. John Smith',
        'legal_email' => 'company@example.com',
        'legal_address' => 'Street, building, city, postal code',
    ],

    'helpers' => [
        'phone' => 'In international format, starting with +.',
        'tags' => 'They help patrons find you.',
        'avatar' => 'Square image, up to 5 MB.',
        'legal_active' => 'When turned off, the details below aren\'t used for payouts.',
        'edrpou' => 'EDRPOU code (8 digits) or tax ID for sole proprietors.',
        'legal_logo' => 'Square image, up to 5 MB.',
    ],

    'currency' => [
        'uah' => 'Hryvnia (UAH)',
        'usd' => 'Dollar (USD)',
        'eur' => 'Euro (EUR)',
    ],

    'social' => [
        'website' => 'Website',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'twitter' => 'X / Twitter',
        'telegram' => 'Telegram',
        'youtube' => 'YouTube',
        'youtube_channel' => 'YouTube channel',
        'tiktok' => 'TikTok',
        'github' => 'GitHub',
        'pinterest' => 'Pinterest',
        'whatsapp' => 'WhatsApp',
        'deviantart' => 'DeviantArt',
    ],

    'messages' => [
        'document_unreadable' => 'Failed to read the document.',
        'document_duplicate' => 'This document has already been uploaded.',
    ],

    'completion_required' => [
        'title' => 'Complete your profile first',
        'body' => 'Save the required profile fields (name, photo, and phone) to unlock the other sections of the dashboard.',
    ],
];
