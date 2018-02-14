<?php

/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;

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
     * Get the response that generated the exception.
     *
     * @return HttpResponse Returns an HTTP response.
     */
    public function getResponse(): HttpResponse {
        return $this->response;
    }
}
