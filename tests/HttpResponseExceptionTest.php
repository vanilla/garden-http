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
        $response = new HttpResponse(501, ["content-type" => "application/json"], '{"message":"Some error occured."}');
        $response->setRequest(new HttpRequest("POST", "/some/path"));
        $this->assertEquals([
            "message" => 'Request "POST /some/path" failed with a response code of 501 and a custom message of "Some error occured."',
            "status" => 501,
            "code" => 501,
            "request" => [
                'url' => '/some/path',
                'method' => 'POST',
            ],
            "response" => [
                'statusCode' => 501,
                'content-type' => 'application/json',
                'body' => '{"message":"Some error occured."}',
            ],
            'class' => 'Garden\Http\HttpResponseException',
        ], $response->asException()->jsonSerialize());
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
            ],
            'request' => null,
        ], $response->asException()->jsonSerialize());
    }
}
