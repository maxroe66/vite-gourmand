<?php

use PHPUnit\Framework\TestCase;
use App\Middlewares\RateLimitMiddleware;
use App\Exceptions\TooManyRequestsException;
use Psr\Log\NullLogger;

/**
 * Tests unitaires pour RateLimitMiddleware::check().
 *
 * La méthode check() isole la logique de rate limiting (fenêtre glissante)
 * sans effets de bord HTTP, avec un timestamp injectable pour la reproductibilité.
 */
class RateLimitMiddlewareTest extends TestCase
{
    private RateLimitMiddleware $middleware;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->middleware = new RateLimitMiddleware(new NullLogger());
        $this->tempDir = sys_get_temp_dir() . '/vg_rate_limit_test_' . uniqid();
        mkdir($this->tempDir, 0700, true);
        $this->middleware->setStorageDir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Nettoyage des fichiers temporaires
        $files = glob($this->tempDir . '/*');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        @rmdir($this->tempDir);
    }

    // ─── Cas nominal ─────────────────────────────────────────────

    public function testFirstRequestIsAllowed(): void
    {
        $result = $this->middleware->check('192.168.1.1', 'login', 5, 900, 1000.0);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(4, $result['remaining']);
        $this->assertEquals(0, $result['retryAfter']);
    }

    public function testMultipleRequestsWithinLimitAreAllowed(): void
    {
        $now = 1000.0;
        for ($i = 0; $i < 4; $i++) {
            $result = $this->middleware->check('192.168.1.1', 'login', 5, 900, $now + $i);
        }

        $this->assertTrue($result['allowed']);
        $this->assertEquals(1, $result['remaining']);
    }

    // ─── Limite atteinte ─────────────────────────────────────────

    public function testRequestBlockedWhenLimitReached(): void
    {
        $now = 1000.0;
        // Remplir 5 slots
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->check('192.168.1.1', 'login', 5, 900, $now + $i);
        }

        // La 6ème doit être bloquée
        $result = $this->middleware->check('192.168.1.1', 'login', 5, 900, $now + 5);

        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertGreaterThan(0, $result['retryAfter']);
    }

    public function testRetryAfterIsCorrect(): void
    {
        $now = 1000.0;
        $window = 900; // 15 minutes

        // Envoyer 5 requêtes à t=1000
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->check('10.0.0.1', 'login', 5, $window, $now);
        }

        // La 6ème à t=1010 (10s plus tard)
        $result = $this->middleware->check('10.0.0.1', 'login', 5, $window, $now + 10);

        $this->assertFalse($result['allowed']);
        // La plus ancienne expire à 1000 + 900 = 1900
        // retryAfter = 1900 - 1010 = 890
        $this->assertEquals(890, $result['retryAfter']);
    }

    // ─── Fenêtre glissante (expiration) ──────────────────────────

    public function testRequestAllowedAfterWindowExpires(): void
    {
        $now = 1000.0;
        $window = 60; // 1 minute

        // Remplir 5 slots à t=1000
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->check('10.0.0.2', 'login', 5, $window, $now);
        }

        // Bloqué à t=1030 (dans la fenêtre)
        $blocked = $this->middleware->check('10.0.0.2', 'login', 5, $window, $now + 30);
        $this->assertFalse($blocked['allowed']);

        // Autorisé à t=1061 (fenêtre expirée)
        $allowed = $this->middleware->check('10.0.0.2', 'login', 5, $window, $now + 61);
        $this->assertTrue($allowed['allowed']);
    }

    public function testSlidingWindowPartialExpiry(): void
    {
        $window = 60;

        // 3 requêtes à t=100
        for ($i = 0; $i < 3; $i++) {
            $this->middleware->check('10.0.0.3', 'api', 5, $window, 100.0);
        }

        // 2 requêtes à t=140
        for ($i = 0; $i < 2; $i++) {
            $this->middleware->check('10.0.0.3', 'api', 5, $window, 140.0);
        }

        // À t=161 : les 3 premières (t=100) expirent, reste 2 (t=140)
        $result = $this->middleware->check('10.0.0.3', 'api', 5, $window, 161.0);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(2, $result['remaining']); // 5 - 2 actives - 1 nouvelle = 2
    }

    // ─── Isolation par prefix ────────────────────────────────────

    public function testDifferentPrefixesAreIsolated(): void
    {
        $now = 1000.0;

        // Remplir le compteur 'login'
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->check('10.0.0.4', 'login', 5, 900, $now);
        }

        // Le compteur 'register' doit être vide
        $result = $this->middleware->check('10.0.0.4', 'register', 5, 900, $now);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(4, $result['remaining']);
    }

    // ─── Isolation par IP ────────────────────────────────────────

    public function testDifferentIpsAreIsolated(): void
    {
        $now = 1000.0;

        // IP A : 5 requêtes (plein)
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->check('10.0.0.10', 'login', 5, 900, $now);
        }

        // IP B : doit passer
        $result = $this->middleware->check('10.0.0.11', 'login', 5, 900, $now);
        $this->assertTrue($result['allowed']);
    }

    // ─── Reset ───────────────────────────────────────────────────

    public function testResetClearsCounter(): void
    {
        $now = 1000.0;

        // Remplir
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->check('10.0.0.5', 'login', 5, 900, $now);
        }

        // Bloqué
        $blocked = $this->middleware->check('10.0.0.5', 'login', 5, 900, $now);
        $this->assertFalse($blocked['allowed']);

        // Reset
        $this->middleware->reset('10.0.0.5', 'login');

        // Autorisé
        $allowed = $this->middleware->check('10.0.0.5', 'login', 5, 900, $now);
        $this->assertTrue($allowed['allowed']);
        $this->assertEquals(4, $allowed['remaining']);
    }

    // ─── TooManyRequestsException ────────────────────────────────

    public function testExceptionHasRetryAfter(): void
    {
        $exception = new TooManyRequestsException(890);

        $this->assertEquals(429, $exception->getCode());
        $this->assertEquals(890, $exception->getRetryAfter());
        $this->assertStringContainsString('Trop de requêtes', $exception->getMessage());
    }

    public function testExceptionCustomMessage(): void
    {
        $exception = new TooManyRequestsException(60, 'Limite atteinte pour le login.');

        $this->assertEquals(60, $exception->getRetryAfter());
        $this->assertEquals('Limite atteinte pour le login.', $exception->getMessage());
    }

    // ─── Limites de configuration ────────────────────────────────

    public function testSingleRequestLimit(): void
    {
        $now = 1000.0;

        $first = $this->middleware->check('10.0.0.6', 'strict', 1, 60, $now);
        $this->assertTrue($first['allowed']);
        $this->assertEquals(0, $first['remaining']);

        $second = $this->middleware->check('10.0.0.6', 'strict', 1, 60, $now + 1);
        $this->assertFalse($second['allowed']);
    }

    public function testHighLimitAllowsMany(): void
    {
        $now = 1000.0;

        for ($i = 0; $i < 100; $i++) {
            $result = $this->middleware->check('10.0.0.7', 'bulk', 100, 60, $now + $i * 0.1);
            $this->assertTrue($result['allowed']);
        }

        // La 101ème doit être bloquée
        $blocked = $this->middleware->check('10.0.0.7', 'bulk', 100, 60, $now + 10);
        $this->assertFalse($blocked['allowed']);
    }

    // ─── Minimum retryAfter ──────────────────────────────────────

    public function testRetryAfterIsAtLeastOne(): void
    {
        $now = 1000.0;

        for ($i = 0; $i < 3; $i++) {
            $this->middleware->check('10.0.0.8', 'test', 3, 1, $now);
        }

        // Bloqué juste après
        $result = $this->middleware->check('10.0.0.8', 'test', 3, 1, $now + 0.5);
        $this->assertFalse($result['allowed']);
        $this->assertGreaterThanOrEqual(1, $result['retryAfter']);
    }
}
