<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Http\Mocks\MockResponse;
use PHPUnit\Framework\TestCase;

/**
 * Tests for exceptions.
 */
class HttpResponseExceptionTest extends TestCase {

    /**
     * Test json serialize implementation of exceptions.
     */
    public function testJsonSerialize(): void {
        $response = new HttpResponse(501, ["content-type" => "application/json", "Cf-Ray" => "ray-id-12345"], '{"message":"Some error occured."}');
        $response->setRequest(new HttpRequest("POST", "https://somesite.com/some/path"));
        $this->assertEquals([
            "message" => 'Request "POST https://somesite.com/some/path" failed with a response code of 501 and a custom message of "Some error occured."',
            "status" => 501,
            "code" => 501,
            "request" => [
                'url' => 'https://somesite.com/some/path',
                "host" => "somesite.com",
                'method' => 'POST',
            ],
            "response" => [
                'statusCode' => 501,
                'content-type' => 'application/json',
                'body' => '{"message":"Some error occured."}',
                "cf-ray" => "ray-id-12345",
                "cf-cache-status" => null,
            ],
            'class' => 'Garden\Http\HttpResponseException',
        ], $response->asException()->jsonSerialize());
    }

    /**
     * @return void
     */
    public function testHostSiteOverrideSerialize() {
        $request = new HttpRequest("GET", "https://proxy-server.com/some/path", ["Host" => "example.com"]);
        $serialized = $request->jsonSerialize();
        $this->assertEquals([
            "url" => "https://proxy-server.com/some/path",
            "host" => "proxy-server.com",
            "method" => "GET",
        ], $serialized);
    }

    /**
     * Test that we can create exceptions for requests without responses.
     *
     * @return void
     */
    public function testExceptionWithNoRequest() {
        $response = MockResponse::json(["error" => "hi"])->withStatus(500);
        $this->assertEquals([
            'message' => 'Unknown request failed with a response code of 500 and a standard message of "Internal Server Error"',
            'status' => 500,
            'class' => 'Garden\Http\HttpResponseException',
            'code' => 500,
            'response' => [
                'statusCode' => 500,
                'content-type' => 'application/json',
                'body' => '{"error":"hi"}',
                "cf-ray" => null,
                "cf-cache-status" => null,
            ],
            'request' => null,
        ], $response->asException()->jsonSerialize());
    }
}
