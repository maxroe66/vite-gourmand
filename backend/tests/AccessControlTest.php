<?php

use PHPUnit\Framework\TestCase;
use App\Controllers\AvisController;
use App\Controllers\StatsController;
use App\Controllers\AdminController;
use App\Services\AvisService;
use App\Services\UserService;
use App\Services\AuthService;
use App\Services\MailerService;
use App\Validators\EmployeeValidator;
use App\Core\Request;
use App\Core\Response;
use Psr\Log\NullLogger;

/**
 * Tests de contrôle d'accès backend (defense-in-depth).
 * 
 * Vérifie que les contrôleurs rejettent les requêtes non autorisées
 * même si le middleware est contourné (sécurité en profondeur).
 * 
 * Audit sécurité item #6 : Protection backend des routes admin.
 */
class AccessControlTest extends TestCase
{
    // =============================================
    // AvisController — validate / delete
    // =============================================

    private function makeAvisController(): AvisController
    {
        $service = $this->createMock(AvisService::class);
        return new AvisController($service);
    }

    public function testValidateAvisRejectsUnauthenticated(): void
    {
        $controller = $this->makeAvisController();
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')->willReturn(null);

        $response = $controller->validate($request, ['id' => '1']);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testValidateAvisRejectsClient(): void
    {
        $controller = $this->makeAvisController();
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['sub' => 1, 'role' => 'CLIENT']);

        $response = $controller->validate($request, ['id' => '1']);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testValidateAvisRejectsEmploye(): void
    {
        $controller = $this->makeAvisController();
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['sub' => 2, 'role' => 'EMPLOYE']);

        $response = $controller->validate($request, ['id' => '1']);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDeleteAvisRejectsUnauthenticated(): void
    {
        $controller = $this->makeAvisController();
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')->willReturn(null);

        $response = $controller->delete($request, ['id' => '1']);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testDeleteAvisRejectsClient(): void
    {
        $controller = $this->makeAvisController();
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['sub' => 1, 'role' => 'CLIENT']);

        $response = $controller->delete($request, ['id' => '1']);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDeleteAvisRejectsEmploye(): void
    {
        $controller = $this->makeAvisController();
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['sub' => 2, 'role' => 'EMPLOYE']);

        $response = $controller->delete($request, ['id' => '1']);

        $this->assertSame(403, $response->getStatusCode());
    }

    // =============================================
    // StatsController — getMenuStats
    // =============================================

    public function testStatsRejectsUnauthenticated(): void
    {
        $controller = new StatsController('test_db', null);
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')->willReturn(null);

        $response = $controller->getMenuStats($request);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testStatsRejectsEmploye(): void
    {
        $controller = new StatsController('test_db', null);
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['sub' => 2, 'role' => 'EMPLOYE']);

        $response = $controller->getMenuStats($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testStatsRejectsClient(): void
    {
        $controller = new StatsController('test_db', null);
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['sub' => 1, 'role' => 'CLIENT']);

        $response = $controller->getMenuStats($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    // =============================================
    // AdminController — checkAdminAccess (via createEmployee)
    // =============================================

    private function makeAdminController(): AdminController
    {
        $userService = $this->createMock(UserService::class);
        $authService = $this->createMock(AuthService::class);
        $mailerService = $this->createMock(MailerService::class);
        $logger = new NullLogger();
        $employeeValidator = $this->createMock(EmployeeValidator::class);

        return new AdminController($userService, $authService, $mailerService, $logger, $employeeValidator);
    }

    public function testAdminCreateEmployeeRejectsUnauthenticated(): void
    {
        $controller = $this->makeAdminController();
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')->willReturn(null);

        $response = $controller->createEmployee($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testAdminCreateEmployeeRejectsEmploye(): void
    {
        $controller = $this->makeAdminController();
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['sub' => 2, 'role' => 'EMPLOYE']);

        $response = $controller->createEmployee($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testAdminCreateEmployeeRejectsClient(): void
    {
        $controller = $this->makeAdminController();
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['sub' => 1, 'role' => 'CLIENT']);

        $response = $controller->createEmployee($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testAdminGetEmployeesRejectsEmploye(): void
    {
        $controller = $this->makeAdminController();
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['sub' => 2, 'role' => 'EMPLOYE']);

        $response = $controller->getEmployees($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testAdminDisableUserRejectsClient(): void
    {
        $controller = $this->makeAdminController();
        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')
            ->willReturn((object)['sub' => 1, 'role' => 'CLIENT']);

        $response = $controller->disableUser(['id' => '5'], $request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
