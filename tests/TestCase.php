<?php

namespace Maestrodimateo\ScrambleSearch\Tests;

use Dedoc\Scramble\ScrambleServiceProvider;
use Maestrodimateo\ScrambleSearch\ScrambleSearchServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ScrambleServiceProvider::class,
            ScrambleSearchServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('scramble.api_path', 'api');
        $app['config']->set('scramble.ui.theme', 'light');
        // Remove RestrictedDocsAccess so the docs page is accessible in tests
        $app['config']->set('scramble.middleware', ['web']);
    }
}