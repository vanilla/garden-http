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
 * Request object to hold an expected response for a mock request.
 */
class MockRequest {

    /** @var HttpRequest */
    private $request;

    /** @var HttpResponse */
    private $response;

    /** @var int */
    private $score = 0;

    /**
     * DI.
     *
     * @param HttpRequest $request
     * @param HttpResponse $response
     */
    public function __construct(HttpRequest $request, HttpResponse $response) {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Determine how closely another request matches or mocked one.
     *
     * @param HttpRequest $incomingRequest The incoming request.
     *
     * @return bool True if the incoming request matched.
     */
    public function match(HttpRequest $incomingRequest): bool {
        if ($incomingRequest->getMethod() !== $this->request->getMethod()) {
            // Wrong method. No match.
            $this->setScore(0);
            return false;
        }

        $incomingUrlParts = parse_url($incomingRequest->getUrl());
        $ownUrlParts = parse_url($this->request->getUrl());
        if (($incomingUrlParts['host'] ?? '') !== ($ownUrlParts['host'] ?? '')) {
            // Wrong host. No match.
            $this->setScore(0);
            return false;
        }

        if (isset($incomingUrlParts['path']) && ($incomingUrlParts['path'] !== $ownUrlParts['path'])) {
            // Wrong path. No match.
            $this->setScore(0);
            return false;
        }

        parse_str($incomingUrlParts['query'] ?? '', $incomingQuery);
        parse_str($ownUrlParts['query'] ?? '', $ownQuery);

        $score = 1;

        $compareArrays = function (array $own, array $incoming) use (&$score): bool {
            foreach ($own as $ownParam => $ownValue) {
                if (!isset($incoming[$ownParam]) || $incoming[$ownParam] != $ownValue) {
                    // The mock specified a query, and it wasn't present or did not match the incoming one.
                    // Both request specified the parameter, but it didn't match.
                    $this->setScore(0);
                    return false;
                }

                // we had a match, increment score.
                $score += 1;
            }
            return true;
        };

        if (!$compareArrays($ownQuery, $incomingQuery)) {
            return false;
        }

        $incomingBody = $incomingRequest->getBody();
        $ownBody = $incomingRequest->getBody();
        if (is_array($ownBody) && is_array($incomingBody)) {
            if (!$compareArrays($ownBody, $incomingBody)) {
                return false;
            }
        }

        $this->setScore($score);
        return true;
    }

    /**
     * @return HttpRequest
     */
    public function getRequest(): HttpRequest {
        return $this->request;
    }

    /**
     * @return HttpResponse
     */
    public function getResponse(): HttpResponse {
        return $this->response;
    }

    /**
     * @return int
     */
    public function getScore(): int {
        return $this->score;
    }

    /**
     * @param int $score
     */
    public function setScore(int $score): void {
        $this->score = $score;
    }
}
