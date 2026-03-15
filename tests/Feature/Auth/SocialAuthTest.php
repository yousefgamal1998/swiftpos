<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_route_sends_user_to_provider(): void
    {
        $driver = Mockery::mock();
        $driver
            ->shouldReceive('redirect')
            ->once()
            ->andReturn(new SymfonyRedirectResponse('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($driver);

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $response = $this->get(route('oauth.google.redirect'));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_oauth_callback_creates_user_and_logs_in(): void
    {
        $socialUser = Mockery::mock(SocialiteUserContract::class);
        $socialUser->shouldReceive('getId')->andReturn('google-123');
        $socialUser->shouldReceive('getEmail')->andReturn('social.user@example.com');
        $socialUser->shouldReceive('getName')->andReturn('Social User');
        $socialUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');

        $driver = Mockery::mock();
        $driver
            ->shouldReceive('user')
            ->once()
            ->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($driver);

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $response = $this->get(route('oauth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'social.user@example.com',
            'provider' => 'google',
            'google_id' => 'google-123',
            'avatar' => 'https://example.com/avatar.png',
        ]);
    }

    public function test_oauth_callback_links_existing_user_by_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing.user@example.com',
            'provider' => null,
            'google_id' => null,
            'avatar' => null,
        ]);

        $socialUser = Mockery::mock(SocialiteUserContract::class);
        $socialUser->shouldReceive('getId')->andReturn('google-999');
        $socialUser->shouldReceive('getEmail')->andReturn('existing.user@example.com');
        $socialUser->shouldReceive('getName')->andReturn('Existing User');
        $socialUser->shouldReceive('getAvatar')->andReturn('https://example.com/existing-avatar.png');

        $driver = Mockery::mock();
        $driver
            ->shouldReceive('user')
            ->once()
            ->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($driver);

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $response = $this->get(route('oauth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($existingUser->fresh());
        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'email' => 'existing.user@example.com',
            'provider' => 'google',
            'google_id' => 'google-999',
            'avatar' => 'https://example.com/existing-avatar.png',
        ]);
    }

    public function test_users_table_has_provider_google_id_and_avatar_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns('users', ['provider', 'google_id', 'avatar'])
        );
    }

    public function test_unsupported_provider_returns_not_found(): void
    {
        $response = $this->get('/auth/twitter/redirect');

        $response->assertNotFound();
    }
}
