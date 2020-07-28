<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
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

    use MockHttpResponseTrait;

    /** @var MockHttpHandler */
    private $mockHandler;

    /** @var HttpResponse */
    private $currentResponse;

    /**
     * @inheritdoc
     */
    public function __construct(string $baseUrl = '') {
        parent::__construct($baseUrl);
        $this->mockHandler = new MockHttpHandler();
        $this->setHandler($this->mockHandler);
    }

    /**
     * Add a single response to be queued up if a request is created.
     *
     * @param string $uri
     * @param HttpResponse $response
     * @param string $method
     *
     * @return $this
     */
    public function addMockResponse(string $uri, HttpResponse $response, string $method = HttpRequest::METHOD_GET) {
        $this->mockHandler->addMockResponse($uri, $response, $method);
        return $this;
    }
}
