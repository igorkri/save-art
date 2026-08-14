<?php

namespace App\Filament\Profile\Resources\Donations\Pages;

use App\Filament\Profile\Resources\Donations\DonationResource;
use Filament\Resources\Pages\ListRecords;

class ListDonations extends ListRecords
{
    protected static string $resource = DonationResource::class;
}
