<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Mocks;

use Garden\Http\HttpResponse;

/**
 * Holds onto an expected sequence of responses.
 */
class MockResponseSequence {

    /** @var HttpResponse[] */
    private array $responseQueue = [];

    /**
     * @param HttpResponse[] $responseQueue
     */
    public function __construct(array $responseQueue) {
        $this->responseQueue = $responseQueue;
    }

    /**
     * @param array|string|HttpResponse $response
     * @return $this
     */
    public function push($response): self {
        if (!$response instanceof HttpResponse) {
            $response = MockResponse::json($response);
        }
        $this->responseQueue[] = $response;

        return $this;
    }

    /**
     * Consume the next request from the queue.
     *
     * @return HttpResponse|null
     */
    public function take(): ?HttpResponse {
        $response = array_shift($this->responseQueue);

        return $response;
    }
}
