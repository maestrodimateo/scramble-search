<?php

use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/api/users', fn () => response()->json([]))->name('users.index');
    Route::post('/api/users', fn () => response()->json([]))->name('users.store');
    Route::delete('/api/users/{id}', fn () => response()->json([]))->name('users.destroy');
});

// ──────────────────────────────────────────────────────────────
// Présence des fonctions de recherche fuzzy dans la page
// ──────────────────────────────────────────────────────────────

it('embeds the fuzzyMatch function', function (): void {
    $html = $this->get('/docs/api')->getContent();

    expect($html)->toContain('function fuzzyMatch(');
});

it('embeds the scoreToken function', function (): void {
    $html = $this->get('/docs/api')->getContent();

    expect($html)->toContain('function scoreToken(');
});

it('embeds the scoreRoute function', function (): void {
    $html = $this->get('/docs/api')->getContent();

    expect($html)->toContain('function scoreRoute(');
});

it('embeds the updated filter function with token splitting', function (): void {
    $html = $this->get('/docs/api')->getContent();

    // La requête est découpée en tokens par split(/\s+/)
    expect($html)->toContain('split(/\s+/)');
});

it('embeds score-based sorting in filter', function (): void {
    $html = $this->get('/docs/api')->getContent();

    // Les résultats sont triés par score décroissant
    expect($html)->toContain('b.score - a.score');
});

it('embeds AND logic between tokens', function (): void {
    $html = $this->get('/docs/api')->getContent();

    // Si un token ne matche rien, la route est exclue (score -1)
    expect($html)->toContain('return -1');
});

it('embeds token-level highlighting', function (): void {
    $html = $this->get('/docs/api')->getContent();

    // Le highlight boucle sur les regexes pré-compilées
    expect($html)->toContain('for (const re of highlightRegexes)');
});

it('embeds subsequence fuzzy logic', function (): void {
    $html = $this->get('/docs/api')->getContent();

    // La boucle de sous-séquence : ni === needle.length
    expect($html)->toContain('ni === needle.length');
});

it('embeds word boundary scoring', function (): void {
    $html = $this->get('/docs/api')->getContent();

    // Correspondance en début de mot reçoit le score le plus élevé
    expect($html)->toContain('\\\\b');
});

it('embeds minimum length guard for fuzzy matching', function (): void {
    $html = $this->get('/docs/api')->getContent();

    // Les tokens d'un seul caractère ne déclenchent pas le fuzzy
    expect($html)->toContain('token.length >= 2');
});

// ──────────────────────────────────────────────────────────────
// Vérification structurelle de la page
// ──────────────────────────────────────────────────────────────

it('still renders the search overlay', function (): void {
    $this->get('/docs/api')->assertSee('id="api-search-overlay"', false);
});

it('still renders the search input', function (): void {
    $this->get('/docs/api')->assertSee('id="api-search-input"', false);
});

it('still renders the keyboard shortcut hint', function (): void {
    $this->get('/docs/api')->assertSee('⌘K');
});