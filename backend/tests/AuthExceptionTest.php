<?php

namespace Tests;

use App\Exceptions\AuthException;
use PHPUnit\Framework\TestCase;

class AuthExceptionTest extends TestCase
{
    public function testTokenMissingException(): void
    {
        $exception = AuthException::tokenMissing();

        $this->assertInstanceOf(AuthException::class, $exception);
        $this->assertEquals("Token d'authentification manquant.", $exception->getMessage());
        $this->assertEquals(AuthException::TOKEN_MISSING, $exception->getCode());
        $this->assertEquals(2001, $exception->getCode());
    }

    public function testTokenInvalidException(): void
    {
        $exception = AuthException::tokenInvalid();

        $this->assertInstanceOf(AuthException::class, $exception);
        $this->assertEquals("Token invalide ou expiré.", $exception->getMessage());
        $this->assertEquals(AuthException::TOKEN_INVALID, $exception->getCode());
        $this->assertEquals(2002, $exception->getCode());
    }

    public function testConfigErrorException(): void
    {
        $exception = AuthException::configError();

        $this->assertInstanceOf(AuthException::class, $exception);
        $this->assertEquals("Erreur de configuration du serveur.", $exception->getMessage());
        $this->assertEquals(AuthException::CONFIG_ERROR, $exception->getCode());
        $this->assertEquals(2003, $exception->getCode());
    }

    public function testExceptionCodesAreUnique(): void
    {
        $codes = [
            AuthException::TOKEN_MISSING,
            AuthException::TOKEN_INVALID,
            AuthException::CONFIG_ERROR
        ];

        $uniqueCodes = array_unique($codes);
        $this->assertCount(3, $uniqueCodes, "Les codes d'erreur doivent être uniques");
    }

    public function testExceptionExtendsBaseException(): void
    {
        $exception = AuthException::tokenMissing();
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
