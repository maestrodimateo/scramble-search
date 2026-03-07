<?php

namespace Maestrodimateo\ScrambleSearch;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Support\ServiceProvider;

class ScrambleSearchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Prepend our view path after all providers have booted so it takes
        // priority over Scramble's default view, while still being overridable
        // by a published view in resources/views/vendor/scramble/.
        $this->app->booted(function (): void {
            $this->app['view']->prependNamespace('scramble', realpath(__DIR__.'/../resources/views'));
        });

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            usort($openApi->tags, fn ($a, $b) => strcmp($a->name, $b->name));
        });
    }
}
