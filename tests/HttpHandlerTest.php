<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\Tests\Fixtures\EchoHandler;
use PHPUnit\Framework\TestCase;

class HttpHandlerTest extends TestCase {
    /**
     * Test sending a request through a handler.
     */
    public function testRequestWithHandler() {
        $request = new HttpRequest('GET', 'http://example.com', ['foo' => 'bar']);
        $handler = new EchoHandler();

        $response = $handler->send($request);

        $this->assertSame($request->getBody(), $response->getBody()['body']);
    }

    /**
     * Test a handler with an HTTP client.
     */
    public function testClientWithHandler() {
        $api = new HttpClient('https://example.com', new EchoHandler());

        $response = $api->post('', ['foo' => 'bar']);

        $this->assertSame($response->getRequest()->getBody(), $response->getBody()['body']);
    }
}
