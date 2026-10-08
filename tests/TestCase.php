<?php

namespace Wotz\SocialiteZenith\Tests;

use Laravel\Socialite\SocialiteServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Wotz\SocialiteZenith\SocialiteZenithServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            SocialiteServiceProvider::class,
            SocialiteZenithServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('services.zenith', [
            'base_url' => 'https://zenith.test/',
            'client_id' => 'zenith-client',
            'client_secret' => 'zenith-secret',
            'redirect' => 'https://app.test/oauth/callback/zenith',
        ]);
    }
}
