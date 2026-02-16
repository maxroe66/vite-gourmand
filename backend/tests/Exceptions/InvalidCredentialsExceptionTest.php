<?php

namespace Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use App\Exceptions\InvalidCredentialsException;

class InvalidCredentialsExceptionTest extends TestCase
{
    public function test_invalidCredentials_factory_creates_exception(): void
    {
        // Arrange & Act
        $exception = InvalidCredentialsException::invalidCredentials();

        // Assert
        $this->assertInstanceOf(InvalidCredentialsException::class, $exception);
    }

    public function test_invalidCredentials_has_correct_message(): void
    {
        // Arrange & Act
        $exception = InvalidCredentialsException::invalidCredentials();

        // Assert
        $this->assertEquals('Email ou mot de passe incorrect.', $exception->getMessage());
    }

    public function test_invalidCredentials_has_correct_code(): void
    {
        // Arrange & Act
        $exception = InvalidCredentialsException::invalidCredentials();

        // Assert
        $this->assertEquals(3001, $exception->getCode());
    }

    public function test_invalidCredentials_is_instance_of_exception(): void
    {
        // Arrange & Act
        $exception = InvalidCredentialsException::invalidCredentials();

        // Assert
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_invalidCredentials_has_correct_type(): void
    {
        // Arrange & Act
        $exception = InvalidCredentialsException::invalidCredentials();

        // Assert
        $this->assertEquals('INVALID_CREDENTIALS', $exception->getType());
    }
}