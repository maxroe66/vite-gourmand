<?php

namespace Tests\Validators;

use PHPUnit\Framework\TestCase;
use App\Validators\MaterielValidator;

/**
 * Tests unitaires pour MaterielValidator.
 * Vérifie la validation des champs : libelle, description, valeur_unitaire, stock_disponible.
 */
class MaterielValidatorTest extends TestCase
{
    private MaterielValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new MaterielValidator();
    }

    /**
     * Données valides par défaut.
     */
    private function validData(): array
    {
        return [
            'libelle' => 'Chaise pliante',
            'description' => 'Chaise en plastique blanche',
            'valeur_unitaire' => 15.50,
            'stock_disponible' => 100,
        ];
    }

    // ─── Cas nominal ─────────────────────────────────────────────

    public function testValidDataPasses(): void
    {
        $result = $this->validator->validate($this->validData());
        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidDataWithoutOptionalDescription(): void
    {
        $data = $this->validData();
        unset($data['description']);
        $result = $this->validator->validate($data);
        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidDataWithCamelCaseKeys(): void
    {
        $data = [
            'libelle' => 'Table ronde',
            'valeurUnitaire' => 45.00,
            'stockDisponible' => 30,
        ];
        $result = $this->validator->validate($data);
        $this->assertTrue($result['isValid'], 'Les clés camelCase doivent être acceptées');
        $this->assertEmpty($result['errors']);
    }

    // ─── libelle ─────────────────────────────────────────────────

    public function testLibelleRequired(): void
    {
        $data = $this->validData();
        unset($data['libelle']);
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('libelle', $result['errors']);
        $this->assertStringContainsString('requis', $result['errors']['libelle']);
    }

    public function testLibelleCannotBeEmpty(): void
    {
        $data = $this->validData();
        $data['libelle'] = '';
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('libelle', $result['errors']);
    }

    public function testLibelleMustBeString(): void
    {
        $data = $this->validData();
        $data['libelle'] = 12345;
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('libelle', $result['errors']);
        $this->assertStringContainsString('chaîne', $result['errors']['libelle']);
    }

    public function testLibelleTooLong(): void
    {
        $data = $this->validData();
        $data['libelle'] = str_repeat('A', 101);
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('libelle', $result['errors']);
        $this->assertStringContainsString('100', $result['errors']['libelle']);
    }

    public function testLibelleExactly100CharsAccepted(): void
    {
        $data = $this->validData();
        $data['libelle'] = str_repeat('A', 100);
        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
    }

    // ─── description ─────────────────────────────────────────────

    public function testDescriptionOptionalEmptyString(): void
    {
        $data = $this->validData();
        $data['description'] = '';
        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
    }

    public function testDescriptionMustBeStringIfPresent(): void
    {
        $data = $this->validData();
        $data['description'] = 12345;
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('description', $result['errors']);
    }

    public function testDescriptionNullIsAccepted(): void
    {
        $data = $this->validData();
        $data['description'] = null;
        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
    }

    // ─── valeur_unitaire ─────────────────────────────────────────

    public function testValeurUnitaireRequired(): void
    {
        $data = $this->validData();
        unset($data['valeur_unitaire']);
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('valeur_unitaire', $result['errors']);
        $this->assertStringContainsString('requise', $result['errors']['valeur_unitaire']);
    }

    public function testValeurUnitaireMustBePositive(): void
    {
        $data = $this->validData();
        $data['valeur_unitaire'] = 0;
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('valeur_unitaire', $result['errors']);
        $this->assertStringContainsString('supérieur à 0', $result['errors']['valeur_unitaire']);
    }

    public function testValeurUnitaireNegativeRejected(): void
    {
        $data = $this->validData();
        $data['valeur_unitaire'] = -10;
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('valeur_unitaire', $result['errors']);
    }

    public function testValeurUnitaireNonNumericRejected(): void
    {
        $data = $this->validData();
        $data['valeur_unitaire'] = 'abc';
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('valeur_unitaire', $result['errors']);
    }

    public function testValeurUnitaireDecimalAccepted(): void
    {
        $data = $this->validData();
        $data['valeur_unitaire'] = 0.01;
        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
    }

    // ─── stock_disponible ────────────────────────────────────────

    public function testStockDisponibleRequired(): void
    {
        $data = $this->validData();
        unset($data['stock_disponible']);
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('stock_disponible', $result['errors']);
        $this->assertStringContainsString('requis', $result['errors']['stock_disponible']);
    }

    public function testStockDisponibleZeroAccepted(): void
    {
        $data = $this->validData();
        $data['stock_disponible'] = 0;
        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
    }

    public function testStockDisponibleNegativeRejected(): void
    {
        $data = $this->validData();
        $data['stock_disponible'] = -5;
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('stock_disponible', $result['errors']);
    }

    public function testStockDisponibleNonNumericRejected(): void
    {
        $data = $this->validData();
        $data['stock_disponible'] = 'abc';
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('stock_disponible', $result['errors']);
    }

    // ─── Erreurs multiples ───────────────────────────────────────

    public function testMultipleErrorsReturnedAtOnce(): void
    {
        $data = []; // Tout manquant
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('libelle', $result['errors']);
        $this->assertArrayHasKey('valeur_unitaire', $result['errors']);
        $this->assertArrayHasKey('stock_disponible', $result['errors']);
    }
}
