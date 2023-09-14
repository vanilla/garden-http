<?php

/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;

use Garden\Utils\ContextException;
use Monolog\Utils;

/**
 * An exception that occurs when there is a non 2xx response.
 */
class HttpResponseException extends ContextException implements \JsonSerializable {
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
        $responseJson = $response->jsonSerialize();
        unset($responseJson['request']);
        $request = $response->getRequest();
        $context = [
            "response" => $responseJson,
            "request" => $request === null ? null : $request->jsonSerialize()
        ];
        parent::__construct($message, $response->getStatusCode(), $context);
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

    /**
     * @return array
     */
    public function jsonSerialize(): array {
        $result = [
            'message' => $this->getMessage(),
            'status' => (int) $this->getHttpStatusCode(),
            'class' => get_class($this),
            'code' => (int) $this->getCode(),
        ] + $this->getContext();
        return $result;
    }
}
