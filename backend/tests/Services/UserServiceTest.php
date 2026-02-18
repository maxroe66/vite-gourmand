<?php

namespace Tests\Services;

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

    // ==========================================
    // TESTS POUR updateProfile()
    // ==========================================

    public function testUpdateProfileReturnsUpdatedUser(): void
    {
        $userId = 42;
        $inputData = [
            'firstName' => 'Marie',
            'lastName' => 'Curie',
            'phone' => '0612345678',
        ];

        $updatedUser = [
            'id' => 42,
            'email' => 'marie@test.com',
            'prenom' => 'Marie',
            'nom' => 'Curie',
            'gsm' => '0612345678',
            'adresse_postale' => null,
            'ville' => null,
            'code_postal' => null,
            'role' => 'UTILISATEUR',
        ];

        $userRepositoryMock = $this->createMock(UserRepository::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        // Doit appeler updateProfile avec les clés mappées
        $userRepositoryMock->expects($this->once())
            ->method('updateProfile')
            ->with($userId, $this->callback(function ($dbData) {
                return $dbData['prenom'] === 'Marie'
                    && $dbData['nom'] === 'Curie'
                    && $dbData['gsm'] === '0612345678';
            }));

        $userRepositoryMock->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($updatedUser);

        $userService = new UserService($userRepositoryMock, $loggerMock);
        $result = $userService->updateProfile($userId, $inputData);

        $this->assertEquals('Marie', $result['prenom']);
        $this->assertEquals(42, $result['id']);
    }

    public function testUpdateProfileThrowsExceptionWhenNoData(): void
    {
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        // updateProfile ne doit jamais être appelé sur le repo
        $userRepositoryMock->expects($this->never())->method('updateProfile');

        $this->expectException(UserServiceException::class);
        $this->expectExceptionMessage('Aucune donnée à mettre à jour');

        $userService = new UserService($userRepositoryMock, $loggerMock);
        $userService->updateProfile(1, []);
    }

    public function testUpdateProfileMapsAllKeysCorrectly(): void
    {
        $userId = 10;
        $inputData = [
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'phone' => '0601020304',
            'address' => '10 Rue Test',
            'city' => 'Bordeaux',
            'postalCode' => '33000',
        ];

        $userRepositoryMock = $this->createMock(UserRepository::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $userRepositoryMock->expects($this->once())
            ->method('updateProfile')
            ->with($userId, $this->callback(function ($dbData) {
                return $dbData['prenom'] === 'Jean'
                    && $dbData['nom'] === 'Dupont'
                    && $dbData['gsm'] === '0601020304'
                    && $dbData['adresse_postale'] === '10 Rue Test'
                    && $dbData['ville'] === 'Bordeaux'
                    && $dbData['code_postal'] === '33000';
            }));

        $userRepositoryMock->method('findById')
            ->willReturn(['id' => 10, 'prenom' => 'Jean']);

        $userService = new UserService($userRepositoryMock, $loggerMock);
        $result = $userService->updateProfile($userId, $inputData);

        $this->assertEquals('Jean', $result['prenom']);
    }
}
