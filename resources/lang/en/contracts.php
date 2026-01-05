<?php

return [
    'status' => [
        'pending' => 'Pending signature',
        'signed' => 'Signed',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
    ],

    'messages' => [
        'created' => 'Contract created. Please sign it.',
        'pending_exists' => 'You already have a pending contract.',
        'signed' => 'Contract signed successfully.',
        'no_active_contract' => 'You have no active signed contract.',
    ],

    'errors' => [
        'not_pending' => 'Contract is not pending signature.',
        'expired' => 'Contract has expired. Please create a new one.',
        'file_not_found' => 'Contract file not found.',
        'not_owner' => 'You do not have access to this contract.',
    ],

    'validation' => [
        'sign_service_required' => 'Please specify a signing service.',
        'sign_service_invalid' => 'Invalid signing service.',
        'signature_required' => 'Signature is required.',
        'signature_too_short' => 'Signature is too short.',
    ],
];
