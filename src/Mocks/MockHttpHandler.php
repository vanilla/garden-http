<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Mocks;

use Garden\Http\HttpHandlerInterface;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;

/**
 * Handler for mock http requests. Never makes any actual network requests.
 */
class MockHttpHandler implements HttpHandlerInterface {

    use MockHttpResponseTrait;

    public function send(HttpRequest $request): HttpResponse {
        $key = $this->makeMockResponseKey($request->getUrl(), $request->getMethod());
        $response = $this->mockedResponses[$key] ?? new HttpResponse(404);
        $response->setRequest($request);
        return $response;
    }
}
