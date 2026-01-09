<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\Request;

class RequestTest extends TestCase
{
    public function test_createFromJson_with_valid_data(): void
    {
        // Arrange
        $data = ['email' => 'test@example.com', 'password' => 'test123'];

        // Act
        $request = Request::createFromJson($data);

        // Assert
        $this->assertEquals($data, $request->getJsonBody());
    }

    public function test_createFromJson_with_null(): void
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
        $request = Request::createFromGlobals();

        // Act
        $request->setAttribute('user', ['id' => 1, 'role' => 'admin']);

        // Assert
        $user = $request->getAttribute('user');
        $this->assertEquals(['id' => 1, 'role' => 'admin'], $user);
    }

    public function test_getAttribute_returns_default_when_not_set(): void
    {
        // Arrange
        $request = Request::createFromGlobals();

        // Act & Assert
        $this->assertNull($request->getAttribute('nonexistent'));
        $this->assertEquals('default', $request->getAttribute('nonexistent', 'default'));
    }

    public function test_setParsedBody_and_getJsonBody(): void
    {
        // Arrange
        $request = Request::createFromGlobals();
        $data = ['test' => 'value'];

        // Act
        $request->setParsedBody($data);

        // Assert
        $this->assertEquals($data, $request->getJsonBody());
    }

    public function test_setRawBody_and_getRawBody(): void
    {
        // Arrange
        $request = Request::createFromGlobals();
        $rawData = '{"test":"value"}';

        // Act
        $request->setRawBody($rawData);

        // Assert
        $this->assertEquals($rawData, $request->getRawBody());
        $this->assertEquals(['test' => 'value'], $request->getJsonBody());
    }

    public function test_setRawBody_resets_parsed_body(): void
    {
        // Arrange
        $request = Request::createFromGlobals();
        $request->setParsedBody(['old' => 'data']);

        // Act
        $request->setRawBody('{"new":"data"}');

        // Assert
        $this->assertEquals(['new' => 'data'], $request->getJsonBody());
    }
}
