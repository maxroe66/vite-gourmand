<?php

use PHPUnit\Framework\TestCase;
use App\Validators\CommandeValidator;

class CommandeValidatorTest extends TestCase
{
    private CommandeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CommandeValidator();
    }

    public function testValidateCreateNominal(): void
    {
        $tomorrow = (new \DateTime())->modify('+2 days')->format('Y-m-d');
        $data = [
            'menuId' => 1,
            'nombrePersonnes' => 10,
            'adresseLivraison' => '10 Rue Saint-Catherine, 33000 Bordeaux',
            'datePrestation' => $tomorrow,
            'heureLivraison' => '12:00'
        ];

        $errors = $this->validator->validateCreate($data);
        $this->assertEmpty($errors, 'La validation nominale devrait réussir');
    }

    public function testValidateCreateWithSnakeCaseKeys(): void
    {
        // Ce test vérifiera si on supporte le format snake_case souvent envoyé par le front ou curl
        $tomorrow = (new \DateTime())->modify('+2 days')->format('Y-m-d');
        $data = [
            'menu_id' => 1,
            'nombre_personnes' => 10,
            'adresseLivraison' => 'Bordeaux', // Mixte possible
            'datePrestation' => $tomorrow,
            'heureLivraison' => '12:00'
        ];

        // Pour l'instant le code ne le supporte pas, on commente ou on assert que ça échoue pour le moment
        // Mais notre but est de le faire passer si on a décidé de supporter snake_case partout.
        // Le controlleur normalise, mais le validateur reçoit les données brutes.
        // Le controlleur devrait peut-être normaliser AVANT le validateur ? ou le validateur être intelligent.
        
        // Disons que pour le moment, le validateur est strict "camelCase" comme défini dans l'implémentation actuelle.
        // Je vais tester l'échec pour être honnête avec le code actuel.
        $errors = $this->validator->validateCreate($data);
        $this->assertArrayHasKey('menuId', $errors, 'Doit échouer si menuId manquant');
    }

    public function testValidateCreateDatePassee(): void
    {
        $yesterday = (new \DateTime())->modify('-1 day')->format('Y-m-d');
        $data = [
            'menuId' => 1,
            'nombrePersonnes' => 10,
            'adresseLivraison' => 'Bordeaux',
            'datePrestation' => $yesterday,
            'heureLivraison' => '12:00'
        ];

        $errors = $this->validator->validateCreate($data);
        $this->assertArrayHasKey('datePrestation', $errors);
    }

    public function testValidateCreateNombrePersonnesNegative(): void
    {
        $data = [
            'menuId' => 1,
            'nombrePersonnes' => -5,
            'adresseLivraison' => 'Bordeaux',
            'datePrestation' => (new \DateTime())->modify('+2 days')->format('Y-m-d'),
            'heureLivraison' => '12:00'
        ];

        $errors = $this->validator->validateCreate($data);
        $this->assertArrayHasKey('nombrePersonnes', $errors);
    }
}
