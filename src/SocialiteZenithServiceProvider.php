<?php

namespace Wotz\SocialiteZenith;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\SocialiteManager;

class SocialiteZenithServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->callAfterResolving(Factory::class, function (SocialiteManager $socialite): void {
            $socialite->extend('zenith', function () use ($socialite): ZenithProvider {
                $config = config('services.zenith', []);

                /** @var ZenithProvider $provider */
                $provider = $socialite->buildProvider(ZenithProvider::class, $config);

                return $provider->setBaseUrl($config['base_url'] ?? null);
            });
        });
    }
}
