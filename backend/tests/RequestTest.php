<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\Request;

class RequestTest extends TestCase
{
    public function test_constructor_and_getJsonBody_with_parsed_body(): void
    {
        // Arrange
        $data = ['email' => 'test@example.com', 'password' => 'test123'];
        $request = Request::createFromJson($data);

        // Act & Assert
        $this->assertEquals($data, $request->getJsonBody());
    }

    public function test_constructor_with_null_body(): void
    {
        // Arrange & Act
        $request = Request::createFromJson(null);

        // Assert
        $this->assertNull($request->getJsonBody());
    }

    public function test_getJsonParam_returns_value_when_exists(): void
    {
        // Arrange
        $data = ['email' => 'test@example.com', 'name' => 'John'];
        $request = Request::createFromJson($data);

        // Act & Assert
        $this->assertEquals('test@example.com', $request->getJsonParam('email'));
        $this->assertEquals('John', $request->getJsonParam('name'));
    }

    public function test_getJsonParam_returns_default_when_not_exists(): void
    {
        // Arrange
        $data = ['email' => 'test@example.com'];
        $request = Request::createFromJson($data);

        // Act & Assert
        $this->assertNull($request->getJsonParam('nonexistent'));
        $this->assertEquals('default', $request->getJsonParam('nonexistent', 'default'));
    }

    public function test_setAttribute_and_getAttribute(): void
    {
        // Arrange
        $request = new Request();

        // Act
        $request->setAttribute('user', ['id' => 1, 'role' => 'admin']);

        // Assert
        $user = $request->getAttribute('user');
        $this->assertEquals(['id' => 1, 'role' => 'admin'], $user);
    }

    public function test_getAttribute_returns_default_when_not_set(): void
    {
        // Arrange
        $request = new Request();

        // Act & Assert
        $this->assertNull($request->getAttribute('nonexistent'));
        $this->assertEquals('default', $request->getAttribute('nonexistent', 'default'));
    }

    public function test_getJsonBody_with_raw_body(): void
    {
        // Arrange
        $rawData = '{"test":"value"}';
        $request = new Request();
        $request->setRawBody($rawData);

        // Assert
        $this->assertEquals($rawData, $request->getRawBody());
        $this->assertEquals(['test' => 'value'], $request->getJsonBody());
    }

    public function test_getJsonBody_invalid_json_sets_error(): void
    {
        $request = new Request();
        $request->setRawBody('{invalid json');

        $this->assertNull($request->getJsonBody());
        $this->assertTrue($request->hasJsonError());
        $error = $request->getJsonError();
        $this->assertIsArray($error);
        $this->assertArrayHasKey('code', $error);
        $this->assertArrayHasKey('message', $error);
    }

    public function test_getJsonBody_empty_body_no_error(): void
    {
        $request = new Request();
        $request->setRawBody('');

        $this->assertNull($request->getJsonBody());
        $this->assertFalse($request->hasJsonError());
        $this->assertNull($request->getJsonError());
    }
}
