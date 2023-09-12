<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests\Mocks;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Http\HttpResponseException;
use Garden\Http\Mocks\MockHttpClient;
use Garden\Http\Mocks\MockHttpHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the mock http client.
 */
class MockHttpClientTest extends TestCase {

    /**
     * @return void
     */
    public function tearDown(): void {
        parent::tearDown();
        MockHttpHandler::clearMock();
    }

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
     * Test `POST` mock responses.
     */
    public function testPostMockedResponseWithBodyRequest() {
        $bodyRequest = serialize(['partOne' => 1, 'partTwo' => 2]);
        $desiredResponseValue = "That's what I want back.";

        $client = new MockHttpClient();
        $mockedResponse = new HttpResponse(200, ['header' => 'value'], $desiredResponseValue);
        $client->addMockResponse('/mock-endpoint?query=param', $mockedResponse, HttpRequest::METHOD_POST, $bodyRequest);
        $result = $client->post('/mock-endpoint?query=param', $bodyRequest);
        $actualResponseValue = $result->getRawBody();

        $this->assertEquals($desiredResponseValue, $actualResponseValue);
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

    /**
     * Test score rankings with multiple similar requests.
     */
    public function testMatchingScore() {
        $client = new MockHttpClient();
        $client->addMockRequest(
            new HttpRequest("GET", "/mock-endpoint"),
            new HttpResponse(200, ['header' => 'value'], 'just url')
        );
        $client->addMockRequest(
            new HttpRequest("GET", "/mock-endpoint?queryParam=foo"),
            new HttpResponse(200, ['header' => 'value'], 'with query')
        );
        $client->addMockRequest(
            new HttpRequest("POST", "/mock-endpoint?queryParam=foo", ['bodyParam' => 'foo']),
            new HttpResponse(200, ['header' => 'value'], 'with query and body')
        );

        $this->assertEquals("just url", $client->get("/mock-endpoint")->getBody());
        $this->assertEquals("with query", $client->get("/mock-endpoint?queryParam=foo")->getBody());
        $this->assertEquals("just url", $client->get("/mock-endpoint?queryParam=bar")->getBody());
        $this->assertEquals(404, $client->get("/other-endpoint?queryParam=bar")->getStatusCode());
        $this->assertEquals("with query and body", $client->post("/mock-endpoint?queryParam=foo", ["bodyParam" => "foo"])->getBody());
    }

    /**
     * @return void
     */
    public function testMockingWildcards() {
        $client = new HttpClient();
        $mock = MockHttpHandler::mock();
        $mock->addMockRequest(new HttpRequest("GET", "/some-url"), new HttpResponse(200));
        $mock->addMockRequest(new HttpRequest("GET", "https://some-domain.com/some-url"), new HttpResponse(201));
        $mock->addMockRequest(new HttpRequest("GET", "https://other-domain.com/some-url"), new HttpResponse(202));
        $mock->addMockRequest(new HttpRequest("GET", "https://other-domain.com/*"), new HttpResponse(202));

        $mock->assertNothingSent();
        $this->assertEquals(200, $client->get("/some-url")->getStatusCode());
        $this->assertEquals(201, $client->get("https://some-domain.com/some-url")->getStatusCode());
        $this->assertEquals(202, $client->get("https://other-domain.com/test")->getStatusCode());

        $request = $mock->assertSent(function (HttpRequest $request) {
            return $request->getUri()->getPath() === "/test" && $request->getUri()->getHost() === "other-domain.com";
        });
        $this->assertEquals("/test", $request->getUri()->getPath());
        $mock->assertSent(function (HttpRequest $request) {
            return $request->getUri()->getPath() === "/some-url" && $request->getUri()->getHost() === "some-domain.com";
        });
        $mock->reset();
        $mock->assertNothingSent();
    }
}
