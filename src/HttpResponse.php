<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Representation of an outgoing, server-side response.
 */
class HttpResponse extends HttpMessage implements
    \ArrayAccess,
    \JsonSerializable,
    ResponseInterface
{
    /// Properties ///

    /**
     * @var int|null
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $reasonPhrase;

    /**
     * @var string
     */
    protected $rawBody;

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var array HTTP response codes and messages.
     */
    protected static $reasonPhrases = [
        // Could not resolve host.
        0 => "Could not resolve host",
        // Informational 1xx
        100 => "Continue",
        101 => "Switching Protocols",
        // Successful 2xx
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        // Redirection 3xx
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        306 => "(Unused)",
        307 => "Temporary Redirect",
        // Client Error 4xx
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Request Entity Too Large",
        414 => "Request-URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed",
        418 => 'I\'m a teapot',
        422 => "Unprocessable Entity",
        423 => "Locked",
        // Server Error 5xx
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported",
    ];

    /// Methods ///

    /**
     * Initialize an instance of the {@link HttpResponse} object.
     *
     * @param int|null $status The http response status or null to get the status from the headers.
     * @param array|string $headers An array of response headers or a header string.
     * @param string $rawBody The raw body of the response.
     */
    public function __construct(
        $status = null,
        $headers = "",
        string $rawBody = ""
    ) {
        $this->setHeaders($headers);
        if (isset($status)) {
            $this->setStatus($status);
        } elseif ($this->statusCode === null) {
            $this->setStatus(200);
        }
        $this->rawBody = $rawBody;
    }

    /**
     * Gets the body of the response, decoded according to its content type.
     *
     * @return mixed Returns the http response body, decoded according to its content type.
     */
    public function getBody()
    {
        if (!isset($this->body)) {
            $contentType = $this->getHeader("Content-Type");

            if (
                !is_null($this->rawBody) &&
                stripos($contentType, "application/json") !== false
            ) {
                $this->body = json_decode($this->rawBody, true);
            } else {
                $this->body = $this->rawBody;
            }
        }
        return $this->body;
    }

    /**
     * Set the body of the response.
     *
     * This method will try and keep the raw body in sync with the value set here.
     *
     * @param mixed $body The new body.
     * @return $this
     */
    public function setBody($body)
    {
        if (is_string($body) || is_null($body)) {
            $this->rawBody = $this->body = $body;
        } elseif (
            is_array($body) ||
            is_bool($body) ||
            is_numeric($body) ||
            $body instanceof \JsonSerializable
        ) {
            $this->rawBody = json_encode(
                $body,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );
            $this->body = $body;
        } else {
            $this->rawBody = "";
            $this->body = $body;
        }

        return $this;
    }

    /**
     * Set all of the headers. This will overwrite any existing headers.
     *
     * @param array|string $headers An array or string of headers to set.
     *
     * The array of headers can be in the following form:
     *
     * - ["Header-Name" => "value", ...]
     * - ["Header-Name" => ["lines, ...], ...]
     * - ["Header-Name: value", ...]
     * - Any combination of the above formats.
     *
     * A header string is the the form of the HTTP standard where each Key: Value pair is separated by `\r\n`.
     *
     * @return HttpResponse Returns `$this` for fluent calls.
     */
    public function setHeaders($headers)
    {
        parent::setHeaders($headers);

        if ($statusLine = $this->parseStatusLine($headers)) {
            $this->setStatus($statusLine);
        }

        return $this;
    }

    /**
     * Check if the provided response matches the provided response type.
     *
     * The {@link $class} is a string representation of the HTTP status code, with 'x' used as a wildcard.
     *
     * Class '2xx' = All 200-level responses
     * Class '30x' = All 300-level responses up to 309
     *
     * @param string $class A string representation of the HTTP status code, with 'x' used as a wildcard.
     * @return boolean Returns `true` if the response code matches the {@link $class}, `false` otherwise.
     */
    public function isResponseClass(string $class): bool
    {
        $pattern =
            "`^" . str_ireplace("x", "\d", preg_quote($class, "`")) . '$`';
        $result = preg_match($pattern, $this->statusCode);

        return $result === 1;
    }

    /**
     * Determine if the response was successful.
     *
     * @return bool Returns `true` if the response was a successful 2xx code.
     */
    public function isSuccessful(): bool
    {
        return $this->isResponseClass("2xx");
    }

    /**
     * Get the raw body of the response.
     *
     * @return string The raw body of the response.
     */
    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    /**
     * Set the raw body of the response.
     *
     * @param string $body The new raw body.
     */
    public function setRawBody(string $body)
    {
        $this->rawBody = $body;
        $this->body = null;
        return $this;
    }

    /**
     * Convert this object to a string.
     *
     * @return string Returns the raw body of the response.
     */
    public function __toString(): string
    {
        return $this->rawBody;
    }

    /**
     * Get the HTTP response status line.
     *
     * @return string Returns the status code and reason phrase separated by a space.
     */
    public function getStatus(): string
    {
        return trim("{$this->statusCode} {$this->reasonPhrase}");
    }

    /**
     * Set the status of the response.
     *
     * @param int|string $code Either the 3-digit integer result code or an entire HTTP status line.
     * @param string|null $reasonPhrase The reason phrase to go with the status code.
     * If no reason is given then one will be determined from the status code.
     * @return $this
     */
    public function setStatus($code, $reasonPhrase = null)
    {
        if (
            preg_match(
                "`(?:HTTP/([\d.]+)\s+)?(\d{3})\s*(.*)`i",
                $code,
                $matches
            )
        ) {
            $this->protocolVersion = $matches[1] ?: $this->protocolVersion;
            $code = (int) $matches[2];
            $reasonPhrase = $reasonPhrase ?: $matches[3];
        }

        if (empty($reasonPhrase) && isset(static::$reasonPhrases[$code])) {
            $reasonPhrase = static::$reasonPhrases[$code];
        }
        if (is_numeric($code)) {
            $this->setStatusCode((int) $code);
        }
        $this->setReasonPhrase((string) $reasonPhrase);
        return $this;
    }

    /**
     * Get the code.
     *
     * @return int Returns the code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode ?? 200;
    }

    /**
     * Set the HTTP status code of the response.
     *
     * @param int $statusCode The new status code of the response.
     * @return HttpResponse Returns `$this` for fluent calls.
     */
    public function setStatusCode(int $statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get the HTTP reason phrase of the response.
     *
     * @return string Returns the reason phrase.
     */
    public function getReasonPhrase(): string
    {
        if ($this->statusCode === 0 && !empty($this->rawBody)) {
            // CURL often returns a 0 error code if it failed to connect.
            // This could be for multiple reasons. We need the actual message provided to differentiate between
            // a timeout vs a DNS resolution failure.
            return $this->rawBody;
        } else {
            return $this->reasonPhrase;
        }
    }

    /**
     * Set the reason phrase of the status.
     *
     * @param string $reasonPhrase The new reason phrase.
     * @return HttpResponse Returns `$this` for fluent calls.
     */
    public function setReasonPhrase(string $reasonPhrase)
    {
        $this->reasonPhrase = $reasonPhrase;
        return $this;
    }

    /**
     * Get the reason phrase for a status.
     *
     * @param int $status The status to test.
     * @return string|null Returns a reason phrase or null for an invalid status.
     */
    public static function reasonPhrase(int $status): ?string
    {
        return self::$reasonPhrases[$status] ?? null;
    }

    /**
     * Parse the status line from a header string or array.
     *
     * @param string|array $headers Either a header string or a header array.
     * @return string Returns the status line or an empty string if the first line is not an HTTP status.
     */
    private function parseStatusLine($headers): string
    {
        if (empty($headers)) {
            return "";
        }

        if (is_string($headers)) {
            if (
                preg_match_all(
                    '`(?:^|\n)(HTTP/[^\r]+)\r\n`',
                    $headers,
                    $matches
                )
            ) {
                $firstLine = end($matches[1]);
            } else {
                $firstLine = trim(strstr($headers, "\r\n", true));
            }
        } else {
            $firstLine = (string) reset($headers);
        }

        // Test the status line.
        if (strpos($firstLine, "HTTP/") === 0) {
            return $firstLine;
        }
        return "";
    }

    /**
     * Whether an offset exists.
     *
     * The is one of the methods of {@link \ArrayAccess} used to access this object as an array.
     * When using this object as an array the response body is referenced.
     *
     * @param mixed $offset An offset to check for.
     * @return boolean true on success or false on failure.
     *
     * The return value will be casted to boolean if non-boolean was returned.
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset): bool
    {
        $body = $this->getBody();
        return isset($body[$offset]);
    }

    /**
     * Retrieve a value at a given array offset.
     *
     * The is one of the methods of {@link \ArrayAccess} used to access this object as an array.
     * When using this object as an array the response body is referenced.
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        $this->getBody();
        $result = isset($this->body[$offset]) ? $this->body[$offset] : null;
        return $result;
    }

    /**
     * Set a value at a given array offset.
     *
     * The is one of the methods of {@link \ArrayAccess} used to access this object as an array.
     * When using this object as an array the response body is referenced.
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value) {
        $this->getBody();
        if (is_null($offset)) {
            $this->body[] = $value;
        } else {
            $this->body[$offset] = $value;
        }
    }

    /**
     * Unset an array offset.
     *
     * The is one of the methods of {@link \ArrayAccess} used to access this object as an array.
     * When using this object as an array the response body is referenced.
     *
     * @param mixed $offset The offset to unset.
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset) {
        $this->getBody();
        unset($this->body[$offset]);
    }

    /**
     * Get the request that generated this response.
     *
     * @return HttpRequest|null Returns the request.
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the request that corresponds to this response.
     *
     * @param HttpRequest $request The request that generated this response.
     * @return $this
     */
    public function setRequest(HttpRequest $request = null)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Convert the response into an exception.
     *
     * @return HttpResponseException
     */
    public function asException(): HttpResponseException
    {
        $request = $this->getRequest();
        if ($request !== null) {
            $requestID = "Request \"{$request->getMethod()} {$request->getUrl()}\"";
        } else {
            $requestID = "Unknown request";
        }

        if ($this->isSuccessful()) {
            $responseAction = "returned a response code of {$this->getStatusCode()}";
        } else {
            $responseAction = "failed with a response code of {$this->getStatusCode()}";
        }

        $body = $this->getBody();
        if (
            is_array($body) &&
            isset($body["message"]) &&
            is_string($body["message"])
        ) {
            $responseMessage = "and a custom message of \"{$body["message"]}\"";
        } else {
            $responseMessage = "and a standard message of \"{$this->getReasonPhrase()}\"";
        }

        $message = implode(" ", [
            $requestID,
            $responseAction,
            $responseMessage,
        ]);

        return new HttpResponseException($this, $message);
    }

    /**
     * Basic JSON implementation.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            "statusCode" => $this->getStatusCode(),
            "content-type" => $this->getHeader("content-type") ?: null,
            "request" => $this->getRequest(),
            "body" => $this->getRawBody(),
            "cf-ray" => $this->getHeader("cf-ray") ?: null,
            "cf-cache-status" => $this->getHeader("cf-cache-status") ?: null,
        ];
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = "")
    {
        $cloned = clone $this;
        $cloned->setStatus($code);
        return $cloned;
    }
}
