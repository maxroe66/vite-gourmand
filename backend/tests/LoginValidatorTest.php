<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Validators\LoginValidator;

class LoginValidatorTest extends TestCase
{
    private LoginValidator $validator;
    private array $validData;

    protected function setUp(): void
    {
        $this->validator = new LoginValidator();

        // Données valides de référence pour le login
        $this->validData = [
            'email' => 'marie.dupont@email.fr',
            'password' => 'anypassword123'
        ];
    }

    public function testValidDataReturnsNoErrors(): void
    {
        $result = $this->validator->validate($this->validData);
        $errors = $result["errors"];
        $this->assertEmpty($errors);
    }

    // Tests email
    public function testEmailRequired(): void
    {
        $data = $this->validData;
        unset($data['email']);

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals("L'email est requis.", $errors['email']);
    }

    public function testEmailMustBeString(): void
    {
        $data = $this->validData;
        $data['email'] = ['not', 'a', 'string'];

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals("L'email doit être une chaîne de caractères.", $errors['email']);
    }

    public function testEmailMustBeValidFormat(): void
    {
        $data = $this->validData;
        $data['email'] = 'invalid-email';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals("L'email n'est pas valide.", $errors['email']);
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

            $result = $this->validator->validate($data);
        $errors = $result["errors"];

            $this->assertEmpty($errors, "Email $email devrait être valide");
        }
    }

    // Tests password
    public function testPasswordRequired(): void
    {
        $data = $this->validData;
        unset($data['password']);

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('password', $errors);
        $this->assertEquals('Le mot de passe est requis.', $errors['password']);
    }

    public function testPasswordMustBeString(): void
    {
        $data = $this->validData;
        $data['password'] = 123456;

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('password', $errors);
        $this->assertEquals('Le mot de passe doit être une chaîne de caractères.', $errors['password']);
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

            $result = $this->validator->validate($data);
        $errors = $result["errors"];

            $this->assertEmpty($errors, "Password '$password' devrait être accepté pour le login");
        }
    }

    // Tests combinés
    public function testMultipleErrorsReturned(): void
    {
        $data = [
            'email' => '',
            'password' => ''
        ];

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
        $this->assertCount(2, $errors);
    }

    public function testMissingFieldsTreatedAsEmpty(): void
    {
        $data = []; // Aucun champ fourni

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    public function testExtraFieldsIgnored(): void
    {
        $data = $this->validData;
        $data['extraField'] = 'should be ignored';
        $data['anotherField'] = 'also ignored';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertEmpty($errors);
    }
}
