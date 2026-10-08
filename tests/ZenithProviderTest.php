<?php

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Wotz\SocialiteZenith\ZenithProvider;

function fakeZenith(int $userStatus = 200): void
{
    Http::preventStrayRequests();

    Http::fake([
        'zenith.test/oauth/token' => Http::response([
            'token_type' => 'Bearer',
            'access_token' => 'zenith-access-token',
            'expires_in' => 3600,
        ]),
        'zenith.test/api/user/current' => $userStatus === 200
            ? Http::response(['data' => [
                'id' => 42,
                'firstname' => 'Wim',
                'lastname' => 'Zebra',
                'email' => 'wim@wotz.be',
                'is_active' => true,
            ]])
            : Http::response(['message' => 'Your account has been deactivated.'], $userStatus),
    ]);
}

function zenithDriver(): ZenithProvider
{
    $request = Request::create('/oauth/callback/zenith', 'GET', ['code' => 'auth-code']);

    return Socialite::driver('zenith')->setRequest($request)->stateless();
}

it('registers the zenith driver with the configured base url', function () {
    expect(Socialite::driver('zenith'))
        ->toBeInstanceOf(ZenithProvider::class)
        ->getBaseUrl()->toBe('https://zenith.test');
});

it('redirects to the zenith authorize url without scopes', function () {
    $location = Socialite::driver('zenith')->stateless()->redirect()->getTargetUrl();

    parse_str(parse_url($location, PHP_URL_QUERY), $query);

    expect($location)->toStartWith('https://zenith.test/oauth/authorize?')
        ->and($query)->toMatchArray([
            'client_id' => 'zenith-client',
            'redirect_uri' => 'https://app.test/oauth/callback/zenith',
            'response_type' => 'code',
            'scope' => '',
        ]);
});

it('exchanges the code and maps the current zenith user', function () {
    fakeZenith();

    $user = zenithDriver()->user();

    expect($user)
        ->getId()->toBe('42')
        ->getName()->toBe('Wim Zebra')
        ->getEmail()->toBe('wim@wotz.be')
        ->and($user->getRaw())->toHaveKey('is_active', true);

    Http::assertSent(fn ($request) => $request->url() === 'https://zenith.test/oauth/token' &&
        $request['grant_type'] === 'authorization_code' &&
        $request['code'] === 'auth-code' &&
        $request['client_secret'] === 'zenith-secret');

    Http::assertSent(fn ($request) => $request->url() === 'https://zenith.test/api/user/current' &&
        $request->hasHeader('Authorization', 'Bearer zenith-access-token') &&
        $request->hasHeader('Accept', 'application/json'));
});

it('throws with the status code when zenith refuses a deactivated user', function () {
    fakeZenith(userStatus: 403);

    zenithDriver()->user();
})->throws(RequestException::class, 'Your account has been deactivated.');
