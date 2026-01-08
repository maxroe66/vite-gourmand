<?php

use PHPUnit\Framework\TestCase;
use App\Services\UserService;
use App\Repositories\UserRepository;
use App\Exceptions\UserServiceException;
use Psr\Log\LoggerInterface;

class UserServiceTest extends TestCase
{
    public function testCreateUserReturnsUserIdOnSuccess()
    {
        $userData = [
            'email' => 'test@example.com',
            'passwordHash' => 'hashedpassword',
            // ... autres données utilisateur
        ];

        // 1. Créer un mock du UserRepository
        $userRepositoryMock = $this->createMock(UserRepository::class);

        // 2. Définir le comportement attendu du mock
        // On s'attend à ce que findByEmail soit appelé avec le bon email et retourne `false` (l'utilisateur n'existe pas)
        $userRepositoryMock->expects($this->once())
            ->method('findByEmail')
            ->with($this->equalTo('test@example.com'))
            ->willReturn(false);

        // On s'attend à ce que `create` soit appelé avec les données utilisateur et retourne un ID
        $userRepositoryMock->expects($this->once())
            ->method('create')
            ->with($this->equalTo($userData))
            ->willReturn(42);

        // 3. Créer un mock du Logger
        $loggerMock = $this->createMock(LoggerInterface::class);

        // 4. Instancier le service avec les mocks
        $userService = new UserService($userRepositoryMock, $loggerMock);

        // 5. Appeler la méthode à tester et vérifier le résultat
        $userId = $userService->createUser($userData);
        $this->assertEquals(42, $userId);
    }

    public function testCreateUserThrowsExceptionIfEmailExists()
    {
        $userData = [
            'email' => 'exists@example.com',
            'passwordHash' => 'hashedpassword',
            // ... autres données utilisateur
        ];

        // 1. Créer un mock du UserRepository
        $userRepositoryMock = $this->createMock(UserRepository::class);

        // 2. Définir le comportement attendu du mock
        // On s'attend à ce que findByEmail retourne une valeur "vraie" (ex: un tableau)
        $userRepositoryMock->expects($this->once())
            ->method('findByEmail')
            ->with($this->equalTo('exists@example.com'))
            ->willReturn(['id_utilisateur' => 1]); // Simule un utilisateur trouvé

        // On s'attend à ce que la méthode `create` ne soit JAMAIS appelée
        $userRepositoryMock->expects($this->never())
            ->method('create');

        // 3. S'attendre à une exception
        $this->expectException(UserServiceException::class);
        $this->expectExceptionMessage('Cet email est déjà utilisé.');

        // 3. Créer un mock du Logger
        $loggerMock = $this->createMock(LoggerInterface::class);

        // 4. Instancier le service et appeler la méthode
        $userService = new UserService($userRepositoryMock, $loggerMock);
        $userService->createUser($userData);
    }
}
