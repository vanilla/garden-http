<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Mocks;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;

/**
 * Trait for mocking HTTP responses.
 */
trait MockHttpRequestTrait {

    /** @var MockRequest[] */
    protected $mockRequests = [];

    /**
     * Return the best matching response, if any.
     *
     * @param HttpRequest $request
     * @return HttpResponse
     */
    protected function dispatchMockRequest(HttpRequest $request): HttpResponse {
        $matchedMocks = [];
        foreach ($this->mockRequests as $mockRequest) {
            if ($mockRequest->match($request)) {
                $matchedMocks[] = $mockRequest;
            }
        }

        // Sort the matches in descending order.
        usort($matchedMocks, function (MockRequest $a, MockRequest $b) {
            return $b->getScore() <=> $a->getScore();
        });

        $bestMock = $matchedMocks[0] ?? null;


        if ($bestMock == null) {
            $response = new HttpResponse(404);
        } else {
            $response = $bestMock->getResponse();
        }

        $response->setRequest($request);
        $request->setResponse($response);
        $this->history[] = $request;
        return $response;
    }

    /**
     * Mock multiple requests at once.
     *
     * @example
     * $this->multi([
     *      "/some/url" => ["message" => "this is a response"],
     *      "GET https://url.here/*" => MockResponse::sequence()
     *          ->push("response1")
     *          ->push(new HttpResponse(500, ["headers"], "body"),
     *      "*" => MockResponse::notFound(),
     * ]);
     *
     * @param array<string, HttpResponse|MockResponseSequence|array> $toMock
     *
     * @return $this
     */
    public function mockMulti(array $toMock): self {
        foreach ($toMock as $url => $response) {
            $this->addMockRequest($url, $response);
        }
        return $this;
    }

    /**
     * Add a mocked request/response combo.
     *
     * @param HttpRequest|string $request
     * @param HttpResponse|MockResponseSequence|array $response
     * @return $this
     */
    public function addMockRequest($request, $response) {
        $this->mockRequests[] = new MockRequest($request, $response);
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
        $this->addMockRequest(
            new HttpRequest($method, $uri),
            $response
        );
        return $this;
    }
}
