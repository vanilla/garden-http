<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
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

    use MockHttpRequestTrait;

    /**
     * @inheritDoc
     */
    public function send(HttpRequest $request): HttpResponse {
        $response = $this->dispatchMockRequest($request);
        $response->setRequest($request);
        return $response;
    }
}
