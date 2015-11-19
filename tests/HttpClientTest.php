<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;

/**
 * Class HttpClientTest
 *
 * @package Garden\Http\Tests
 */
class HttpClientTest extends \PHPUnit_Framework_TestCase {

    /**
     *
     *
     * @return HttpClient
     */
    public function getApi() {
        $api = new HttpClient();
        $api->setBaseUrl('http://garden-http.dev/')
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
     *
     *
     * @param $method The HTTP method to test.
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
     *
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
     *
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
     *
     *
     * @return array
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
