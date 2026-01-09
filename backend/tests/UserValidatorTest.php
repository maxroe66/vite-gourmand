<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Validators\UserValidator;

class UserValidatorTest extends TestCase
{
    private UserValidator $validator;
    private array $validData;

    protected function setUp(): void
    {
        $this->validator = new UserValidator();

        // Données valides de référence
        $this->validData = [
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'email' => 'jean.dupont@example.com',
            'password' => 'Password123',
            'phone' => '0123456789',
            'address' => '123 Rue de la Paix',
            'city' => 'Paris',
            'postalCode' => '75001'
        ];
    }

    public function testValidDataReturnsNoErrors(): void
    {
        $result = $this->validator->validate($this->validData);
        $errors = $result["errors"];
        $this->assertEmpty($errors);
    }

    // Tests firstName
    public function testFirstNameRequired(): void
    {
        $data = $this->validData;
        unset($data['firstName']);

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('firstName', $errors);
        $this->assertEquals('Le prénom est requis.', $errors['firstName']);
    }

    public function testFirstNameMustBeString(): void
    {
        $data = $this->validData;
        $data['firstName'] = 123;

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('firstName', $errors);
    }

    public function testFirstNameWithInvalidCharacters(): void
    {
        $data = $this->validData;
        $data['firstName'] = 'Jean123';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('firstName', $errors);
        $this->assertStringContainsString('lettres', $errors['firstName']);
    }

    public function testFirstNameWithAccents(): void
    {
        $data = $this->validData;
        $data['firstName'] = 'François';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayNotHasKey('firstName', $errors);
    }

    public function testFirstNameWithEmojis(): void
    {
        $data = $this->validData;
        $data['firstName'] = '😀🚀✨漢字';

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid'], 'Validation should fail for emojis');
        $this->assertArrayHasKey('firstName', $result['errors'], 'Error key firstName missing');
    }

    // Tests lastName
    public function testLastNameRequired(): void
    {
        $data = $this->validData;
        unset($data['lastName']);

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('lastName', $errors);
    }

    public function testLastNameMustBeString(): void
    {
        $data = $this->validData;
        $data['lastName'] = 456;

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('lastName', $result['errors']);
    }

    // Tests email
    public function testEmailRequired(): void
    {
        $data = $this->validData;
        unset($data['email']);

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals("L'adresse email est requise.", $errors['email']);
    }

    public function testEmailMustBeString(): void
    {
        $data = $this->validData;
        $data['email'] = 12345;

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('email', $errors);
    }

    public function testEmailInvalidFormat(): void
    {
        $data = $this->validData;
        $data['email'] = 'invalid-email';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('email', $errors);
        $this->assertStringContainsString('format', $errors['email']);
    }

    // Tests password
    public function testPasswordRequired(): void
    {
        $data = $this->validData;
        unset($data['password']);

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('password', $errors);
    }

    public function testPasswordMustBeString(): void
    {
        $data = $this->validData;
        $data['password'] = 12345678;

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('password', $errors);
    }

    public function testPasswordTooShort(): void
    {
        $data = $this->validData;
        $data['password'] = 'Pass1';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('password', $errors);
        $this->assertStringContainsString('8 caractères', $errors['password']);
    }

    public function testPasswordWithoutUppercase(): void
    {
        $data = $this->validData;
        $data['password'] = 'password123';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('password', $errors);
        $this->assertStringContainsString('majuscule', $errors['password']);
    }

    public function testPasswordWithoutLowercase(): void
    {
        $data = $this->validData;
        $data['password'] = 'PASSWORD123';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('password', $errors);
    }

    public function testPasswordWithoutDigit(): void
    {
        $data = $this->validData;
        $data['password'] = 'PasswordABC';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('password', $result['errors']);
    }

    // Tests phone
    public function testPhoneRequired(): void
    {
        $data = $this->validData;
        unset($data['phone']);

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('phone', $errors);
    }

    public function testPhoneMustBeString(): void
    {
        $data = $this->validData;
        $data['phone'] = 123456789;

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('phone', $errors);
    }

    public function testPhoneInvalidFormat(): void
    {
        $data = $this->validData;
        $data['phone'] = '123';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('phone', $errors);
    }

    public function testPhoneWithSpaces(): void
    {
        $data = $this->validData;
        $data['phone'] = '01 23 45 67 89';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayNotHasKey('phone', $errors);
    }

    // Tests address
    public function testAddressRequired(): void
    {
        $data = $this->validData;
        unset($data['address']);

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('address', $errors);
    }

    public function testAddressMustBeString(): void
    {
        $data = $this->validData;
        $data['address'] = 12345;

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('address', $errors);
    }

    public function testAddressTooShort(): void
    {
        $data = $this->validData;
        $data['address'] = 'Rue';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('address', $errors);
        $this->assertStringContainsString('5 caractères', $errors['address']);
    }

    // Tests city
    public function testCityRequired(): void
    {
        $data = $this->validData;
        unset($data['city']);

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('city', $errors);
    }

    public function testCityMustBeString(): void
    {
        $data = $this->validData;
        $data['city'] = 75001;

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('city', $result['errors']);
    }

    // Tests postalCode
    public function testPostalCodeRequired(): void
    {
        $data = $this->validData;
        $data['postalCode'] = '';

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('postalCode', $result['errors']);
    }

    public function testPostalCodeMustBeString(): void
    {
        $data = $this->validData;
        $data['postalCode'] = 75001;

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('postalCode', $errors);
    }

    public function testPostalCodeInvalidFormat(): void
    {
        $data = $this->validData;
        $data['postalCode'] = '123';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('postalCode', $errors);
        $this->assertStringContainsString('5 chiffres', $errors['postalCode']);
    }

    public function testPostalCodeWithLetters(): void
    {
        $data = $this->validData;
        $data['postalCode'] = '75A01';

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertArrayHasKey('postalCode', $errors);
    }

    // Tests combinés
    public function testMultipleFieldsInvalid(): void
    {
        $data = [
            'firstName' => '',
            'lastName' => '',
            'email' => 'invalid',
            'password' => '123',
            'phone' => '12',
            'address' => 'Ab',
            'city' => '',
            'postalCode' => '123'
        ];

        $result = $this->validator->validate($data);
        $errors = $result["errors"];

        $this->assertCount(8, $errors);
        $this->assertArrayHasKey('firstName', $errors);
        $this->assertArrayHasKey('lastName', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
        $this->assertArrayHasKey('phone', $errors);
        $this->assertArrayHasKey('address', $errors);
        $this->assertArrayHasKey('city', $errors);
        $this->assertArrayHasKey('postalCode', $errors);
    }
}
