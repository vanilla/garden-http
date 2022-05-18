<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Mocks;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;

/**
 * Mock HTTP client for testing. Does send actual HTTP requests.
 */
class MockHttpClient extends HttpClient {

    /** @var MockHttpHandler */
    private $mockHandler;

    /**
     * @inheritdoc
     */
    public function __construct(string $baseUrl = '') {
        parent::__construct($baseUrl);
        $this->mockHandler = new MockHttpHandler();
        $this->setHandler($this->mockHandler);
    }

    /**
     * Add a mocked request/response combo.
     *
     * @param HttpRequest $request
     * @param HttpResponse $response
     *
     * @return $this
     */
    public function addMockRequest(HttpRequest $request, HttpResponse $response) {
        $this->mockHandler->addMockRequest($request, $response);
        return $this;
    }

    /**
     * Add a single response to be queued up if a request is created.
     *
     * @param string $uri
     * @param HttpResponse $response
     * @param string $method
     *
     * @return $this
     * @deprecated Use addMockRequest()
     */
    public function addMockResponse(
        string $uri,
        HttpResponse $response,
        string $method = HttpRequest::METHOD_GET
    ) {
        $this->mockHandler->addMockResponse($uri, $response, $method);
        return $this;
    }
}
