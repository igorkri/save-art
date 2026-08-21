<?php

return [
    'title' => 'My Profile',
    'navigation_label' => 'Profile',
    'saved_notification' => 'Profile saved',
    'completion_prompt' => 'Please complete your registration to get full access to all platform features.',

    'tabs' => [
        'personal' => 'Personal details',
        'legal' => 'Legal details',
        'social' => 'Social media',
        'security' => 'Security',
        'agreement' => 'Cooperation agreement',
        'documents' => 'Documents',
    ],

    'sections' => [
        'personal' => [
            'title' => 'Personal details',
            'description' => 'Information displayed on your public author profile.',
        ],
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
            'title' => 'Add your social media',
            'description' => 'Share your public profiles if you would like to grow your network faster.',
        ],
        'security' => [
            'title' => 'Security settings',
            'description' => 'Change your password and email address here.',
        ],
        'agreement' => [
            'title' => 'Cooperation agreement between the User and the Platform',
            'description' => 'A cooperation agreement must be signed to conduct financial transactions between the Platform and the User.',
            'signing_note' => 'We use the Vchasno electronic document management service.',
            'documents_note' => 'Identity documents are also required. Upload photos or scans of your passport and tax number.',
            'optional_note' => 'You can complete this section at any time. If you are an artist who wants to receive project payouts, complete it now.',
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
        'email_change' => 'If you change your email, we will send a confirmation message to the new address.',
    ],

    'actions' => [
        'save' => 'Save',
        'sign_agreement' => 'Sign agreement',
        'upload_documents' => 'Upload documents',
        'delete_profile' => 'Delete profile',
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
        'contract_prepared' => 'Agreement prepared',
        'contract_prepared_body' => 'The agreement was created and is awaiting an electronic signature.',
        'delete_confirmation' => 'A profile deletion request will be sent to the administration. This action should not be taken accidentally.',
        'deletion_requested' => 'Profile deletion request sent.',
    ],

    'completion_required' => [
        'title' => 'Complete your profile first',
        'body' => 'Save the required profile fields (name, photo, and phone) to unlock the other sections of the dashboard.',
    ],
];
