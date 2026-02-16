<?php

namespace Tests\Middlewares;

use PHPUnit\Framework\TestCase;
use App\Middlewares\SecurityHeadersMiddleware;

/**
 * Tests unitaires pour SecurityHeadersMiddleware (CSP).
 *
 * La logique pure est dans buildPolicy() — testable sans headers HTTP.
 */
class SecurityHeadersMiddlewareTest extends TestCase
{
    /**
     * Config CSP par défaut (identique à config.php).
     */
    private function defaultConfig(): array
    {
        return [
            'csp' => [
                'default_src' => ["'self'"],
                'script_src'  => ["'self'", 'https://cdn.jsdelivr.net'],
                'style_src'   => ["'self'", 'https://cdnjs.cloudflare.com'],
                'img_src'     => ["'self'", 'data:'],
                'font_src'    => ["'self'", 'https://cdnjs.cloudflare.com'],
                'connect_src' => ["'self'"],
                'frame_src'   => ["'none'"],
                'object_src'  => ["'none'"],
                'base_uri'    => ["'self'"],
                'form_action' => ["'self'"],
            ],
        ];
    }

    // ──────────────────────────────────────────────
    // Politique par défaut
    // ──────────────────────────────────────────────

    public function testDefaultPolicyContainsAllDirectives(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("script-src 'self' https://cdn.jsdelivr.net", $policy);
        $this->assertStringContainsString("style-src 'self' https://cdnjs.cloudflare.com", $policy);
        $this->assertStringContainsString("img-src 'self' data:", $policy);
        $this->assertStringContainsString("font-src 'self' https://cdnjs.cloudflare.com", $policy);
        $this->assertStringContainsString("connect-src 'self'", $policy);
        $this->assertStringContainsString("frame-src 'none'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("base-uri 'self'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
    }

    public function testDefaultPolicyDirectivesAreSemicolonSeparated(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        $directives = explode('; ', $policy);
        $this->assertCount(10, $directives, 'Il doit y avoir 10 directives CSP');
    }

    // ──────────────────────────────────────────────
    // Sécurité : pas de unsafe-eval ni unsafe-inline dans script-src
    // ──────────────────────────────────────────────

    public function testNoUnsafeEvalInScriptSrc(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        // Extraire la directive script-src
        preg_match('/script-src ([^;]+)/', $policy, $matches);
        $scriptSrc = $matches[1] ?? '';

        $this->assertStringNotContainsString("'unsafe-eval'", $scriptSrc);
    }

    public function testNoUnsafeInlineInScriptSrc(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        preg_match('/script-src ([^;]+)/', $policy, $matches);
        $scriptSrc = $matches[1] ?? '';

        $this->assertStringNotContainsString("'unsafe-inline'", $scriptSrc);
    }

    public function testNoUnsafeInlineInStyleSrc(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        preg_match('/style-src ([^;]+)/', $policy, $matches);
        $styleSrc = $matches[1] ?? '';

        // unsafe-inline a été retiré : tous les inline styles ont été migrés vers des classes CSS
        $this->assertStringNotContainsString("'unsafe-inline'", $styleSrc);
    }

    // ──────────────────────────────────────────────
    // Sécurité : blocage des frames et objets
    // ──────────────────────────────────────────────

    public function testFrameSrcIsNone(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        $this->assertStringContainsString("frame-src 'none'", $policy);
    }

    public function testObjectSrcIsNone(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        $this->assertStringContainsString("object-src 'none'", $policy);
    }

    // ──────────────────────────────────────────────
    // Surcharge de configuration
    // ──────────────────────────────────────────────

    public function testCustomScriptSrcOverridesDefault(): void
    {
        $config = $this->defaultConfig();
        $config['csp']['script_src'] = ["'self'", 'https://example.com'];

        $middleware = new SecurityHeadersMiddleware($config);
        $policy = $middleware->buildPolicy();

        $this->assertStringContainsString("script-src 'self' https://example.com", $policy);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $policy);
    }

    public function testCustomConnectSrcAllowsExternalApi(): void
    {
        $config = $this->defaultConfig();
        $config['csp']['connect_src'] = ["'self'", 'https://api.external.com'];

        $middleware = new SecurityHeadersMiddleware($config);
        $policy = $middleware->buildPolicy();

        $this->assertStringContainsString("connect-src 'self' https://api.external.com", $policy);
    }

    // ──────────────────────────────────────────────
    // Config vide / absente
    // ──────────────────────────────────────────────

    public function testEmptyCspConfigUsesDefaults(): void
    {
        $middleware = new SecurityHeadersMiddleware([]);
        $policy = $middleware->buildPolicy();

        // Sans config, buildPolicy() utilise les fallbacks codés en dur
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("script-src 'self' https://cdn.jsdelivr.net", $policy);
    }

    public function testNullCspConfigUsesDefaults(): void
    {
        $middleware = new SecurityHeadersMiddleware(['csp' => null]);
        $policy = $middleware->buildPolicy();

        $this->assertStringContainsString("default-src 'self'", $policy);
    }

    // ──────────────────────────────────────────────
    // Directives vides sont omises
    // ──────────────────────────────────────────────

    public function testEmptyDirectiveIsOmitted(): void
    {
        $config = $this->defaultConfig();
        $config['csp']['frame_src'] = [];

        $middleware = new SecurityHeadersMiddleware($config);
        $policy = $middleware->buildPolicy();

        $this->assertStringNotContainsString('frame-src', $policy);
    }

    // ──────────────────────────────────────────────
    // CDN autorisés
    // ──────────────────────────────────────────────

    public function testChartJsCdnAllowedInScriptSrc(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        $this->assertStringContainsString('https://cdn.jsdelivr.net', $policy);
    }

    public function testFontAwesomeCdnAllowedInStyleSrc(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        preg_match('/style-src ([^;]+)/', $policy, $matches);
        $styleSrc = $matches[1] ?? '';

        $this->assertStringContainsString('https://cdnjs.cloudflare.com', $styleSrc);
    }

    public function testFontAwesomeCdnAllowedInFontSrc(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        preg_match('/font-src ([^;]+)/', $policy, $matches);
        $fontSrc = $matches[1] ?? '';

        $this->assertStringContainsString('https://cdnjs.cloudflare.com', $fontSrc);
    }

    // ──────────────────────────────────────────────
    // Format de sortie
    // ──────────────────────────────────────────────

    public function testPolicyIsValidCspFormat(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        // Chaque directive doit être séparée par "; "
        $this->assertDoesNotMatchRegularExpression('/;;/', $policy, 'Pas de double point-virgule');
        $this->assertDoesNotMatchRegularExpression('/^\s*;/', $policy, 'Pas de point-virgule initial');
        $this->assertDoesNotMatchRegularExpression('/;\s*$/', $policy, 'Pas de point-virgule final');
    }

    public function testBaseUriPreventsBaseTagHijacking(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        $this->assertStringContainsString("base-uri 'self'", $policy);
    }

    public function testFormActionRestrictedToSelf(): void
    {
        $middleware = new SecurityHeadersMiddleware($this->defaultConfig());
        $policy = $middleware->buildPolicy();

        $this->assertStringContainsString("form-action 'self'", $policy);
    }
}
