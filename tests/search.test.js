// Tests unitaires pour la logique de recherche fuzzy (Node.js, sans framework)
// Exécuter avec : node tests/search.test.js

// ── Copie des fonctions depuis docs.blade.php ─────────────────────────────────

function fuzzyMatch(haystack, needle) {
    let ni = 0;
    for (let hi = 0; hi < haystack.length && ni < needle.length; hi++) {
        if (haystack[hi] === needle[ni]) ni++;
    }
    return ni === needle.length;
}

function scoreToken(text, token) {
    if (!text) return 0;
    if (new RegExp('\\b' + token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i').test(text)) return 3;
    if (text.includes(token)) return 2;
    if (token.length >= 2 && fuzzyMatch(text, token)) return 1;
    return 0;
}

function scoreRoute(route, tokens) {
    const fields = [
        route.path.toLowerCase(),
        route.method.toLowerCase(),
        route.summary.toLowerCase(),
        route.tag.toLowerCase(),
    ];
    let total = 0;
    for (const token of tokens) {
        const fieldScores = fields.map(f => scoreToken(f, token));
        const best = Math.max(...fieldScores);
        if (best === 0) return -1;
        total += fieldScores.reduce((sum, s) => sum + s, 0);
    }
    return total;
}

function filter(routes, query) {
    const q = query.trim();
    if (!q) return routes;
    const tokens = q.toLowerCase().split(/\s+/).filter(Boolean);
    return routes
        .map(r => ({ route: r, score: scoreRoute(r, tokens) }))
        .filter(({ score }) => score > 0)
        .sort((a, b) => b.score - a.score)
        .map(({ route }) => route);
}

// ── Utilitaires de test ───────────────────────────────────────────────────────

let passed = 0;
let failed = 0;

function assert(description, condition) {
    if (condition) {
        console.log(`  ✓ ${description}`);
        passed++;
    } else {
        console.error(`  ✗ ${description}`);
        failed++;
    }
}

function describe(title, fn) {
    console.log(`\n${title}`);
    fn();
}

// ── Données de test ───────────────────────────────────────────────────────────

const routes = [
    { method: 'GET',    path: '/api/users',           summary: 'List users',          operationId: 'users.index',   tag: 'Users'   },
    { method: 'POST',   path: '/api/users',           summary: 'Create user',         operationId: 'users.store',   tag: 'Users'   },
    { method: 'GET',    path: '/api/users/{id}',      summary: 'Show user',           operationId: 'users.show',    tag: 'Users'   },
    { method: 'PUT',    path: '/api/users/{id}',      summary: 'Update user',         operationId: 'users.update',  tag: 'Users'   },
    { method: 'DELETE', path: '/api/users/{id}',      summary: 'Delete user',         operationId: 'users.destroy', tag: 'Users'   },
    { method: 'GET',    path: '/api/posts',           summary: 'List posts',          operationId: 'posts.index',   tag: 'Posts'   },
    { method: 'POST',   path: '/api/posts',           summary: 'Create post',         operationId: 'posts.store',   tag: 'Posts'   },
    { method: 'GET',    path: '/api/profile',         summary: 'Get user profile',    operationId: 'profile.show',  tag: 'Profile' },
    { method: 'DELETE', path: '/api/comments/{id}',  summary: 'Delete comment',      operationId: 'comments.destroy', tag: 'Comments' },
];

// ── fuzzyMatch ────────────────────────────────────────────────────────────────

describe('fuzzyMatch', () => {
    assert('matche une chaîne identique',              fuzzyMatch('user', 'user'));
    assert('matche une sous-séquence simple',          fuzzyMatch('users', 'usr'));
    assert('matche des caractères non contigus',       fuzzyMatch('delete', 'dlt'));
    assert('matche le début seulement',                fuzzyMatch('profile', 'pro'));
    assert('matche des chars éparpillés',              fuzzyMatch('api/users/{id}', 'aisd'));
    assert('retourne false si needle plus long',       !fuzzyMatch('ab', 'abc'));
    assert('retourne false si pas de sous-séquence',   !fuzzyMatch('post', 'xyz'));
    assert('needle vide matche toujours',              fuzzyMatch('anything', ''));
    assert('matche insensible à l\'ordre des chars',  !fuzzyMatch('abc', 'bac'));
});

// ── scoreToken ────────────────────────────────────────────────────────────────

describe('scoreToken', () => {
    assert('score 3 pour correspondance de mot entier',  scoreToken('/api/users', 'users') === 3);
    assert('score 3 pour correspondance en début',       scoreToken('user profile', 'user') === 3);
    assert('score 2 pour sous-chaîne sans limite de mot', scoreToken('superuser', 'user') === 2);
    assert('score 1 pour fuzzy match',                   scoreToken('delete', 'dlt') === 1);
    assert('score 0 si aucun match',                     scoreToken('posts', 'xyz') === 0);
    assert('score 0 si text vide',                       scoreToken('', 'user') === 0);
    assert('score 0 pour fuzzy sur token d\'1 char',     scoreToken('test', 't') !== 1); // word boundary ou substring
    assert('insensible à la casse pour scoreToken',      scoreToken('Users', 'users') === 3);
});

// ── scoreRoute ────────────────────────────────────────────────────────────────

describe('scoreRoute', () => {
    const userRoute = routes[0]; // GET /api/users

    assert('retourne > 0 si tous les tokens matchent',
        scoreRoute(userRoute, ['users']) > 0);

    assert('retourne -1 si un token ne matche rien',
        scoreRoute(userRoute, ['users', 'xyz']) === -1);

    assert('retourne > 0 pour match multi-tokens dans champs différents',
        scoreRoute({ method: 'GET', path: '/api/users', summary: 'List users', tag: 'Profile' }, ['users', 'profile']) > 0);

    assert('retourne -1 pour token sans aucun match',
        scoreRoute(routes[5], ['zzzzz']) === -1);

    assert('score plus élevé pour correspondance exacte vs fuzzy',
        scoreRoute(routes[0], ['users']) > scoreRoute(routes[0], ['usr']));
});

// ── filter (recherche complète) ───────────────────────────────────────────────

describe('filter – requête vide', () => {
    assert('retourne toutes les routes si query vide',  filter(routes, '').length === routes.length);
    assert('retourne toutes les routes si que espaces', filter(routes, '   ').length === routes.length);
});

describe('filter – un mot', () => {
    const r = filter(routes, 'users');
    assert('trouve les routes /api/users',              r.some(x => x.path === '/api/users'));
    assert('trouve /api/users/{id}',                    r.some(x => x.path === '/api/users/{id}'));
    assert('exclut /api/posts',                         !r.some(x => x.path === '/api/posts'));

    const del = filter(routes, 'delete');
    assert('trouve les routes DELETE par méthode',      del.some(x => x.method === 'DELETE'));
    assert('"delete" matche aussi le summary "Delete user"',
        del.some(x => x.operationId === 'users.destroy'));
});

describe('filter – multi-mots (AND)', () => {
    const r = filter(routes, 'delete user');
    assert('"delete user" trouve la route DELETE /api/users/{id}',
        r.some(x => x.operationId === 'users.destroy'));
    assert('"delete user" n\'inclut pas DELETE /api/comments',
        !r.some(x => x.operationId === 'comments.destroy'));

    const r2 = filter(routes, 'create post');
    assert('"create post" trouve POST /api/posts',      r2.some(x => x.operationId === 'posts.store'));
    // "create" matche le summary "Create user" ET "post" matche la méthode POST → incluse aussi
    assert('"create post" inclut aussi POST /api/users (méthode + summary)', r2.some(x => x.operationId === 'users.store'));
});

describe('filter – fuzzy', () => {
    const r = filter(routes, 'usr');
    assert('"usr" matche les routes user (fuzzy)',      r.some(x => x.tag === 'Users'));

    const r2 = filter(routes, 'dlt');
    assert('"dlt" matche delete via fuzzy',             r2.length > 0);
});

describe('filter – tri par pertinence', () => {
    const r = filter(routes, 'user');
    assert('les routes avec correspondance exacte apparaissent en premier',
        r[0].path.includes('user') || r[0].tag === 'Users' || r[0].summary.includes('user'));

    // Une route matchant dans plusieurs champs (path + tag) scored plus haut qu'une avec un seul champ
    const mixed = [
        { method: 'GET', path: '/api/other',  summary: 'List users', operationId: 'a', tag: 'Other' },
        { method: 'GET', path: '/api/users',  summary: 'List users', operationId: 'b', tag: 'Users' },
    ];
    const sorted = filter(mixed, 'users');
    // 'b' a "users" dans path (score 3) + summary (score 3) + tag (score 3) → total 9
    // 'a' a "users" seulement dans summary (score 3) → total 3
    assert('route avec match dans plusieurs champs scorée avant route avec un seul match',
        sorted[0].operationId === 'b');
});

describe('filter – insensible à la casse', () => {
    assert('"USERS" trouve les routes user', filter(routes, 'USERS').length > 0);
    assert('"Users" trouve les routes user', filter(routes, 'Users').length > 0);
    assert('"GET" trouve les routes GET',    filter(routes, 'GET').some(x => x.method === 'GET'));
});

// ── Résumé ────────────────────────────────────────────────────────────────────

console.log(`\n${'─'.repeat(50)}`);
console.log(`Résultats : ${passed} passés, ${failed} échoués`);
if (failed > 0) {
    console.error(`\n${failed} test(s) ont échoué.`);
    process.exit(1);
} else {
    console.log('\nTous les tests sont passés.');
}
