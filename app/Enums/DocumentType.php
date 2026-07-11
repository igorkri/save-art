<?php

namespace App\Enums;

enum DocumentType: string
{
    case Contract = 'contract';
    case KycDocument = 'kyc_document';
    case TaxForm = 'tax_form';
    case IdentityProof = 'identity_proof';
    case AddressProof = 'address_proof';
    case BankDetails = 'bank_details';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'Контракт',
            self::KycDocument => 'KYC Документ',
            self::TaxForm => 'Податкова форма',
            self::IdentityProof => 'Посвідчення особи',
            self::AddressProof => 'Довідка про адресу',
            self::BankDetails => 'Банківські реквізити',
            self::Other => 'Інше',
        };
    }
}
