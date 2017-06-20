<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;


/**
 * HTTP messages consist of requests from a client to a server and responses from a server to a client.
 *
 * This is the base class for both the {@link HttpRequest} class and the {@link HttpResponse} class.
 */
abstract class HttpMessage {
    /// Properties ///

    /**
     * @var string|array The body of the message.
     */
    protected $body;

    /**
     * @var string The HTTP protocol version of the request.
     */
    protected $protocolVersion = '1.1';

    /**
     * @var array An array of headers stored by lower cased header name.
     */
    private $headers = [];

    /**
     * @var array An array of header names as specified by the various header methods.
     */
    private $headerNames = [];

    /// Methods ///

    /**
     * Adds a new header with the given value.
     *
     * If an existing header exists with the given name then the value will be appended to the end of the list.
     *
     * @param string $name The name of the header.
     * @param string $value The value of the header.
     * @return HttpMessage $this Returns `$this` for fluent calls.
     */
    public function addHeader($name, $value) {
        $key = strtolower($name);
        $this->headerNames[$key] = $name;
        $this->headers[$key][] = $value;

        return $this;
    }

    /**
     * Retrieves a header by the given case-insensitive name, as a string.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeaderLines() instead
     * and supply your own delimiter when concatenating.
     *
     * @param string $name Case-insensitive header field name.
     * @return string
     */
    public function getHeader($name) {
        $lines = $this->getHeaderLines($name);
        return implode(',', $lines);
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[]
     */
    public function getHeaderLines($name) {
        $key = strtolower($name);
        $result = isset($this->headers[$key]) ? $this->headers[$key] : [];
        return $result;
    }

    /**
     * Retrieves all message headers.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers.
     * Each key is a header name, and each value is an an array of strings.
     */
    public function getHeaders() {
        $result = [];

        foreach ($this->headers as $key => $lines) {
            $name = $this->headerNames[$key];
            $result[$name] = $lines;
        }

        return $result;
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
     * @return HttpMessage Returns `$this` for fluent calls.
     */
    public function setHeaders($headers) {
        $this->headers = [];
        $this->headerNames = [];

        $headers = $this->parseHeaders($headers);
        foreach ($headers as $name => $lines) {
            $key = strtolower($name);
            if (isset($this->headers[$key])) {
                $this->headers[$key] = array_merge($this->headers[$key], $lines);
            } else {
                $this->headers[$key] = $lines;
            }
            $this->headerNames[$key] = $name;
        }

        return $this;
    }

    /**
     * Parse the http response headers from a response.
     *
     * @param mixed $headers Either the header string from a curl response or an array of header lines.
     * @return array
     */
    private function parseHeaders($headers) {
        if (is_string($headers)) {
            $headers = explode("\r\n", $headers);
        }

        if (empty($headers)) {
            return [];
        }

        $result = [];
        foreach ($headers as $key => $line) {
            if (is_numeric($key)) {
                if (strpos($line, 'HTTP/') === 0) {
                    // Strip the status line and restart.
                    $result = [];
                    continue;
                } elseif (strstr($line, ': ')) {
                    list($key, $line) = explode(': ', $line);
                } else {
                    continue;
                }
            }
            $result[$key][] = $line;
        }

        return $result;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($header) {
        return !empty($this->headers[strtolower($header)]);
    }

    /**
     * Set a header by case-insensitive name. Setting a header will overwrite the current value for the header.
     *
     * @param string $name The name of the header.
     * @param string|string[]|null $value The value of the new header. Pass `null` to remove the header.
     * @return HttpMessage Returns $this for fluent calls.
     */
    public function setHeader($name, $value) {
        $key = strtolower($name);

        if ($value === null) {
            unset($this->headerNames[$key], $this->headers[$key]);
        } else {
            $this->headerNames[$key] = $name;
            $this->headers[$key] = (array)$value;
        }

        return $this;
    }

    /**
     * Get the HTTP protocol version of the message.
     *
     * @return string Returns the current HTTP protocol version.
     */
    public function getProtocolVersion() {
        return $this->protocolVersion;
    }

    /**
     * Set the HTTP protocol version.
     *
     * The default protocol version of all messages is HTTP 1.1. Some old servers may only support HTTP 1.0 so that can
     * be overridden with this method.
     *
     * @param string $protocolVersion The new protocol version to set.
     * @return HttpMessage Returns `$this` for fluent calls.
     */
    public function setProtocolVersion($protocolVersion) {
        $this->protocolVersion = $protocolVersion;
        return $this;
    }

    /**
     * Get the body of the message.
     *
     * @return string|array Returns the body.
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Set the body of the message.
     *
     * @param string|array $body The new body of the message.
     * @return HttpMessage Returns `$this` for fluent calls.
     */
    public function setBody($body) {
        $this->body = $body;
        return $this;
    }
}
