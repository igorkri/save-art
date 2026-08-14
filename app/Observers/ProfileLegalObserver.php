<?php

namespace App\Observers;

use App\Models\ProfileLegal;
use App\Observers\Concerns\DeletesReplacedFile;

class ProfileLegalObserver
{
    use DeletesReplacedFile;

    /**
     * Handle the ProfileLegal "updated" event.
     * Прибираємо старий логотип, якщо він був замінений або видалений.
     */
    public function updated(ProfileLegal $profileLegal): void
    {
        $this->deleteReplacedFile($profileLegal, 'logo');
    }
}
