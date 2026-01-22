<?php

use PHPUnit\Framework\TestCase;
use App\Controllers\StatsController;
use App\Core\Request;
use App\Core\Response;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use stdClass;

class StatsControllerTest extends TestCase
{
    private StatsController $controller;
    private $mongoClient;
    private $mongoCollection;
    
    protected function setUp(): void
    {
        // Mock MongoDB
        $this->mongoClient = $this->createMock(Client::class);
        $mongoDb = $this->createMock(Database::class);
        $this->mongoCollection = $this->createMock(Collection::class);

        $this->mongoClient->method('selectCollection')->willReturn($this->mongoCollection);
        $this->mongoClient->method('selectDatabase')->willReturn($mongoDb);
        $mongoDb->method('selectCollection')->willReturn($this->mongoCollection);

        $this->controller = new StatsController('test_db', $this->mongoClient);
    }

    public function testGetMenuStatsAsAdmin(): void
    {
        $request = $this->createMock(Request::class);
        $user = new stdClass();
        $user->role = 'ADMINISTRATEUR';
        $request->method('getAttribute')->with('user')->willReturn($user);

        // Usage of stub to satisfy type hint for MongoDB\Driver\CursorInterface
        $mockCursor = $this->createStub(\MongoDB\Driver\CursorInterface::class);
        
        $this->mongoCollection->expects($this->once())
            ->method('aggregate')
            ->willReturn($mockCursor);

        $response = $this->controller->getMenuStats($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetMenuStatsForbidden(): void
    {
        // Mock Request with Employee User (Not Admin)
        $request = $this->createMock(Request::class);
        $user = new stdClass();
        $user->role = 'EMPLOYE';
        $request->method('getAttribute')->with('user')->willReturn($user);

        $response = $this->controller->getMenuStats($request);
        
        $this->assertEquals(403, $response->getStatusCode());
    }
}
