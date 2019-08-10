<?php

/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;

/**
 * An exception that occurs when there is a non 2xx response.
 */
class HttpResponseException extends \Exception {
    /**
     * @var HttpResponse
     */
    private $response;

    /**
     * HttpException constructor.
     *
     * @param HttpResponse $response The response that generated this exception.
     * @param string $message The error message.
     */
    public function __construct(HttpResponse $response, $message = "") {
        parent::__construct($message, $response->getStatusCode(), null);
        $this->response = $response;
    }

    /**
     * Get the request that generated the exception.
     *
     * This is a convenience method that returns the request from the response property.
     *
     * @return HttpRequest
     */
    public function getRequest(): HttpRequest {
        return $this->response->getRequest();
    }

    /**
     * Get the response that generated the exception.
     *
     * @return HttpResponse Returns an HTTP response.
     */
    public function getResponse(): HttpResponse {
        return $this->response;
    }
}
