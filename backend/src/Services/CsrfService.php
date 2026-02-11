<?php

namespace App\Services;

class CsrfService
{
    private array $config;
    private string $cookieName;
    private string $headerName;
    private int $tokenBytes;
    private int $ttl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $csrf = $config['csrf'] ?? [];

        $this->cookieName = $csrf['cookie_name'] ?? 'csrfToken';
        $this->headerName = $csrf['header_name'] ?? 'X-CSRF-Token';
        $this->tokenBytes = (int)($csrf['token_bytes'] ?? 32);
        $this->ttl = (int)($csrf['ttl'] ?? 7200);
    }

    public function getCookieName(): string
    {
        return $this->cookieName;
    }

    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    public function getTokenFromCookie(): ?string
    {
        return $_COOKIE[$this->cookieName] ?? null;
    }

    public function ensureTokenCookie(): string
    {
        $token = $this->getTokenFromCookie();
        if (!$token) {
            $token = $this->generateToken();
            $this->setTokenCookie($token);
        }
        return $token;
    }

    public function rotateToken(): string
    {
        $token = $this->generateToken();
        $this->setTokenCookie($token);
        return $token;
    }

    public function clearTokenCookie(): void
    {
        $this->setTokenCookie('', time() - 3600);
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes($this->tokenBytes));
    }

    private function setTokenCookie(string $token, ?int $expires = null): void
    {
        $expires = $expires ?? (time() + $this->ttl);
        $options = $this->buildCookieOptions($expires);
        setcookie($this->cookieName, $token, $options);
    }

    private function buildCookieOptions(int $expires): array
    {
        $isSecure = $this->isSecureRequest();
        $sameSite = $isSecure ? 'None' : 'Lax';

        $options = [
            'expires' => $expires,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => false,
            'samesite' => $sameSite,
        ];

        $domain = $this->resolveCookieDomain();
        if ($domain !== null) {
            $options['domain'] = $domain;
        }

        return $options;
    }

    private function isSecureRequest(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
            !empty($_SERVER['HTTP_X_ARR_SSL']) ||
            (!empty($_SERVER['HTTP_X_ARR_PROTO']) && strtolower($_SERVER['HTTP_X_ARR_PROTO']) === 'https')
        );
    }

    private function resolveCookieDomain(): ?string
    {
        $configured = $this->config['cookie_domain'] ?? null;
        if (!empty($configured)) {
            return '.' . ltrim($configured, '.');
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === '') {
            return null;
        }

        $baseHost = preg_replace('/:\\d+$/', '', $host);
        if (stripos($baseHost, 'www.') === 0) {
            $baseHost = substr($baseHost, 4);
        }

        return '.' . $baseHost;
    }
}
