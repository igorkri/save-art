<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationToken;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FrontendSsoRedirectController extends Controller
{
    public function __invoke(Request $request, string $application): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('filament.profile.auth.login');
        }

        abort_unless(
            $user instanceof User
            && ! $user->is_blocked
            && $user->canAccessPanel(Filament::getPanel('profile')),
            403,
        );

        [$frontendUrl, $targetApp] = match ($application) {
            'save-art' => [config('app.frontend_url'), 'save_art'],
            'art-ua-info' => [config('services.art_ua_info_frontend_url'), 'art_ua_info'],
            default => abort(404),
        };

        $grant = ImpersonationToken::issue($user, $user, targetApp: $targetApp);

        return redirect()->away(
            rtrim((string) $frontendUrl, '/').'/impersonate/'.$grant->token,
        );
    }
}
