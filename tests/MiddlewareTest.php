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
use PHPUnit\Framework\TestCase;

/**
 * Tests for HTTP client middleware.
 */
class MiddlewareTest extends TestCase {
    /**
     * Middleware should pass its call off to the inner request sending.
     */
    public function testInnerSend() {
        $api = new HttpClient('http://example.com/api');
        $api->addMiddleware([MockRequest::class, 'middleware']);

        $response = $api->post('/stuff?c=123', ['foo' => 'bar'], ['X-Baz' => 'bam']);
        $this->assertEquals(MockRequest::class, $response->getBody()['class']);
    }

    /**
     * Middleware should be able to short-circuit the response.
     */
    public function testOverwriteResponse() {
        $api = new HttpClient('http://example.com/api');
        $api->addMiddleware([MockRequest::class, 'echoRequest']);

        $response = $api->post('/stuff?c=123', ['foo' => 'bar'], ['X-Baz' => 'bam']);
        $this->assertEquals(HttpRequest::class, $response->getBody()['class']);
    }

    /**
     * Multiple middleware should chain, calling the last middleware first.
     */
    public function testMiddlewareChaining() {
        $api = new HttpClient('http://example.com/api');
        $api->addMiddleware($this->makeChainMiddleware('a'))
            ->addMiddleware($this->makeChainMiddleware('b'))
            ->addMiddleware($this->makeChainMiddleware('c'));

        $response = $api->post('/');

        $this->assertEquals('abc', $response->getHeader('X-Foo'));
        $this->assertEquals('cba', $response->getRequest()->getHeader('X-Foo'));
    }

    /**
     * Make a simple middleware that appends a value to the X-Foo request and response header.
     *
     * @param string $val The value to append.
     * @return \Closure Returns the middleware.
     */
    protected function makeChainMiddleware(string $val) {
        return function (HttpRequest $request, callable $next) use ($val): HttpResponse {
            $request->setHeader('X-Foo', $request->getHeader('X-Foo').$val);

            $response = $next($request);
            $response->setHeader('X-Foo', $response->getHeader('X-Foo').$val);

            return $response;
        };
    }
}
