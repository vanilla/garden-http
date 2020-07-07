<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests\Mocks;

use Garden\Exception\NotFoundException;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Http\HttpResponseException;
use Garden\Http\Mocks\MockHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the mock http client.
 */
class MockHttpClientTest extends TestCase {

    /**
     * Test that mock responses are found or not found.
     */
    public function testMockResponses() {
        $client = new MockHttpClient();
        $mockedResponse = new HttpResponse(200, ['header' => 'value'], '');
        $client->addMockResponse('/mock-endpoint?query=param', $mockedResponse, HttpRequest::METHOD_GET);
        $result = $client->get('/mock-endpoint', ['query' => 'param']);
        $this->assertEquals($mockedResponse, $result);

        $client->addMockResponse('/mock-endpoint?query=param', $mockedResponse, HttpRequest::METHOD_DELETE);
        $result = $client->delete('/mock-endpoint', ['query' => 'param']);
        $this->assertEquals($mockedResponse, $result);
    }

    /**
     * Test that non-matching requests go to not found.
     */
    public function testNotFound() {
        $client = new MockHttpClient();
        $mockedResponse = new HttpResponse(200, ['header' => 'value'], '');
        $client->addMockResponse('/mock-endpoint?query=param', $mockedResponse, HttpRequest::METHOD_GET);

        $result = $client->get('/not-mocked');
        $this->assertEquals(404, $result->getStatusCode());

        $result = $client->get('/mock-endpoint');
        $this->assertEquals(404, $result->getStatusCode(), 'Query params must match');

        $result = $client->delete('/mock-endpoint?query=param');
        $this->assertEquals(404, $result->getStatusCode(), 'Http method must match');
    }


    /**
     * Test that non-matching requests go to not found.
     */
    public function testExceptionsThrownWithMock() {
        $client = new MockHttpClient();
        $client->setThrowExceptions(true);

        $this->expectException(HttpResponseException::class);
        $client->get('/not-mocked');
    }

    /**
     * Test that non-matching requests go to not found.
     */
    public function testMiddlewareApplies() {
        $client = new MockHttpClient();

        $client->addMiddleware(function (HttpRequest $request, callable $next): HttpResponse {
            $request->addHeader('request-header', 'foo');
            $response = $next($request);
            $response->addHeader('response-header', 'bar');
            return $response;
        });

        $response = $client->get('/not-mocked');
        $this->assertEquals('bar', $response->getHeader('response-header'));
        $this->assertEquals('foo', $response->getRequest()->getHeader('request-header'));
    }
}
