<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Validators\LoginValidator;

class LoginValidatorTest extends TestCase
{
    private array $validData;

    protected function setUp(): void
    {
        // Données valides de référence pour le login
        $this->validData = [
            'email' => 'marie.dupont@email.fr',
            'password' => 'anypassword123'
        ];
    }

    public function testValidDataReturnsValid(): void
    {
        $result = LoginValidator::validate($this->validData);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    // Tests email
    public function testEmailRequired(): void
    {
        $data = $this->validData;
        $data['email'] = '';

        $result = LoginValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertEquals("L'email est requis.", $result['errors']['email']);
    }

    public function testEmailMustBeString(): void
    {
        $data = $this->validData;
        $data['email'] = ['not', 'a', 'string'];

        $result = LoginValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertEquals("L'email doit être une chaîne de caractères.", $result['errors']['email']);
    }

    public function testEmailMustBeValidFormat(): void
    {
        $data = $this->validData;
        $data['email'] = 'invalid-email';

        $result = LoginValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertEquals("L'email n'est pas valide.", $result['errors']['email']);
    }

    public function testEmailAcceptsValidFormats(): void
    {
        $validEmails = [
            'user@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user_name@example-domain.com'
        ];

        foreach ($validEmails as $email) {
            $data = $this->validData;
            $data['email'] = $email;

            $result = LoginValidator::validate($data);

            $this->assertTrue($result['isValid'], "Email $email devrait être valide");
            $this->assertEmpty($result['errors']);
        }
    }

    // Tests password
    public function testPasswordRequired(): void
    {
        $data = $this->validData;
        $data['password'] = '';

        $result = LoginValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('password', $result['errors']);
        $this->assertEquals('Le mot de passe est requis.', $result['errors']['password']);
    }

    public function testPasswordMustBeString(): void
    {
        $data = $this->validData;
        $data['password'] = 123456;

        $result = LoginValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('password', $result['errors']);
        $this->assertEquals('Le mot de passe doit être une chaîne de caractères.', $result['errors']['password']);
    }

    public function testPasswordNoComplexityValidation(): void
    {
        // Pour le login, on n'impose PAS de règles de complexité
        // car on vérifie juste que le champ existe (le hash sera comparé par AuthService)
        $simplePasswords = [
            'abc',           // Trop court
            'lowercase',     // Pas de majuscule
            'UPPERCASE',     // Pas de minuscule
            'NoNumbers',     // Pas de chiffre
            'password'       // Mot de passe faible
        ];

        foreach ($simplePasswords as $password) {
            $data = $this->validData;
            $data['password'] = $password;

            $result = LoginValidator::validate($data);

            $this->assertTrue($result['isValid'], "Password '$password' devrait être accepté pour le login");
            $this->assertEmpty($result['errors']);
        }
    }

    // Tests combinés
    public function testMultipleErrorsReturned(): void
    {
        $data = [
            'email' => '',
            'password' => ''
        ];

        $result = LoginValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertArrayHasKey('password', $result['errors']);
        $this->assertCount(2, $result['errors']);
    }

    public function testMissingFieldsTreatedAsEmpty(): void
    {
        $data = []; // Aucun champ fourni

        $result = LoginValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertArrayHasKey('password', $result['errors']);
    }

    public function testExtraFieldsIgnored(): void
    {
        $data = $this->validData;
        $data['extraField'] = 'should be ignored';
        $data['anotherField'] = 'also ignored';

        $result = LoginValidator::validate($data);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }
}
