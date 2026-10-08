# Socialite Zenith

A [Laravel Socialite](https://laravel.com/docs/socialite) provider for Zenith, the WOTZ OAuth2 server.

Zenith is a Laravel Passport server using the authorization code flow. This provider requests no scopes, so users don't see a consent screen.

## Installation

```bash
composer require wotz/socialite-zenith
```

The service provider registers the `zenith` Socialite driver automatically.

Add the Zenith credentials to `config/services.php`:

```php
'zenith' => [
    'base_url' => env('ZENITH_URL'),
    'client_id' => env('ZENITH_CLIENT_ID'),
    'client_secret' => env('ZENITH_CLIENT_SECRET'),
    'redirect' => env('ZENITH_REDIRECT_URI'),
],
```

The client ID and secret come from a confidential client on Zenith. The redirect URI must match the one registered on that client exactly.

## Usage

```php
use Laravel\Socialite\Facades\Socialite;

Route::get('/auth/zenith/redirect', fn () => Socialite::driver('zenith')->redirect());

Route::get('/auth/zenith/callback', function () {
    $zenithUser = Socialite::driver('zenith')->user();

    $zenithUser->getId();    // Zenith user id, as a string
    $zenithUser->getName();  // "firstname lastname"
    $zenithUser->getEmail();
    $zenithUser->getRaw();   // the full `data` object from /api/user/current
});
```

To use it on a Filament panel, combine it with [dutchcodingcompany/filament-socialite](https://github.com/DutchCodingCompany/filament-socialite) and register `Provider::make('zenith')` on the plugin.

### Errors

The provider talks to Zenith through Laravel's HTTP client, so failures throw `Illuminate\Http\Client\RequestException` with the response attached. A user who is deactivated in Zenith gets a **403** from the user endpoint:

```php
use Illuminate\Http\Client\RequestException;

try {
    $zenithUser = Socialite::driver('zenith')->user();
} catch (RequestException $exception) {
    if ($exception->response->forbidden()) {
        // deactivated in Zenith
    }
}
```

### The access token

The access token works on the whole Zenith API for that user. Use it to fetch the user, then throw it away: don't store it, put it in the session or log it.

## Testing

```bash
composer test
```

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
