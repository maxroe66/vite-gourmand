<?php

namespace Tests\Middlewares;

use PHPUnit\Framework\TestCase;
use App\Middlewares\CorsMiddleware;
use Psr\Log\NullLogger;

/**
 * Tests unitaires pour CorsMiddleware::process().
 *
 * La méthode process() isole la logique CORS pure (sans header()/exit),
 * ce qui permet des tests déterministes et rapides.
 */
class CorsMiddlewareTest extends TestCase
{
    // ─── Helpers ─────────────────────────────────────────────────

    private function makeMiddleware(array $corsConfig): CorsMiddleware
    {
        return new CorsMiddleware(
            ['cors' => $corsConfig],
            new NullLogger()
        );
    }

    private function defaultConfig(array $overrides = []): array
    {
        return array_merge([
            'allowed_origins'  => ['https://vite-et-gourmand.me'],
            'allowed_methods'  => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers'  => ['Content-Type', 'Authorization', 'X-CSRF-Token'],
            'allow_credentials' => true,
            'max_age'          => 86400,
        ], $overrides);
    }

    /**
     * Vérifie qu'un header exact est présent dans le résultat.
     */
    private function assertHeaderPresent(string $expected, array $result, string $message = ''): void
    {
        $this->assertContains($expected, $result['headers'], $message ?: "Header attendu : {$expected}");
    }

    /**
     * Vérifie qu'aucun header contenant $substring n'est présent.
     */
    private function assertNoHeaderContaining(string $substring, array $result): void
    {
        foreach ($result['headers'] as $header) {
            $this->assertStringNotContainsString($substring, $header,
                "Aucun header ne devrait contenir '{$substring}', trouvé : {$header}");
        }
    }

    // ─── Origine autorisée ───────────────────────────────────────

    public function testAllowedOriginReturnsCorrectHeaders(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig());
        $result = $mw->process('https://vite-et-gourmand.me', 'GET');

        $this->assertHeaderPresent('Access-Control-Allow-Origin: https://vite-et-gourmand.me', $result);
        $this->assertHeaderPresent('Vary: Origin', $result);
        $this->assertHeaderPresent('Access-Control-Allow-Credentials: true', $result);
        $this->assertHeaderPresent('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS', $result);
        $this->assertHeaderPresent('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token', $result);
        $this->assertHeaderPresent('Access-Control-Max-Age: 86400', $result);
        $this->assertNull($result['status_code']);
        $this->assertFalse($result['terminate']);
    }

    // ─── Origine refusée ─────────────────────────────────────────

    public function testUnauthorizedOriginReturnsNoHeaders(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig());
        $result = $mw->process('https://evil.com', 'GET');

        $this->assertEmpty($result['headers']);
        $this->assertNull($result['status_code']);
        $this->assertFalse($result['terminate']);
    }

    public function testUnauthorizedOriginOnOptionsReturns403(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig());
        $result = $mw->process('https://evil.com', 'OPTIONS');

        $this->assertEmpty($result['headers']);
        $this->assertEquals(403, $result['status_code']);
        $this->assertTrue($result['terminate']);
    }

    // ─── Origine vide (requête same-origin) ──────────────────────

    public function testEmptyOriginIsRejectedSilently(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig());
        $result = $mw->process('', 'GET');

        $this->assertEmpty($result['headers']);
        $this->assertNull($result['status_code']);
        $this->assertFalse($result['terminate']);
    }

    // ─── Preflight OPTIONS ───────────────────────────────────────

    public function testPreflightReturns204AndTerminates(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig());
        $result = $mw->process('https://vite-et-gourmand.me', 'OPTIONS');

        $this->assertHeaderPresent('Access-Control-Allow-Origin: https://vite-et-gourmand.me', $result);
        $this->assertEquals(204, $result['status_code']);
        $this->assertTrue($result['terminate']);
    }

    // ─── Multi-origines ──────────────────────────────────────────

    public function testMultipleAllowedOriginsFirstMatches(): void
    {
        $config = $this->defaultConfig([
            'allowed_origins' => ['https://vite-et-gourmand.me', 'https://staging.vite-et-gourmand.me'],
        ]);
        $mw = $this->makeMiddleware($config);

        $result = $mw->process('https://vite-et-gourmand.me', 'GET');
        $this->assertHeaderPresent('Access-Control-Allow-Origin: https://vite-et-gourmand.me', $result);
    }

    public function testMultipleAllowedOriginsSecondMatches(): void
    {
        $config = $this->defaultConfig([
            'allowed_origins' => ['https://vite-et-gourmand.me', 'https://staging.vite-et-gourmand.me'],
        ]);
        $mw = $this->makeMiddleware($config);

        $result = $mw->process('https://staging.vite-et-gourmand.me', 'GET');
        $this->assertHeaderPresent('Access-Control-Allow-Origin: https://staging.vite-et-gourmand.me', $result);
    }

    public function testMultipleAllowedOriginsRejectsUnlisted(): void
    {
        $config = $this->defaultConfig([
            'allowed_origins' => ['https://vite-et-gourmand.me', 'https://staging.vite-et-gourmand.me'],
        ]);
        $mw = $this->makeMiddleware($config);

        $result = $mw->process('https://other.com', 'GET');
        $this->assertEmpty($result['headers']);
    }

    // ─── Access-Control-Max-Age ──────────────────────────────────

    public function testMaxAgeHeaderPresent(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig(['max_age' => 3600]));
        $result = $mw->process('https://vite-et-gourmand.me', 'GET');

        $this->assertHeaderPresent('Access-Control-Max-Age: 3600', $result);
    }

    public function testMaxAgeZeroNotSent(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig(['max_age' => 0]));
        $result = $mw->process('https://vite-et-gourmand.me', 'GET');

        $this->assertNoHeaderContaining('Max-Age', $result);
    }

    // ─── Wildcard + credentials = interdit ───────────────────────

    public function testWildcardWithCredentialsBlocksGet(): void
    {
        $config = $this->defaultConfig([
            'allowed_origins'  => ['*'],
            'allow_credentials' => true,
        ]);
        $mw = $this->makeMiddleware($config);
        $result = $mw->process('https://vite-et-gourmand.me', 'GET');

        $this->assertEmpty($result['headers'], 'Wildcard + credentials ne doit envoyer aucun header.');
        $this->assertFalse($result['terminate']);
    }

    public function testWildcardWithCredentialsBlocksOptions(): void
    {
        $config = $this->defaultConfig([
            'allowed_origins'  => ['*'],
            'allow_credentials' => true,
        ]);
        $mw = $this->makeMiddleware($config);
        $result = $mw->process('https://vite-et-gourmand.me', 'OPTIONS');

        $this->assertEmpty($result['headers']);
        $this->assertEquals(403, $result['status_code']);
        $this->assertTrue($result['terminate']);
    }

    // ─── Vary: Origin toujours présent ───────────────────────────

    public function testVaryOriginAlwaysPresentOnAllowedOrigin(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig());
        $result = $mw->process('https://vite-et-gourmand.me', 'POST');

        $this->assertHeaderPresent('Vary: Origin', $result);
    }

    public function testVaryOriginAbsentOnRejectedOrigin(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig());
        $result = $mw->process('https://evil.com', 'POST');

        $this->assertEmpty($result['headers'], 'Aucun header CORS sur origine rejetée, y compris Vary.');
    }

    // ─── Credentials désactivés ──────────────────────────────────

    public function testNoCredentialsHeaderWhenDisabled(): void
    {
        $config = $this->defaultConfig(['allow_credentials' => false]);
        $mw = $this->makeMiddleware($config);
        $result = $mw->process('https://vite-et-gourmand.me', 'GET');

        $this->assertNoHeaderContaining('Allow-Credentials', $result);
    }

    // ─── Matching strict (pas de sous-domaine implicite) ─────────

    public function testSubdomainIsNotImplicitlyAllowed(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig());
        $result = $mw->process('https://sub.vite-et-gourmand.me', 'GET');

        $this->assertEmpty($result['headers'], 'Un sous-domaine non listé ne doit pas être accepté.');
    }

    public function testOriginWithDifferentProtocolRejected(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig());
        $result = $mw->process('http://vite-et-gourmand.me', 'GET');

        $this->assertEmpty($result['headers'], 'HTTP ne doit pas matcher HTTPS.');
    }

    public function testOriginWithPortRejected(): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig());
        $result = $mw->process('https://vite-et-gourmand.me:8443', 'GET');

        $this->assertEmpty($result['headers'], 'Une origine avec port non listé doit être rejetée.');
    }

    // ─── Toutes les méthodes HTTP ────────────────────────────────

    /**
     * @dataProvider httpMethodsProvider
     */
    public function testAllHttpMethodsGetCorsHeaders(string $method, bool $shouldTerminate): void
    {
        $mw = $this->makeMiddleware($this->defaultConfig());
        $result = $mw->process('https://vite-et-gourmand.me', $method);

        $this->assertNotEmpty($result['headers']);
        $this->assertHeaderPresent('Access-Control-Allow-Origin: https://vite-et-gourmand.me', $result);
        $this->assertEquals($shouldTerminate, $result['terminate']);
    }

    public static function httpMethodsProvider(): array
    {
        return [
            'GET'     => ['GET', false],
            'POST'    => ['POST', false],
            'PUT'     => ['PUT', false],
            'DELETE'  => ['DELETE', false],
            'OPTIONS' => ['OPTIONS', true],
        ];
    }
}
