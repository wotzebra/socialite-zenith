<?php

namespace Wotz\SocialiteZenith;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

/**
 * Zenith is the WOTZ OAuth2 server (Laravel Passport, authorization code flow).
 *
 * No scopes are requested, so Zenith skips its consent screen. A user who is
 * deactivated in Zenith gets a 403 from the user endpoint, which surfaces here as
 * an `Illuminate\Http\Client\RequestException`.
 */
class ZenithProvider extends AbstractProvider
{
    /**
     * @var array<int, string>
     */
    protected $scopes = [];

    protected string $baseUrl = '';

    public function setBaseUrl(?string $baseUrl): static
    {
        $this->baseUrl = rtrim((string) $baseUrl, '/');

        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase("{$this->baseUrl}/oauth/authorize", $state);
    }

    protected function getTokenUrl(): string
    {
        return "{$this->baseUrl}/oauth/token";
    }

    /**
     * Uses the Laravel HTTP client instead of Socialite's Guzzle client, so a
     * failure carries the status code and `Http::fake()` works in tests.
     *
     * @return array<string, mixed>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getAccessTokenResponse($code): array
    {
        return Http::asForm()
            ->acceptJson()
            ->post($this->getTokenUrl(), $this->getTokenFields($code))
            ->throw()
            ->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    protected function getUserByToken($token): array
    {
        return Http::withToken($token)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/user/current")
            ->throw()
            ->json('data');
    }

    /**
     * @param  array<string, mixed>  $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => (string) $user['id'],
            'email' => $user['email'] ?? null,
            'name' => trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')),
        ]);
    }
}
