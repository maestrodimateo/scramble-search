<?php

use Illuminate\Support\Facades\Route;
use Maestrodimateo\ScrambleSearch\ScrambleSearchServiceProvider;

beforeEach(function (): void {
    // Register a dummy API route so Scramble has at least one path in the spec
    Route::get('/api/ping', fn () => response()->json(['status' => 'ok']))
        ->name('ping');
});

it('registers the service provider', function (): void {
    $loaded = array_keys($this->app->getLoadedProviders());

    expect($loaded)->toContain(ScrambleSearchServiceProvider::class);
});

it('prepends our path to the scramble view namespace', function (): void {
    $finder = $this->app['view']->getFinder();
    $hints  = $finder->getHints();

    expect($hints)->toHaveKey('scramble');

    $firstPath        = realpath($hints['scramble'][0]);
    $packageViewsPath = realpath(__DIR__.'/../resources/views');

    expect($firstPath)->toBe($packageViewsPath);
});

it('resolves scramble::docs from the package views directory', function (): void {
    $resolvedPath     = $this->app['view']->getFinder()->find('scramble::docs');
    $packageViewsPath = realpath(__DIR__.'/../resources/views');

    expect($resolvedPath)->toStartWith($packageViewsPath);
});

it('renders the docs page with a 200 status', function (): void {
    $response = $this->get('/docs/api');

    $response->assertStatus(200);
});

it('renders the search overlay element', function (): void {
    $response = $this->get('/docs/api');

    $response->assertSee('id="api-search-overlay"', false);
});

it('renders the search trigger button', function (): void {
    $response = $this->get('/docs/api');

    $response->assertSee('id="api-search-trigger"', false);
});

it('renders the keyboard shortcut hint', function (): void {
    $response = $this->get('/docs/api');

    $response->assertSee('⌘K');
});

it('embeds the openapi spec in the page', function (): void {
    $response = $this->get('/docs/api');

    $response->assertSee('apiDescriptionDocument');
});