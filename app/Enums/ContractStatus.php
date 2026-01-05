<?php

namespace App\Enums;

/**
 * Contract status enum.
 * Corresponds to screens 03.7.5 and 03.7.5.1 from Figma specification.
 */
enum ContractStatus: string
{
    case Pending = 'pending';
    case Signed = 'signed';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('contracts.status.pending'),
            self::Signed => __('contracts.status.signed'),
            self::Rejected => __('contracts.status.rejected'),
            self::Expired => __('contracts.status.expired'),
        };
    }
}
