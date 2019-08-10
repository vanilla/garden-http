<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests\Fixtures;

use Garden\Http\HttpHandlerInterface;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;

/**
 * A mock request that doesn't execute against cURL for testing.
 */
class MockRequest extends HttpRequest {
    /**
     * Respond to the request by echoing its input as output.
     *
     * @return HttpResponse Returns an response.
     */
    public function send(HttpHandlerInterface $handler = null): HttpResponse {
        return static::echoRequest($this);
    }

    /**
     * Make a response that echos out the request.
     *
     * @param HttpRequest $request The request to echo.
     * @return HttpResponse Returns the echo'd response.
     */
    public static function echoRequest(HttpRequest $request): HttpResponse {
        $parsed = parse_url($request->getUrl());

        $result = [
            'method' => $request->getMethod(),
            'host' => $parsed['host'],
            'path' => $parsed['path'] ?? '/',
            'port' => $parsed['port'] ?? 80,
            'headers' => array_map(function ($lines) {
                return implode(', ', $lines);
            }, $request->getHeaders()),
            'query' => $parsed['query'] ?? '',
            'body' => $request->getBody(),
            'class' => get_class($request),
        ];

        $response = new HttpResponse(200, ['Content-Type' => 'application/json'], json_encode($result));
        $response->setRequest($request);

        return $response;
    }

    /**
     * A middleware function that can be used to convert requests into mock requests on HTTP clients.
     *
     * @param HttpRequest $request The request being sent.
     * @param callable $next The next middleware wrapper in the chain.
     * @return HttpResponse The final response.
     */
    public static function middleware(HttpRequest $request, callable $next): HttpResponse {
        $mock = new MockRequest($request->getMethod(), $request->getUrl(), $request->getBody(), $request->getHeaders(), $request->getOptions());

        $response = $next($mock);
        return $response;
    }
}
