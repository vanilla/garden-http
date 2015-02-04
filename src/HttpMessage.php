<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Http;


abstract class HttpMessage {
    /// Properties ///

    /**
     * @var
     */
    protected $body;

    /**
     * @var array An array of headers stored by lower cased header name.
     */
    private $headers = [];

    /**
     * @var array An array of header names as specified by the various header methods.
     */
    private $headerNames = [];

    /**
     * @var string The HTTP protocol version of the request.
     */
    protected $protocolVersion = 1.1;

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
     * Parse the http response headers from a response.
     *
     * @param mixed $headers Either the header string from a curl response or an array of header lines.
     * @return array
     */
    private function parseHeaders($headers) {
        if (is_string($headers)) {
            $headers = explode("\r\n", $headers);
        }

        // Strip the status line.
        $firstLine = array_shift($headers);
        if (strpos($firstLine, 'HTTP/') !== 0) {
            array_unshift($headers, $firstLine);
        }

        $result = [];
        foreach ($headers as $key => $line) {
            if (is_numeric($key)) {
                if (strstr($line, ': ')) {
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
     * Set all of the headers. This will overwrite any existing headers.
     *
     * @param array $headers An array of headers to set. This array can be in the following form.
     *
     * - ["Header-Name" => "value", ...]
     * - ["Header-Name" => ["lines, ...], ...]
     * - ["Header-Name: value", ...]
     * - Any combination of the above formats.
     *
     * @return HttMessage Returns `$this` for fluent calls.
     */
    public function setHeaders(array $headers) {
        $this->headers = [];
        $this->headerNames = [];

        $headers = $this->parseHeaders($headers);
        foreach ($headers as $name => $lines) {
            $key = strtolower($name);
            $this->headers[$key] = $lines;
            $this->headerNames[$key] = $name;
        }

        return $this;
    }

    /**
     * Get the protocolVersion.
     *
     * @return string Returns the protocolVersion.
     */
    public function getProtocolVersion() {
        return $this->protocolVersion;
    }

    /**
     * Set the protocolVersion.
     *
     * @param string $protocolVersion
     * @return HttpMessage Returns `$this` for fluent calls.
     */
    public function setProtocolVersion($protocolVersion) {
        $this->protocolVersion = $protocolVersion;
        return $this;
    }
}
