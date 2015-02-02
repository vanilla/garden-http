<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Http;


class HttpMessage {
    /// Properties ///

    /**
     * @var
     */
    protected $body;

    /**
     * @var array
     */
    protected $headers = [];

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
        $name = static::normalizeHeaderName($name);
        $this->headers[$name][] = $value;

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
        $name = static::normalizeHeaderName($name);
        $result = isset($this->headers[$name]) ? $this->headers[$name] : [];
        return $result;
    }

    /**
     * Normalize a header field name to follow the general HTTP header `Capital-Dash-Separated` convention.
     *
     * @param string $name The header name to normalize.
     * @return string Returns the normalized header name.
     */
    protected static function normalizeHeaderName($name) {
        $result = str_replace(' ', '-', ucwords(str_replace(['-', '_'], ' ', strtolower($name))));
        return $result;
    }

    /**
     * Normalize an array of headers so they are all internally consistent for look ups.
     *
     * @param array $headers The array of headers to normalize.
     * @return array Returns an array of normalized headers.
     */
    protected static function normalizeHeaders($headers) {
        $result = [];
        foreach ($headers as $key => $value) {
            $key = static::normalizeHeaderName($key);
            if (is_scalar($value)) {
                $value = [(string)$value];
            } elseif (!is_array($value)) {
                continue;
            }

            if (isset($result[$key])) {
                $result[$key] = array_merge($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Parse the http response headers from a response.
     *
     * @param mixed $headers Either the header string from a curl response or an array of header lines.
     * @return array
     */
    protected function parseHeaders($headers) {
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
}
