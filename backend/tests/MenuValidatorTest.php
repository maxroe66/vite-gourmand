<?php

use PHPUnit\Framework\TestCase;
use App\Validators\MenuValidator;

/**
 * Tests unitaires pour MenuValidator.
 * Vérifie la validation des types (filter_var au lieu de is_int),
 * les bornes min/max et les cas limites.
 */
class MenuValidatorTest extends TestCase
{
    private MenuValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new MenuValidator();
    }

    /**
     * Données valides par défaut (tous les champs corrects).
     */
    private function validData(): array
    {
        return [
            'titre'            => 'Menu Découverte',
            'description'      => 'Un menu complet pour découvrir nos spécialités.',
            'prix'             => 29.90,
            'nb_personnes_min' => 4,
            'stock'            => 50,
            'id_theme'         => 1,
            'id_regime'        => 2,
        ];
    }

    // ─── Cas nominal ─────────────────────────────────────────────

    public function testValidDataPasses(): void
    {
        $result = $this->validator->validate($this->validData());
        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    // ─── Entiers passés sous forme de chaîne ─────────────────────
    // C'est le cas réel quand les données viennent de multipart/form-data.

    public function testStringIntegersAreAccepted(): void
    {
        $data = $this->validData();
        $data['nb_personnes_min'] = '10';
        $data['stock']            = '100';
        $data['id_theme']         = '3';
        $data['id_regime']        = '5';

        $result = $this->validator->validate($data);
        $this->assertTrue($result['isValid'], 'Des entiers sous forme de chaîne ("10") doivent être acceptés.');
        $this->assertEmpty($result['errors']);
    }

    // ─── nb_personnes_min ────────────────────────────────────────

    public function testNbPersonnesMinRequired(): void
    {
        $data = $this->validData();
        unset($data['nb_personnes_min']);

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('nb_personnes_min', $result['errors']);
    }

    public function testNbPersonnesMinRejectsNonNumericString(): void
    {
        $data = $this->validData();
        $data['nb_personnes_min'] = 'abc';

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('nb_personnes_min', $result['errors']);
    }

    public function testNbPersonnesMinRejectsZero(): void
    {
        $data = $this->validData();
        $data['nb_personnes_min'] = 0;

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('nb_personnes_min', $result['errors']);
    }

    public function testNbPersonnesMinRejectsNegative(): void
    {
        $data = $this->validData();
        $data['nb_personnes_min'] = -5;

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('nb_personnes_min', $result['errors']);
    }

    public function testNbPersonnesMinRejectsAboveMax(): void
    {
        $data = $this->validData();
        $data['nb_personnes_min'] = 501;

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('nb_personnes_min', $result['errors']);
    }

    public function testNbPersonnesMinRejectsFloat(): void
    {
        $data = $this->validData();
        $data['nb_personnes_min'] = 3.5;

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('nb_personnes_min', $result['errors']);
    }

    // ─── stock ───────────────────────────────────────────────────

    public function testStockRequired(): void
    {
        $data = $this->validData();
        unset($data['stock']);

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('stock', $result['errors']);
    }

    public function testStockAcceptsZero(): void
    {
        $data = $this->validData();
        $data['stock'] = 0;

        $result = $this->validator->validate($data);
        $this->assertTrue($result['isValid'], 'stock = 0 doit être accepté.');
    }

    public function testStockAcceptsStringZero(): void
    {
        $data = $this->validData();
        $data['stock'] = '0';

        $result = $this->validator->validate($data);
        $this->assertTrue($result['isValid'], 'stock = "0" doit être accepté.');
    }

    public function testStockRejectsNegative(): void
    {
        $data = $this->validData();
        $data['stock'] = -1;

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('stock', $result['errors']);
    }

    public function testStockRejectsAboveMax(): void
    {
        $data = $this->validData();
        $data['stock'] = 10001;

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('stock', $result['errors']);
    }

    public function testStockRejectsNonNumericString(): void
    {
        $data = $this->validData();
        $data['stock'] = 'beaucoup';

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('stock', $result['errors']);
    }

    // ─── id_theme ────────────────────────────────────────────────

    public function testIdThemeRequired(): void
    {
        $data = $this->validData();
        unset($data['id_theme']);

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('id_theme', $result['errors']);
    }

    public function testIdThemeRejectsNonNumericString(): void
    {
        $data = $this->validData();
        $data['id_theme'] = 'italien';

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('id_theme', $result['errors']);
    }

    public function testIdThemeRejectsZero(): void
    {
        $data = $this->validData();
        $data['id_theme'] = 0;

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('id_theme', $result['errors']);
    }

    public function testIdThemeRejectsNegative(): void
    {
        $data = $this->validData();
        $data['id_theme'] = -1;

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('id_theme', $result['errors']);
    }

    // ─── id_regime ───────────────────────────────────────────────

    public function testIdRegimeRequired(): void
    {
        $data = $this->validData();
        unset($data['id_regime']);

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('id_regime', $result['errors']);
    }

    public function testIdRegimeRejectsNonNumericString(): void
    {
        $data = $this->validData();
        $data['id_regime'] = 'vegan';

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('id_regime', $result['errors']);
    }

    public function testIdRegimeRejectsZero(): void
    {
        $data = $this->validData();
        $data['id_regime'] = 0;

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('id_regime', $result['errors']);
    }

    public function testIdRegimeRejectsNegative(): void
    {
        $data = $this->validData();
        $data['id_regime'] = -3;

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('id_regime', $result['errors']);
    }

    // ─── Autres champs (couverture complémentaire) ───────────────

    public function testTitreMinLength(): void
    {
        $data = $this->validData();
        $data['titre'] = 'AB';

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('titre', $result['errors']);
    }

    public function testDescriptionMinLength(): void
    {
        $data = $this->validData();
        $data['description'] = 'Court';

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('description', $result['errors']);
    }

    public function testPrixRejectsNonNumeric(): void
    {
        $data = $this->validData();
        $data['prix'] = 'gratuit';

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('prix', $result['errors']);
    }

    public function testPrixRejectsZero(): void
    {
        $data = $this->validData();
        $data['prix'] = 0;

        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('prix', $result['errors']);
    }

    public function testConditionsOptionalButMustBeString(): void
    {
        // Sans conditions : OK
        $data = $this->validData();
        $result = $this->validator->validate($data);
        $this->assertTrue($result['isValid']);

        // Avec conditions valides : OK
        $data['conditions'] = 'Commande 48h à l\'avance';
        $result = $this->validator->validate($data);
        $this->assertTrue($result['isValid']);

        // Avec conditions invalides : KO
        $data['conditions'] = 12345;
        $result = $this->validator->validate($data);
        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('conditions', $result['errors']);
    }
}
