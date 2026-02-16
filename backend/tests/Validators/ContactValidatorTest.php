<?php

namespace Tests\Validators;

use PHPUnit\Framework\TestCase;
use App\Validators\ContactValidator;

class ContactValidatorTest extends TestCase
{
    private ContactValidator $validator;
    private array $validData;

    protected function setUp(): void
    {
        $this->validator = new ContactValidator();

        // Données valides de référence
        $this->validData = [
            'titre' => 'Demande de devis pour un événement',
            'email' => 'visiteur@example.com',
            'description' => 'Bonjour, je souhaite organiser un repas pour 20 personnes le 15 mars.'
        ];
    }

    // ── Tests données valides ──

    public function testValidDataReturnsNoErrors(): void
    {
        $result = $this->validator->validate($this->validData);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidDataWithAccentsAndSpecialChars(): void
    {
        $data = $this->validData;
        $data['titre'] = 'Événement à Noël — spécialités françaises';
        $data['description'] = 'Je voudrais un menu végétarien pour Pâques, avec des plats sans gluten.';

        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    // ── Tests titre ──

    public function testTitreRequired(): void
    {
        $data = $this->validData;
        unset($data['titre']);

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('titre', $result['errors']);
        $this->assertStringContainsString('requis', $result['errors']['titre']);
    }

    public function testTitreEmpty(): void
    {
        $data = $this->validData;
        $data['titre'] = '';

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('titre', $result['errors']);
    }

    public function testTitreMustBeString(): void
    {
        $data = $this->validData;
        $data['titre'] = 12345;

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('titre', $result['errors']);
        $this->assertStringContainsString('chaîne', $result['errors']['titre']);
    }

    public function testTitreTooShort(): void
    {
        $data = $this->validData;
        $data['titre'] = 'Ab';

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('titre', $result['errors']);
        $this->assertStringContainsString('3 caractères', $result['errors']['titre']);
    }

    public function testTitreExactlyMinLength(): void
    {
        $data = $this->validData;
        $data['titre'] = 'Abc';

        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
    }

    public function testTitreTooLong(): void
    {
        $data = $this->validData;
        $data['titre'] = str_repeat('a', 151);

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('titre', $result['errors']);
        $this->assertStringContainsString('150', $result['errors']['titre']);
    }

    public function testTitreExactly150Chars(): void
    {
        $data = $this->validData;
        $data['titre'] = str_repeat('a', 150);

        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
    }

    // ── Tests email ──

    public function testEmailRequired(): void
    {
        $data = $this->validData;
        unset($data['email']);

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertStringContainsString('requise', $result['errors']['email']);
    }

    public function testEmailEmpty(): void
    {
        $data = $this->validData;
        $data['email'] = '';

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
    }

    public function testEmailMustBeString(): void
    {
        $data = $this->validData;
        $data['email'] = 12345;

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertStringContainsString('chaîne', $result['errors']['email']);
    }

    public function testEmailInvalidFormat(): void
    {
        $data = $this->validData;
        $data['email'] = 'pas-un-email';

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertStringContainsString('invalide', $result['errors']['email']);
    }

    public function testEmailMissingDomain(): void
    {
        $data = $this->validData;
        $data['email'] = 'user@';

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
    }

    public function testEmailMissingAt(): void
    {
        $data = $this->validData;
        $data['email'] = 'user.example.com';

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('email', $result['errors']);
    }

    public function testEmailValidFormats(): void
    {
        $validEmails = [
            'user@example.com',
            'user.name@domain.fr',
            'user+tag@domain.co.uk',
        ];

        foreach ($validEmails as $email) {
            $data = $this->validData;
            $data['email'] = $email;

            $result = $this->validator->validate($data);

            $this->assertTrue($result['isValid'], "Email valide rejeté: $email");
        }
    }

    // ── Tests description ──

    public function testDescriptionRequired(): void
    {
        $data = $this->validData;
        unset($data['description']);

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('description', $result['errors']);
        $this->assertStringContainsString('requis', $result['errors']['description']);
    }

    public function testDescriptionEmpty(): void
    {
        $data = $this->validData;
        $data['description'] = '';

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('description', $result['errors']);
    }

    public function testDescriptionMustBeString(): void
    {
        $data = $this->validData;
        $data['description'] = 12345;

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('description', $result['errors']);
        $this->assertStringContainsString('chaîne', $result['errors']['description']);
    }

    public function testDescriptionTooShort(): void
    {
        $data = $this->validData;
        $data['description'] = 'Court';

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('description', $result['errors']);
        $this->assertStringContainsString('10 caractères', $result['errors']['description']);
    }

    public function testDescriptionExactlyMinLength(): void
    {
        $data = $this->validData;
        $data['description'] = str_repeat('a', 10);

        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
    }

    // ── Tests multiples erreurs ──

    public function testMultipleFieldErrors(): void
    {
        $data = [
            'titre' => '',
            'email' => 'invalid',
            'description' => 'court'
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertCount(3, $result['errors']);
        $this->assertArrayHasKey('titre', $result['errors']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertArrayHasKey('description', $result['errors']);
    }

    public function testAllFieldsMissing(): void
    {
        $result = $this->validator->validate([]);

        $this->assertFalse($result['isValid']);
        $this->assertCount(3, $result['errors']);
    }

    // ── Tests sécurité ──

    public function testTitreWithHtmlTagsIsAccepted(): void
    {
        // Le validator accepte les tags ; l'échappement se fait à l'affichage
        $data = $this->validData;
        $data['titre'] = 'Test <script>alert("xss")</script>';

        $result = $this->validator->validate($data);

        // Le validator ne bloque pas les tags HTML (c'est le rôle de htmlspecialchars à l'affichage)
        $this->assertTrue($result['isValid']);
    }

    public function testDescriptionWithVeryLongTextIsAccepted(): void
    {
        $data = $this->validData;
        $data['description'] = str_repeat('Lorem ipsum dolor sit amet. ', 500);

        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
    }
}
