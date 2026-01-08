<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Validators\UserValidator;

class UserValidatorTest extends TestCase
{
    private array $validData;

    protected function setUp(): void
    {
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

    public function testValidDataReturnsValid(): void
    {
        $result = UserValidator::validate($this->validData);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    // Tests firstName
    public function testFirstNameRequired(): void
    {
        $data = $this->validData;
        $data['firstName'] = '';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('firstName', $result['errors']);
        $this->assertEquals('Le prénom est requis.', $result['errors']['firstName']);
    }

    public function testFirstNameMustBeString(): void
    {
        $data = $this->validData;
        $data['firstName'] = 123;

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('firstName', $result['errors']);
    }

    public function testFirstNameWithInvalidCharacters(): void
    {
        $data = $this->validData;
        $data['firstName'] = 'Jean123';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('firstName', $result['errors']);
        $this->assertStringContainsString('lettres', $result['errors']['firstName']);
    }

    public function testFirstNameWithAccents(): void
    {
        $data = $this->validData;
        $data['firstName'] = 'François';

        $result = UserValidator::validate($data);

        $this->assertTrue($result['isValid']);
        $this->assertArrayNotHasKey('firstName', $result['errors']);
    }

    // Tests lastName
    public function testLastNameRequired(): void
    {
        $data = $this->validData;
        $data['lastName'] = '';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('lastName', $result['errors']);
    }

    public function testLastNameMustBeString(): void
    {
        $data = $this->validData;
        $data['lastName'] = 456;

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('lastName', $result['errors']);
    }

    // Tests email
    public function testEmailRequired(): void
    {
        $data = $this->validData;
        $data['email'] = '';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertEquals("L'adresse email est requise.", $result['errors']['email']);
    }

    public function testEmailMustBeString(): void
    {
        $data = $this->validData;
        $data['email'] = 12345;

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
    }

    public function testEmailInvalidFormat(): void
    {
        $data = $this->validData;
        $data['email'] = 'invalid-email';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertStringContainsString('format', $result['errors']['email']);
    }

    // Tests password
    public function testPasswordRequired(): void
    {
        $data = $this->validData;
        $data['password'] = '';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('password', $result['errors']);
    }

    public function testPasswordMustBeString(): void
    {
        $data = $this->validData;
        $data['password'] = 12345678;

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('password', $result['errors']);
    }

    public function testPasswordTooShort(): void
    {
        $data = $this->validData;
        $data['password'] = 'Pass1';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('password', $result['errors']);
        $this->assertStringContainsString('8 caractères', $result['errors']['password']);
    }

    public function testPasswordWithoutUppercase(): void
    {
        $data = $this->validData;
        $data['password'] = 'password123';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('password', $result['errors']);
        $this->assertStringContainsString('majuscule', $result['errors']['password']);
    }

    public function testPasswordWithoutLowercase(): void
    {
        $data = $this->validData;
        $data['password'] = 'PASSWORD123';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('password', $result['errors']);
    }

    public function testPasswordWithoutDigit(): void
    {
        $data = $this->validData;
        $data['password'] = 'PasswordABC';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('password', $result['errors']);
    }

    // Tests phone
    public function testPhoneRequired(): void
    {
        $data = $this->validData;
        $data['phone'] = '';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('phone', $result['errors']);
    }

    public function testPhoneMustBeString(): void
    {
        $data = $this->validData;
        $data['phone'] = 123456789;

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('phone', $result['errors']);
    }

    public function testPhoneInvalidFormat(): void
    {
        $data = $this->validData;
        $data['phone'] = '123';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('phone', $result['errors']);
    }

    public function testPhoneWithSpaces(): void
    {
        $data = $this->validData;
        $data['phone'] = '01 23 45 67 89';

        $result = UserValidator::validate($data);

        $this->assertTrue($result['isValid']);
        $this->assertArrayNotHasKey('phone', $result['errors']);
    }

    // Tests address
    public function testAddressRequired(): void
    {
        $data = $this->validData;
        $data['address'] = '';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('address', $result['errors']);
    }

    public function testAddressMustBeString(): void
    {
        $data = $this->validData;
        $data['address'] = 12345;

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('address', $result['errors']);
    }

    public function testAddressTooShort(): void
    {
        $data = $this->validData;
        $data['address'] = 'Rue';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('address', $result['errors']);
        $this->assertStringContainsString('5 caractères', $result['errors']['address']);
    }

    // Tests city
    public function testCityRequired(): void
    {
        $data = $this->validData;
        $data['city'] = '';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('city', $result['errors']);
    }

    public function testCityMustBeString(): void
    {
        $data = $this->validData;
        $data['city'] = 75001;

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('city', $result['errors']);
    }

    // Tests postalCode
    public function testPostalCodeRequired(): void
    {
        $data = $this->validData;
        $data['postalCode'] = '';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('postalCode', $result['errors']);
    }

    public function testPostalCodeMustBeString(): void
    {
        $data = $this->validData;
        $data['postalCode'] = 75001;

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('postalCode', $result['errors']);
    }

    public function testPostalCodeInvalidFormat(): void
    {
        $data = $this->validData;
        $data['postalCode'] = '123';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('postalCode', $result['errors']);
        $this->assertStringContainsString('5 chiffres', $result['errors']['postalCode']);
    }

    public function testPostalCodeWithLetters(): void
    {
        $data = $this->validData;
        $data['postalCode'] = '75A01';

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('postalCode', $result['errors']);
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

        $result = UserValidator::validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertCount(8, $result['errors']);
        $this->assertArrayHasKey('firstName', $result['errors']);
        $this->assertArrayHasKey('lastName', $result['errors']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertArrayHasKey('password', $result['errors']);
        $this->assertArrayHasKey('phone', $result['errors']);
        $this->assertArrayHasKey('address', $result['errors']);
        $this->assertArrayHasKey('city', $result['errors']);
        $this->assertArrayHasKey('postalCode', $result['errors']);
    }
}
