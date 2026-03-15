<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return $this->redirectToProvider('google');
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        return $this->handleProviderCallback('google');
    }

    public function redirectToFacebook(): RedirectResponse
    {
        return $this->redirectToProvider('facebook');
    }

    public function handleFacebookCallback(): RedirectResponse
    {
        return $this->handleProviderCallback('facebook');
    }

    private function redirectToProvider(string $provider): RedirectResponse
    {
        if (! $this->isProviderConfigured($provider)) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => ucfirst($provider).' OAuth is not configured. Please set '.$this->oauthEnvHint($provider).'.',
                ]);
        }

        /** @var RedirectResponse */
        return Socialite::driver($provider)->redirect();
    }

    private function handleProviderCallback(string $provider): RedirectResponse
    {
        if (! $this->isProviderConfigured($provider)) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => ucfirst($provider).' OAuth is not configured. Please set '.$this->oauthEnvHint($provider).'.',
                ]);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Authentication with '.ucfirst($provider).' failed. Please try again.',
                ]);
        }

        return $this->loginWithSocialUser($provider, $socialUser);
    }

    private function loginWithSocialUser(string $provider, SocialiteUser $socialUser): RedirectResponse
    {
        $email = $socialUser->getEmail();
        if (! $email) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'No email address was returned by '.ucfirst($provider).'.',
                ]);
        }

        $socialId = (string) $socialUser->getId();

        $user = User::query()
            ->where('provider', $provider)
            ->where('provider_id', $socialId)
            ->first();

        if (! $user) {
            $user = User::query()
                ->where('provider', $provider)
                ->where('google_id', $socialId)
                ->first();
        }

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        $payload = [
            'name' => $user?->name ?: ($socialUser->getName() ?: Str::before($email, '@')),
            'email' => $email,
            'provider' => $provider,
            'provider_id' => $socialId,
            'avatar' => $socialUser->getAvatar(),
            'email_verified_at' => $user?->email_verified_at ?? now(),
        ];

        if ($provider === 'google') {
            $payload['google_id'] = $socialId;
        }

        if ($user) {
            $user->update($payload);
        } else {
            $user = User::query()->create([
                ...$payload,
                'password' => Hash::make(Str::random(40)),
            ]);
        }

        if ($user->getRoleNames()->isEmpty()) {
            $user->assignRole(Role::firstOrCreate(['name' => 'cashier']));
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function isProviderConfigured(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"))
            && filled(config("services.{$provider}.redirect"));
    }

    private function oauthEnvHint(string $provider): string
    {
        $upper = Str::upper($provider);

        return "{$upper}_CLIENT_ID, {$upper}_CLIENT_SECRET, and {$upper}_REDIRECT_URI";
    }
}
