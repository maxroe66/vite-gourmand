<?php

namespace Tests\Services;

use App\Exceptions\UserServiceException;
use PHPUnit\Framework\TestCase;

class UserServiceExceptionTest extends TestCase
{
    public function testEmailExistsException(): void
    {
        $exception = UserServiceException::emailExists();

        $this->assertInstanceOf(UserServiceException::class, $exception);
        $this->assertEquals("Cet email est déjà utilisé.", $exception->getMessage());
        $this->assertEquals(UserServiceException::EMAIL_EXISTS, $exception->getCode());
        $this->assertEquals(1001, $exception->getCode());
    }

    public function testDbErrorException(): void
    {
        $exception = UserServiceException::dbError();

        $this->assertInstanceOf(UserServiceException::class, $exception);
        $this->assertEquals("Erreur technique lors de la création de l'utilisateur.", $exception->getMessage());
        $this->assertEquals(UserServiceException::DB_ERROR, $exception->getCode());
        $this->assertEquals(1002, $exception->getCode());
    }

    public function testDbErrorExceptionWithCustomMessage(): void
    {
        $customMessage = "Erreur personnalisée de la base de données";
        $exception = UserServiceException::dbError($customMessage);

        $this->assertInstanceOf(UserServiceException::class, $exception);
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(UserServiceException::DB_ERROR, $exception->getCode());
    }

    public function testExceptionCodesAreUnique(): void
    {
        $codes = [
            UserServiceException::EMAIL_EXISTS,
            UserServiceException::DB_ERROR
        ];

        $uniqueCodes = array_unique($codes);
        $this->assertCount(2, $uniqueCodes, "Les codes d'erreur doivent être uniques");
    }

    public function testExceptionExtendsBaseException(): void
    {
        $exception = UserServiceException::emailExists();
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionCodesAreDifferentFromAuthException(): void
    {
        // Vérifier que les codes UserService (1001-1002) sont différents des codes Auth (2001-2003)
        $this->assertNotEquals(2001, UserServiceException::EMAIL_EXISTS);
        $this->assertNotEquals(2002, UserServiceException::EMAIL_EXISTS);
        $this->assertNotEquals(2003, UserServiceException::EMAIL_EXISTS);
        $this->assertNotEquals(2001, UserServiceException::DB_ERROR);
        $this->assertNotEquals(2002, UserServiceException::DB_ERROR);
        $this->assertNotEquals(2003, UserServiceException::DB_ERROR);
    }
}
