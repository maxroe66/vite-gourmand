<?php
use PHPUnit\Framework\TestCase;
use App\Services\UserService;
use App\Exceptions\UserServiceException;

class UserServiceTest extends TestCase
{
    private $userService;

    protected function setUp(): void
    {
        // L'instance sera créée dans chaque test avec le mock PDO
    }

    public function testCreateUserReturnsUserIdOnSuccess()
    {
        $userData = [
            'email' => 'test@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'phone' => '0600000000',
            'address' => '1 rue du test',
            'city' => 'Testville',
            'postalCode' => '12345',
            'passwordHash' => 'hashedpassword',
            'role' => 'client'
        ];

        $pdo = $this->createMock(\PDO::class);
        $stmt1 = $this->createMock(\PDOStatement::class);
        $stmt2 = $this->createMock(\PDOStatement::class);

        // Premier prepare : vérification email
        $stmt1->method('execute')->with(['email' => $userData['email']])->willReturn(true);
        $stmt1->method('fetch')->willReturn(false);

        // Second prepare : insertion
        $stmt2->method('execute')->with([
            'email' => $userData['email'],
            'prenom' => $userData['firstName'],
            'nom' => $userData['lastName'],
            'gsm' => $userData['phone'],
            'adresse_postale' => $userData['address'],
            'ville' => $userData['city'],
            'code_postal' => $userData['postalCode'],
            'mot_de_passe' => $userData['passwordHash'],
            'role' => $userData['role']
        ])->willReturn(true);

        $pdo->method('prepare')->willReturnOnConsecutiveCalls($stmt1, $stmt2);
        $pdo->method('lastInsertId')->willReturn('42');

        $userService = new \App\Services\UserService($pdo);
        $userId = $userService->createUser($userData);
        $this->assertEquals(42, $userId);
    }

    public function testCreateUserThrowsExceptionIfEmailExists()
    {
        $userData = [
            'email' => 'exists@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'phone' => '0600000000',
            'address' => '1 rue du test',
            'city' => 'Testville',
            'postalCode' => '12345',
            'passwordHash' => 'hashedpassword',
            'role' => 'client'
        ];

        $pdo = $this->createMock(\PDO::class);
        $stmt = $this->createMock(\PDOStatement::class);

        $stmt->method('execute')->with(['email' => $userData['email']])->willReturn(true);
        $stmt->method('fetch')->willReturn([
            'id_utilisateur' => 1,
            'email' => $userData['email'],
            'prenom' => $userData['firstName'],
            'nom' => $userData['lastName'],
            'gsm' => $userData['phone'],
            'adresse_postale' => $userData['address'],
            'ville' => $userData['city'],
            'code_postal' => $userData['postalCode'],
            'mot_de_passe' => $userData['passwordHash'],
            'role' => $userData['role']
        ]);

        $pdo->method('prepare')->willReturn($stmt);
        $userService = new \App\Services\UserService($pdo);
        $this->expectException(UserServiceException::class);
        $userService->createUser($userData);
    }
}
