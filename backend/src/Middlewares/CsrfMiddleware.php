<?php

namespace App\Middlewares;

use App\Core\Request;
use App\Exceptions\ForbiddenException;
use App\Services\CsrfService;

class CsrfMiddleware
{
    private CsrfService $csrfService;

    public function __construct(CsrfService $csrfService)
    {
        $this->csrfService = $csrfService;
    }

    public function handle(Request $request, array $args = []): void
    {
        $method = strtoupper($request->getMethod());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $cookieToken = $this->csrfService->getTokenFromCookie();
        $headerToken = $this->getHeaderToken();

        if (!$cookieToken || !$headerToken || !hash_equals((string)$cookieToken, (string)$headerToken)) {
            throw new ForbiddenException('CSRF token invalide.');
        }
    }

    private function getHeaderToken(): ?string
    {
        $headerName = $this->csrfService->getHeaderName();
        $headerKey = strtolower($headerName);

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strtolower($key) === $headerKey) {
                    return $value;
                }
            }
        }

        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
        if (!empty($_SERVER[$serverKey])) {
            return $_SERVER[$serverKey];
        }

        return null;
    }
}
