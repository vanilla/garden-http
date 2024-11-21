<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests;

require_once __DIR__ . "/Fixtures/HmacMiddleware.php";

use Garden\Http\HttpClient;
use Garden\Http\HttpResponseException;
use Garden\Http\Tests\Fixtures\HmacMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the README.
 */
class ReadmeTest extends TestCase
{
    public function testBasicExample()
    {
        $api = new HttpClient("http://httpbin.org");
        $api->setThrowExceptions(true);
        $api->setDefaultHeader("Content-Type", "application/json");

        // Get some data from the API.
        $response = $api->get("/get"); // requests off of base url
        $data = $response->getBody(); // returns array of json decoded data

        $response = $api->post("https://httpbin.org/post", ["foo" => "bar"]);
        // Access the response like an array.
        $posted = $response["json"]; // should be ['foo' => 'bar']

        if (!$response->isSuccessful()) {
            $this->markTestSkipped();
        }
        $this->assertIsArray($data);
        $this->assertSame(["foo" => "bar"], $posted);
    }

    /**
     * Test that exceptions can be thrown.
     */
    public function testExceptionsExample()
    {
        $this->expectException(HttpResponseException::class);
        $this->expectExceptionCode(404);
        $api = new HttpClient("https://httpbin.org");
        $api->setThrowExceptions(true);

        try {
            $api->get("/status/404");
        } catch (\Exception $ex) {
            $code = $ex->getCode(); // should be 404
            throw $ex;
        }
    }

    public function testBasicAuthentication()
    {
        $api = new HttpClient("https://httpbin.org");
        $api->setDefaultOption("auth", ["username", "password123"]);

        // This request is made with the default authentication set above.
        $r1 = $api->get("/basic-auth/username/password123");

        // This request overrides the basic authentication.
        $r2 = $api->get(
            "/basic-auth/username/password",
            [],
            [],
            ["auth" => ["username", "password"]]
        );

        $this->assertEquals(200, $r1->getStatusCode());
        $this->assertEquals(200, $r2->getStatusCode());
    }

    public function testRequestMiddleware()
    {
        $api = new HttpClient("https://httpbin.org");
        $middleware = new HmacMiddleware("key", "password");
        $api->addMiddleware($middleware);

        $r = $api->get("/get");

        $this->assertNotEmpty($r["headers"]["Authorization"]);
    }
}
