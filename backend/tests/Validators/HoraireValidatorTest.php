<?php

namespace Tests\Validators;

use PHPUnit\Framework\TestCase;
use App\Validators\HoraireValidator;

/**
 * Tests unitaires pour HoraireValidator.
 * Vérifie la validation des champs : heureOuverture, heureFermeture, ferme.
 */
class HoraireValidatorTest extends TestCase
{
    private HoraireValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new HoraireValidator();
    }

    // ─── Cas nominal (jour ouvert) ───────────────────────────────

    public function testValidOpenDayPasses(): void
    {
        $data = [
            'ferme' => false,
            'heureOuverture' => '09:00',
            'heureFermeture' => '18:00',
        ];
        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidOpenDayWithSecondsFormat(): void
    {
        $data = [
            'ferme' => false,
            'heureOuverture' => '09:00:00',
            'heureFermeture' => '18:30:00',
        ];
        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
    }

    // ─── Cas nominal (jour fermé) ────────────────────────────────

    public function testClosedDayPassesWithoutHours(): void
    {
        $data = [
            'ferme' => true,
        ];
        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    public function testClosedDayPassesEvenWithEmptyHours(): void
    {
        $data = [
            'ferme' => true,
            'heureOuverture' => '',
            'heureFermeture' => '',
        ];
        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
    }

    // ─── heureOuverture ──────────────────────────────────────────

    public function testHeureOuvertureRequiredWhenOpen(): void
    {
        $data = [
            'ferme' => false,
            'heureFermeture' => '18:00',
        ];
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('heureOuverture', $result['errors']);
        $this->assertStringContainsString('requise', $result['errors']['heureOuverture']);
    }

    public function testHeureOuvertureInvalidFormat(): void
    {
        $data = [
            'ferme' => false,
            'heureOuverture' => '9h00',
            'heureFermeture' => '18:00',
        ];
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('heureOuverture', $result['errors']);
        $this->assertStringContainsString('format', $result['errors']['heureOuverture']);
    }

    public function testHeureOuvertureEmptyWhenOpen(): void
    {
        $data = [
            'ferme' => false,
            'heureOuverture' => '',
            'heureFermeture' => '18:00',
        ];
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('heureOuverture', $result['errors']);
    }

    // ─── heureFermeture ──────────────────────────────────────────

    public function testHeureFermetureRequiredWhenOpen(): void
    {
        $data = [
            'ferme' => false,
            'heureOuverture' => '09:00',
        ];
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('heureFermeture', $result['errors']);
        $this->assertStringContainsString('requise', $result['errors']['heureFermeture']);
    }

    public function testHeureFermetureInvalidFormat(): void
    {
        $data = [
            'ferme' => false,
            'heureOuverture' => '09:00',
            'heureFermeture' => 'midi',
        ];
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('heureFermeture', $result['errors']);
        $this->assertStringContainsString('format', $result['errors']['heureFermeture']);
    }

    // ─── Cohérence heures ────────────────────────────────────────

    public function testFermetureBeforeOuvertureRejected(): void
    {
        $data = [
            'ferme' => false,
            'heureOuverture' => '18:00',
            'heureFermeture' => '09:00',
        ];
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('heureFermeture', $result['errors']);
        $this->assertStringContainsString('postérieure', $result['errors']['heureFermeture']);
    }

    public function testSameOuvertureAndFermetureRejected(): void
    {
        $data = [
            'ferme' => false,
            'heureOuverture' => '12:00',
            'heureFermeture' => '12:00',
        ];
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('heureFermeture', $result['errors']);
    }

    // ─── Clés snake_case ─────────────────────────────────────────

    public function testSnakeCaseKeysAccepted(): void
    {
        $data = [
            'ferme' => false,
            'heure_ouverture' => '08:00',
            'heure_fermeture' => '20:00',
        ];
        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);
    }

    // ─── Ferme sans flag (défaut false) ──────────────────────────

    public function testDefaultNotClosedWhenFermeAbsent(): void
    {
        $data = [
            'heureOuverture' => '09:00',
            'heureFermeture' => '17:00',
        ];
        $result = $this->validator->validate($data);

        $this->assertTrue($result['isValid'], 'Sans flag "ferme", le jour est considéré ouvert');
    }

    public function testMissingAllFieldsWhenOpen(): void
    {
        $data = ['ferme' => false];
        $result = $this->validator->validate($data);

        $this->assertFalse($result['isValid']);
        $this->assertArrayHasKey('heureOuverture', $result['errors']);
        $this->assertArrayHasKey('heureFermeture', $result['errors']);
    }
}
