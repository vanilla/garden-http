<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;


class HttpRequest extends HttpMessage {
    /// Constants ///

    const METHOD_DELETE = 'DELETE';
    const METHOD_GET = 'GET';
    const METHOD_HEAD = 'HEAD';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_PATCH = 'PATCH';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';

    /// Properties ///

    /**
     * @var string The HTTP method of the request.
     */
    protected $method;

    /**
     * @var string The URL of the request.
     */
    protected $url;

    /**
     * @var mixed The body of the request.
     */
    protected $body;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var bool
     */
    protected $verifyPeer;

    /// Methods ///

    public function __construct($method, $url, $body, array $headers = [], array $options = []) {
        $this->method = strtoupper($method);
        $this->url = $url;
        $this->body = $body;
        $this->headers = static::normalizeHeaders($headers);

        if (!isset($this->headers['User-Agent'])) {
            $this->headers['User-Agent'] = ['garden-http/1.0.0 (HttpRequest)'];
        }

        $options += [
            'protocolVersion' => '1.1',
            'username' => '',
            'password' => '',
            'verifyPeer' => true
        ];

        $this->protocolVersion = $options['protocolVersion'];
        $this->username = $options['username'];
        $this->password = $options['password'];
        $this->verifyPeer = $options['verifyPeer'];
    }

    protected function createCurl() {
        $ch = curl_init();

        // Add the body first so we can calculate a content length.
        $body = '';
        if ($this->method === self::METHOD_HEAD) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        } elseif ($this->method !== self::METHOD_GET) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);

            $body = $this->getCurlBody();
            if ($body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        // Decode the headers.
        $headers = [];
        foreach ($this->headers as $key => $values) {
            foreach ($values as $line) {
                $headers[] = "$key: $line";
            }
        }

        if (is_string($body) && !isset($this->headers['Content-Length'])) {
            $headers[] = 'Content-Length: '.strlen($body);
        }

        if (!isset($this->headers['Expect'])) {
            $headers[] = 'Expect:';
        }

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
        curl_setopt($ch, CURLOPT_ENCODING, ''); //"utf-8");

        if (!empty($this->username)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->username.":".((empty($this->password)) ? "" : $this->password));
        }

        return $ch;
    }

    protected function getCurlBody() {
        $body = $this->body;

        if (is_string($body)) {
            return (string)$body;
        }

        $contentType = $this->getHeader('Content-Type');
        if (stripos($contentType, 'application/json') === 0) {
            $body = json_encode($body);
        }

        return $body;
    }

    /**
     * Execute a curl handle and return the response.
     *
     * @param resource $ch The curl handle to execute.
     * @return HttpResponse Returns an {@link RestResponse} object with the information from the request
     * @throws Exception Throws an exception when the request returns a non 2xx response..
     */
    protected function execCurl($ch) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);

        // Split the full response into its headers and body
        $info = curl_getinfo($ch);
        $code = $info["http_code"];
        if ($response) {
            $header_size = $info["header_size"];
            $rawHeaders = explode("\r\n", substr($response, 0, $header_size));
            $status = array_shift($rawHeaders);
            $rawBody = substr($response, $header_size);
        } else {
            $error = curl_error($ch);
            $status = $code;
            $rawHeaders = [];
            $rawBody = curl_error($ch);
        }

        $result = new HttpResponse($status, $rawHeaders, $rawBody, $info);
        return $result;
    }

    public function send() {
        $ch = $this->createCurl();
        $response = $this->execCurl($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Get the HTTP method.
     *
     * @return string Returns the HTTP method.
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Set the HTTP method.
     *
     * @param string $method
     * @return HttpRequest Returns `$this` for fluent calls.
     */
    public function setMethod($method) {
        $this->method = strtoupper($method);
        return $this;
    }
}
