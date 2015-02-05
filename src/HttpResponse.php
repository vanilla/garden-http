<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;


class HttpResponse extends HttpMessage implements \ArrayAccess {
    /// Properties ///

    protected $statusCode;

    protected $reasonPhrase;

    protected $rawBody;

    /**
     * @var array HTTP response codes and messages.
     */
    protected static $reasonPhrases = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );

    /// Methods ///

    /**
     * Initialize an instance of the {@link HttpResponse} object.
     *
     * @param int $status The http response status.
     * @param mixed $headers An array of response headers.
     * @param string $rawBody The raw body of the response.
     */
    public function __construct($status = 0, $headers = '', $rawBody = '') {
        $this->setStatus($status);
        $this->setHeaders($headers);
        $this->rawBody = $rawBody;
    }

    /**
     * Gets the body of the response, decoded according to its content type.
     *
     * @return mixed Returns the http response body, decoded according to its content type.
     */
    public function getBody() {
        if (!isset($this->body)) {
            $contentType = $this->getHeader('Content-Type');

            if (stripos($contentType, 'application/json') !== false) {
                $this->body = json_decode($this->rawBody, true);
            } else {
                $this->body = $this->rawBody;
            }
        }
        return $this->body;
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
    public function isResponseClass($class) {
        $pattern = '`^'.str_ireplace('x', '\d', preg_quote($class, '`')).'$`';
        $result = preg_match($pattern, $this->statusCode);

        return $result;
    }

    /**
     * Determine if the response was successful.
     *
     * @return bool Returns `true` if the response was a successful 2xx code.
     */
    public function isSuccessful() {
        return $this->isResponseClass('2xx');
    }

    /**
     * Get the raw body of the response.

     * @param string|null Set a new raw response body.
     * @return string The raw body of the response.
     */
    public function getRawBody($value = null) {
        if ($value !== null) {
            $this->rawBody = $value;
        }
        return $this->rawBody;
    }

    /**
     * Convert this object to the string.
     *
     * @return string Returns the raw body of the response.
     */
    public function __toString() {
        return $this->rawBody;
    }

    public function getStatus() {
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
    public function setStatus($code, $reasonPhrase = null) {
        if (preg_match('`(?:HTTP/([\d.]+)\s+)?(\d{3})\s*(.*)`i', $code, $matches)) {
            $this->protocolVersion = $matches[1] ?: $this->protocolVersion;
            $code = $matches[2];
            $reasonPhrase = $reasonPhrase ?: $matches[3];
        }

        if (!$reasonPhrase && isset(static::$reasonPhrases[$code])) {
            $reasonPhrase = static::$reasonPhrases[$code];
        }
        $this->setStatusCode($code);
        $this->setReasonPhrase((string)$reasonPhrase);
        return $this;
    }

    /**
     * Get the code.
     *
     * @return int Returns the code.
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * Set the code.
     *
     * @param int $statusCode
     * @return HttpResponse Returns `$this` for fluent calls.
     */
    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get the reasonPhrase.
     *
     * @return mixed Returns the reasonPhrase.
     */
    public function getReasonPhrase() {
        return $this->reasonPhrase;
    }

    /**
     * Set the reasonPhrase.
     *
     * @param mixed $reasonPhrase
     * @return HttpResponse Returns `$this` for fluent calls.
     */
    public function setReasonPhrase($reasonPhrase) {
        $this->reasonPhrase = $reasonPhrase;
        return $this;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset) {
        $body = $this->getBody();
        return isset($body[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset) {
        $this->getBody();
        return isset($this->body[$offset]) ? $this->body[$offset] : null;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value) {
        $this->getBody();
        if (is_null($offset)) {
            $this->body[] = $value;
        } else {
            $this->body[$offset] = $value;
        }

    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset) {
        $this->getBody();
        unset($this->body[$offset]);
    }
}
