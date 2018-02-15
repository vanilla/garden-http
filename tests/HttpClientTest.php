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


/**
 * Contains tests against the {@link HttpClient} class.
 */
class HttpClientTest extends \PHPUnit_Framework_TestCase {
    /**
     * Get the API that will be used to make test calls.
     *
     * @return HttpClient Returns the test {@link HttpClient}.
     */
    public function getApi() {
        $api = new HttpClient();
        $api->setBaseUrl('http://garden-http.dev:8080/')
            ->setDefaultHeader('Referer', basename(str_replace('\\', '/', __CLASS__)))
            ->setDefaultHeader('Content-Type', 'application/json')
            ->setThrowExceptions(true);
        return $api;
    }

    /**
     * A simple test to see if we have access to the server.
     */
    public function testAccess() {
        $api = $this->getApi();

        $response = $api->get('/echo.json');
        $data = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('bar', $data['foo']);
    }

    /**
     * Test that setting an HTTP method name sends a request of that type properly.
     *
     * @param string $method The HTTP method to test.
     * @dataProvider provideMethods
     * @throws \Exception Throws an exception when the returned data is a string.
     */
    public function testHttpMethodNames($method) {
        $api = $this->getApi()->setThrowExceptions(false);
        $methodName = strtolower($method);

        /* @var HttpResponse $r */
        $r = $api->$methodName('/echo.json', ['foo' => 'bar']);
        $data = $r->getBody();

        if (is_string($data)) {
            throw new \Exception("Invalid response: $data.", 500);
        }

        $this->assertEquals(200, $r->getStatusCode());
        if ($method === HttpRequest::METHOD_HEAD) {
            $this->assertNull($data);
        } else {
            $this->assertEquals($method, $data['method']);
        }
    }

    /**
     * Test basic HTTP authorization.
     */
    public function testBasicAuth() {
        $api = $this->getApi();
        $api->setDefaultOption('auth', ['foo', 'bar']);

        $response = $api->get('/basic-protected/foo/bar.json');
        $data = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('You are in.', $data['message']);
    }

    /**
     * Test basic authentication when the wrong username is supplied.
     *
     * @expectedException \Exception
     * @expectedExceptionCode 401
     * @expectedExceptionMessage Invalid username.
     */
    public function testBasicAuthWrongUsername() {
        $api = $this->getApi();
        $api->setDefaultOption('auth', ['foo', 'bar']);

        $response = $api->get('/basic-protected/fooz/bar.json');
        $data = $response->getBody();
    }

    /**
     * Test that the basic getters and setters work.
     */
    public function testBasicPropertyAccess() {
        $api = $this->getApi();

        $baseUrl = 'https://localhost';
        $this->assertNotSame($baseUrl, $api->getBaseUrl());
        $api->setBaseUrl($baseUrl);
        $this->assertSame($baseUrl, $api->getBaseUrl());

        $this->assertNotSame('B', $api->getDefaultHeader('A'));
        $api->setDefaultHeader('A', 'B');
        $this->assertSame('B', $api->getDefaultHeader('A'));

        $headers = ['Foo' => 'bar', 'Boo' => 'baz', 'a' => 'c'];
        $this->assertNotSame($headers, $api->getDefaultHeaders());
        $api->setDefaultHeaders($headers);
        $this->assertSame($headers, $api->getDefaultHeaders());

        $this->assertNotSame('B', $api->getDefaultOption('A'));
        $api->setDefaultOption('A', 'B');
        $this->assertSame('B', $api->getDefaultOption('A'));

        $options = ['Foo' => 'bar', 'Boo' => 'baz', 'a' => 'c'];
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
     *
     * @expectedException \Exception
     * @expectedExceptionCode 401
     * @expectedExceptionMessage Invalid password.
     */
    public function testBasicWrongPassword() {
        $api = $this->getApi();
        $api->setDefaultOption('auth', ['foo', 'bar']);

        $response = $api->get('/basic-protected/foo/baz.json');
        $data = $response->getBody();
    }

    /**
     * Test an API call that returns an error response rather than throw an exception.
     */
    public function testErrorResponse() {
        $api = $this->getApi()->setThrowExceptions(false);
        $api->setDefaultOption('auth', ['foo', 'bar']);

        $response = $api->get('/basic-protected/fooz/bar.json');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testResponseInException() {
        $api = $this->getApi();
        $api->setDefaultOption('auth', ['foo', 'bar']);

        try {
            $response = $api->get('/basic-protected/fooz/bar.json');
        } catch (HttpResponseException $ex) {
            $this->assertInstanceOf(HttpResponse::class, $ex->getResponse());
            $this->assertInstanceOf(HttpRequest::class, $ex->getRequest());
            $this->assertSame($ex->getResponse()->getRequest(), $ex->getRequest());
            $this->assertSame($ex->getCode(), $ex->getResponse()->getStatusCode());
        }
    }


    /**
     * Provide all of the default HTTP methods.
     *
     * @return array Returns a data provider array of HTTP methods.
     */
    public function provideMethods() {
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
}
