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
use PHPUnit\Framework\Assert;

/**
 * Handler for mock http requests. Never makes any actual network requests.
 */
class MockHttpHandler implements HttpHandlerInterface {

    use MockHttpRequestTrait;

    /** @var MockHttpHandler|null */
    public static ?MockHttpHandler $mock = null;

    /** @var array<HttpRequest> */
    private array $history = [];

    /**
     * @inheritDoc
     */
    public function send(HttpRequest $request): HttpResponse {
        $response = $this->dispatchMockRequest($request);
        $response->setRequest($request);
        $request->setResponse($response);
        return $response;
    }

    /**
     * Mock all incoming network requests to garden-http.
     *
     * @return MockHttpHandler The mocked handler.
     */
    public static function mock(): MockHttpHandler {
        if (self::$mock === null) {
            self::$mock = new MockHttpHandler();
        }

        return self::$mock;
    }

    /**
     * @return MockHttpHandler|null
     */
    public static function getMock(): ?MockHttpHandler {
        return self::$mock;
    }

    /**
     * Clear the mock and allow normal requests to take place.
     *
     * @return void
     */
    public static function clearMock(): void {
        self::$mock = null;
    }

    /**
     * Reset the mocked requests/responses.
     *
     * @return MockHttpHandler
     */
    public function reset(): MockHttpHandler {
        $this->history = [];
        $this->mockRequests = [];
        self::$mock = new MockHttpHandler();
        return self::$mock;
    }

    /**
     * Assert that no requests were sent.
     *
     * @return void
     */
    public function assertNothingSent(): void {
        $historyIDs = $this->getHistoryIDs();
        Assert::assertEmpty($this->history, "Expected no requests to be sent. Instead received " . count($historyIDs) . ".\n" . implode("\n", $historyIDs));
    }

    /**
     * Assert that a request was sent that matches a callable.
     *
     * @param callable(HttpRequest $request): bool $matcher
     *
     * @return HttpRequest
     */
    public function assertSent(callable $matcher): HttpRequest {
        $matchingRequest = null;
        foreach ($this->history as $request) {
            $result = call_user_func($matcher, $request);
            if ($result) {
                $matchingRequest = $request;
                break;
            }
        }

        $historyIDs = $this->getHistoryIDs();
        if (empty($historyIDs)) {
            Assert::fail("Expected to find a matching request. Instead no requests were sent.");
        }
        Assert::assertInstanceOf(HttpRequest::class, $matchingRequest, "Expected to find a matching request. Instead there were " . count($historyIDs) . " requests that did not match.\n" . implode("\n", $historyIDs));
        return $matchingRequest;
    }

    /**
     * @return string[]
     */
    private function getHistoryIDs(): array {
        $historyIDs = [];
        foreach ($this->history as $historyItem) {
            $historyIDs[] = "{$historyItem->getMethod()} {$historyItem->getUrl()}";
        }
        return $historyIDs;
    }
}
