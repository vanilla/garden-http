<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
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
}
