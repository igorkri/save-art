<?php

use App\Models\ArtistBoard;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ArtistBoard::query()->each(function (ArtistBoard $artistBoard): void {
            $logoMuseums = $artistBoard->logo_museums;

            if (! is_array($logoMuseums)) {
                return;
            }

            $flattened = array_values(array_filter(array_map(
                fn ($museum) => is_array($museum) ? ($museum['logo_museum'] ?? null) : $museum,
                $logoMuseums,
            )));

            $artistBoard->update(['logo_museums' => $flattened]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ArtistBoard::query()->each(function (ArtistBoard $artistBoard): void {
            $logoMuseums = $artistBoard->logo_museums;

            if (! is_array($logoMuseums)) {
                return;
            }

            $restored = array_map(
                fn ($path) => ['logo_museum' => $path],
                $logoMuseums,
            );

            $artistBoard->update(['logo_museums' => $restored]);
        });
    }
};
