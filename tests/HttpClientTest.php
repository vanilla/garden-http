<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Http\HttpResponseException;
use PHPUnit\Framework\TestCase;

/**
 * Contains tests against the {@link HttpClient} class.
 *
 * Run this in a separate process before executing this test suite:
 *
 * php -S 0.0.0.0:8091 ./tests/test-server.php
 */
class HttpClientTest extends TestCase
{
    /**
     * Get the API that will be used to make test calls.
     *
     * @return HttpClient Returns the test {@link HttpClient}.
     */
    public function getApi()
    {
        $api = new HttpClient();
        $api->setBaseUrl("http://0.0.0.0:8091/")
            ->setDefaultHeader(
                "Referer",
                basename(str_replace("\\", "/", __CLASS__))
            )
            ->setDefaultHeader("Content-Type", "application/json")
            ->setDefaultHeader("Accept", "application/json")
            ->setThrowExceptions(true);
        return $api;
    }

    /**
     * A simple test to see if we have access to the server.
     */
    public function testAccess()
    {
        $api = $this->getApi();

        $response = $api->get("/echo");
        $data = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("bar", $data["foo"]);
    }

    /**
     * Test that setting an HTTP method name sends a request of that type properly.
     *
     * @param string $method The HTTP method to test.
     * @dataProvider provideMethods
     * @throws \Exception Throws an exception when the returned data is a string.
     */
    public function testHttpMethodNames($method)
    {
        $api = $this->getApi()->setThrowExceptions(false);
        $methodName = strtolower($method);

        /* @var HttpResponse $r */
        $r = $api->$methodName("/echo", ["foo" => "bar"]);
        $data = $r->getBody();

        if (is_string($data)) {
            throw new \Exception("Invalid response: $data.", 500);
        }

        $this->assertEquals(200, $r->getStatusCode());
        if ($method === HttpRequest::METHOD_HEAD) {
            $this->assertNull($data);
        } else {
            $this->assertEquals($method, $data["method"]);
        }
    }

    /**
     * Test basic HTTP authorization.
     */
    public function testBasicAuth()
    {
        $api = $this->getApi();
        $api->setDefaultOption("auth", ["foo", "bar"]);

        $response = $api->get("/basic-protected/foo/bar");
        $data = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("You are in.", $data["message"]);
    }

    /**
     * Test basic authentication when the wrong username is supplied.
     */
    public function testBasicAuthWrongUsername()
    {
        $this->expectException(HttpResponseException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage("Invalid username.");
        $api = $this->getApi();
        $api->setDefaultOption("auth", ["foo", "bar"]);

        $response = $api->get("/basic-protected/fooz/bar");
        $data = $response->getBody();
    }

    /**
     * Test that the basic getters and setters work.
     */
    public function testBasicPropertyAccess()
    {
        $api = $this->getApi();

        $baseUrl = "https://localhost";
        $this->assertNotSame($baseUrl, $api->getBaseUrl());
        $api->setBaseUrl($baseUrl);
        $this->assertSame($baseUrl, $api->getBaseUrl());

        $this->assertNotSame("B", $api->getDefaultHeader("A"));
        $api->setDefaultHeader("A", "B");
        $this->assertSame("B", $api->getDefaultHeader("A"));

        $headers = ["Foo" => "bar", "Boo" => "baz", "a" => "c"];
        $this->assertNotSame($headers, $api->getDefaultHeaders());
        $api->setDefaultHeaders($headers);
        $this->assertSame($headers, $api->getDefaultHeaders());

        $this->assertNotSame("B", $api->getDefaultOption("A"));
        $api->setDefaultOption("A", "B");
        $this->assertSame("B", $api->getDefaultOption("A"));

        $options = ["Foo" => "bar", "Boo" => "baz", "a" => "c"];
        $this->assertNotSame($options, $api->getDefaultOptions());
        $api->setDefaultOptions($options);
        $this->assertSame($options, $api->getDefaultOptions());

        $throw = !$api->getThrowExceptions();
        $this->assertNotSame($throw, $api->getThrowExceptions());
        $api->setThrowExceptions($throw);
        $this->assertSame($throw, $api->getThrowExceptions());
    }

    /**
     * Test basic authentication when the correct username is supplied.
     */
    public function testBasicWrongPassword()
    {
        $this->expectException(HttpResponseException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage("Invalid password.");
        $api = $this->getApi();
        $api->setDefaultOption("auth", ["foo", "bar"]);

        $response = $api->get("/basic-protected/foo/baz");
        $data = $response->getBody();
    }

    /**
     * Test an API call that returns an error response rather than throw an exception.
     */
    public function testErrorResponse()
    {
        $api = $this->getApi()->setThrowExceptions(false);
        $api->setDefaultOption("auth", ["foo", "bar"]);

        $response = $api->get("/basic-protected/fooz/bar");
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testResponseInException()
    {
        $api = $this->getApi();
        $api->setDefaultOption("auth", ["foo", "bar"]);

        try {
            $response = $api->get("/basic-protected/fooz/bar");
        } catch (HttpResponseException $ex) {
            $this->assertInstanceOf(HttpResponse::class, $ex->getResponse());
            $this->assertInstanceOf(HttpRequest::class, $ex->getRequest());
            $this->assertSame(
                $ex->getResponse()->getRequest(),
                $ex->getRequest()
            );
            $this->assertSame(
                $ex->getCode(),
                $ex->getResponse()->getStatusCode()
            );
        }
    }

    /**
     * Provide all of the default HTTP methods.
     *
     * @return array Returns a data provider array of HTTP methods.
     */
    public function provideMethods()
    {
        $arr = [
            HttpRequest::METHOD_GET => [HttpRequest::METHOD_GET],
            HttpRequest::METHOD_HEAD => [HttpRequest::METHOD_HEAD],
            HttpRequest::METHOD_OPTIONS => [HttpRequest::METHOD_OPTIONS],
            HttpRequest::METHOD_PATCH => [HttpRequest::METHOD_PATCH],
            HttpRequest::METHOD_POST => [HttpRequest::METHOD_POST],
            HttpRequest::METHOD_PUT => [HttpRequest::METHOD_PUT],
            HttpRequest::METHOD_DELETE => [HttpRequest::METHOD_DELETE],
        ];

        return $arr;
    }

    /**
     * Tests the default behavior where a cURL handle should not be reused between requests by default
     */
    public function testConnectionIsNotReusedByDefault()
    {
        $api = $this->getApi();

        $this->assertNotEquals(
            $this->getClientPort($api),
            $this->getClientPort($api)
        );
    }

    /**
     * Tests that when Keep-Alive is requested through header, the cURL handle is being reused
     */
    public function testConnectionIsReusedWhenKeepAliveRequested()
    {
        $this->markTestSkipped(
            "We don't have a local way of running a keepalive server currently"
        );
        $api = new $this->getApi();
        $headers = ["Connection" => "keep-alive"];

        $this->assertEquals(
            $this->getClientPort($api, $headers),
            $this->getClientPort($api, $headers)
        );
    }

    /**
     * Tests that when Keep-Alive is used, if a subsequent request doesn't request it, then the connection is reset
     */
    public function testConnectionIsReusedCorrectlyInMixedRequestsWithNoExplicitHeader()
    {
        $api = $this->getApi();

        $this->assertNotEquals(
            $this->getClientPort($api, ["Connection" => "keep-alive"]),
            $this->getClientPort($api)
        );
    }

    /**
     * Tests that when Keep-Alive is used, if a subsequent request explicitly requests to close the connection, then
     * the connection is reset
     */
    public function testConnectionIsReusedCorrectlyInMixedRequestsWithExplicitClose()
    {
        $api = $this->getApi();

        $this->assertNotEquals(
            $this->getClientPort($api, ["Connection" => "keep-alive"]),
            $this->getClientPort($api, ["Connection" => "close"])
        );
    }

    /**
     * Checks with the echo server what connecting port we have used. This is used to figure out whether the connection
     * has been reused or not.
     *
     * @param HttpClient $client the HTTP client to run the request with
     * @param array $headers additional headers to add to the request
     * @return string of the port number as reported by nginx
     */
    protected function getClientPort(HttpClient $client, array $headers = [])
    {
        $response = $client->request("GET", "echo", null, $headers);
        $body = $response->getBody();
        return $body["phpServer"]["REMOTE_PORT"];
    }
}
