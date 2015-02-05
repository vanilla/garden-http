<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests;

use Garden\Http\HttpClient;

/**
 * Test cases for the README.
 */
class ReadmeTest extends \PHPUnit_Framework_TestCase {
    public function testBasicExample() {
        $api = new HttpClient('http://httpbin.org');
        $api->setDefaultHeader('Content-Type', 'application/json');

        // Get some data from the API.
        $response = $api->get('/get'); // requests off of base url
        if ($response->isSuccessful()) {
            $data = $response->getBody(); // returns array of json decoded data
        }

        $response = $api->post('https://httpbin.org/post', ['foo' => 'bar']);
        if ($response->isResponseClass('2xx')) {
            // Access the response like an array.
            $posted = $response['json']; // should be ['foo' => 'bar']
        }

        if (!$response->isSuccessful()) {
            $this->markTestSkipped();
        }
        $this->assertInternalType('array', $data);
        $this->assertSame(['foo' => 'bar'], $posted);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 404
     */
    public function testExceptionsExample() {
        $api = new HttpClient('https://httpbin.org');
        $api->setThrowExceptions(true);

        try {
            $api->get('/status/404');
        } catch (\Exception $ex) {
            $code = $ex->getCode(); // should be 404
            throw $ex;
        }
    }

    public function testBasicAuthentication() {
        $api = new HttpClient('https://httpbin.org');
        $api->setDefaultOption('auth', ['username', 'password']);

        // This request is made with the default authentication set above.
        $r1 = $api->get('/basic-auth/username/password');

        // This request overrides the basic authentication.
        $r2 = $api->get('/basic-auth/username/password123', [], [], ['auth' => ['username', 'password123']]);

        $this->assertEquals(200, $r1->getStatusCode());
        $this->assertEquals(200, $r2->getStatusCode());
    }
}
