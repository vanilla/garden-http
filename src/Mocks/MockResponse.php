<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Mocks;

use Garden\Http\HttpResponse;

/**
 * Simplified mock response.
 */
class MockResponse extends HttpResponse {

    /**
     * @return MockResponseSequence
     */
    public static function sequence(): MockResponseSequence {
        return new MockResponseSequence([]);
    }

    /**
     * Mock an empty not found response.
     *
     * @return MockResponse
     */
    public static function notFound(): MockResponse {
        return new MockResponse(404);
    }

    /**
     * Mock an empty success response.
     *
     * @return MockResponse
     */
    public static function success(): MockResponse {
        return new MockResponse(200);
    }

    /**
     * Mock a successful json response.
     *
     * @param mixed $body
     *
     * @return MockResponse
     */
    public static function json($body): MockResponse {
        return new MockResponse(200, ['content-type' => 'application/json'], json_encode($body));
    }
}
