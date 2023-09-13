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

    /** @var HttpResponse|MockResponseSequence */
    private $response;

    /** @var int */
    private $score = 0;

    /**
     * DI.
     *
     * @param HttpRequest|string $request
     * @param HttpResponse|string|array|null|callable(HttpRequest): HttpResponse $response
     */
    public function __construct($request, $response = null) {
        if ($request === "*") {
            $request = new HttpRequest(HttpRequest::METHOD_GET, "https://*/*");
        }
        if (is_string($request)) {
            $pieces = explode(" ", $request);
            $method = count($pieces) === 1 ? HttpRequest::METHOD_GET : $pieces[0];
            $url = count($pieces) === 1 ? $pieces[0] : $pieces[1];

            $request = new HttpRequest($method, $url);
        }

        $ownUrlParts = parse_url($request->getUrl());
        if (empty($ownUrlParts['host'])) {
            // Add a wildcard.
            $request->setUrl("https://*" . $request->getUrl());
        }

        $response = $response ?? MockResponse::success();

        if (!is_callable($response) && !$response instanceof MockResponseSequence && !$response instanceof HttpResponse) {
            $response = MockResponse::json($response);
        }

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
        $score = 0;
        if ($incomingRequest->getMethod() !== $this->request->getMethod()) {
            // Wrong method. No match.
            $this->setScore(0);

            return false;
        }

        $incomingUrlParts = parse_url($incomingRequest->getUrl());
        $ownUrlParts = parse_url($this->request->getUrl());
        $compareUrls = function (string $own, string $incoming) use (&$score): bool {
            if (str_contains($own, "*") && fnmatch($own, $incoming) || $incoming == "" && ($own === "*" || $own === "/*")) {
                $score += 1;

                return true;
            } elseif ($own == $incoming) {
                $score += 2;

                return true;
            } else {
                return false;
            }
        };

        if (!$compareUrls($ownUrlParts['host'] ?? "", $incomingUrlParts['host'] ?? "")) {
            // Wrong host. No match.
            $this->setScore(0);

            return false;
        }

        if (isset($ownUrlParts['path']) && !$compareUrls($ownUrlParts['path'] ?? "", $incomingUrlParts['path'] ?? "")) {
            // Wrong path. No match.
            $this->setScore(0);

            return false;
        }

        parse_str($incomingUrlParts['query'] ?? '', $incomingQuery);
        parse_str($ownUrlParts['query'] ?? '', $ownQuery);

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
     * Get the expected response from the mock.
     *
     * @param HttpRequest $forRequest The request dispatched.
     *
     * @return HttpResponse
     */
    public function getResponse(HttpRequest $forRequest): HttpResponse {
        $response = $this->response;
        if (is_callable($response)) {
            return call_user_func($response, $forRequest);
        } elseif ($response instanceof MockResponseSequence) {
            return $response->take() ?? MockResponse::notFound();
        } else {
            return $this->response;
        }
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
