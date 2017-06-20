<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;


/**
 * Representation of an outgoing, client-side request.
 */
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
     * @var array
     */
    protected $auth;

    /**
     * @var int
     */
    protected $timeout;

    /**
     * @var bool
     */
    protected $verifyPeer;

    /// Methods ///

    /**
     * Initialize an instance of the {@link HttpRequest} class.
     *
     * @param string $method The HTTP method of the request.
     * @param string $url The URL where the request will be sent.
     * @param string|array $body The body of the request.
     * @param array $headers An array of http headers to be sent with the request.
     * @param array $options An array of extra options.
     *
     * - protocolVersion: The HTTP protocol version.
     * - verifyPeer: Whether or not to verify an SSL peer. Default true.
     * - auth: A username/password used to send basic HTTP authentication with the request.
     * - timeout: The number of seconds to wait before the request times out. A value of zero means no timeout.
     */
    public function __construct($method = self::METHOD_GET, $url = '', $body = '', array $headers = [], array $options = []) {
        $this->setMethod(strtoupper($method));
        $this->setUrl($url);
        $this->setBody($body);
        $this->setHeaders($headers);

        $options += [
            'protocolVersion' => '1.1',
            'auth' => [],
            'timeout' => 0,
            'verifyPeer' => true
        ];

        $this->setProtocolVersion($options['protocolVersion']);
        $this->setAuth($options['auth']);
        $this->setVerifyPeer($options['verifyPeer']);
    }

    /**
     * Get the auth.
     *
     * @return array Returns the auth.
     */
    public function getAuth() {
        return $this->auth;
    }

    /**
     * Set the basic HTTP authentication for the request.
     *
     * @param array $auth An array in the form `[username, password]`.
     * @return HttpRequest Returns `$this` for fluent calls.
     */
    public function setAuth(array $auth) {
        $this->auth = $auth;
        return $this;
    }

    /**
     * Send the request.
     *
     * @return HttpResponse Returns the response from the API.
     */
    public function send() {
        $ch = $this->createCurl();
        $response = $this->execCurl($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Create the cURL resource that represents this request.
     *
     * @return resource Returns the cURL resource.
     * @see curl_init(), curl_setopt(), curl_exec()
     */
    protected function createCurl() {
        $ch = curl_init();

        // Add the body first so we can calculate a content length.
        $body = '';
        if ($this->method === self::METHOD_HEAD) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        } elseif ($this->method !== self::METHOD_GET) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);

            $body = $this->makeCurlBody();
            if ($body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        // Decode the headers.
        $headers = [];
        foreach ($this->getHeaders() as $key => $values) {
            foreach ($values as $line) {
                $headers[] = "$key: $line";
            }
        }

        if (is_string($body) && !$this->hasHeader('Content-Length')) {
            $headers[] = 'Content-Length: '.strlen($body);
        }

        if (!$this->hasHeader('Expect')) {
            $headers[] = 'Expect:';
        }

        curl_setopt(
            $ch,
            CURLOPT_HTTP_VERSION,
            $this->getProtocolVersion() == '1.0' ? CURL_HTTP_VERSION_1_0 : CURL_HTTP_VERSION_1_1
        );

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeout());
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifyPeer ? 2 : 0);
        curl_setopt($ch, CURLOPT_ENCODING, ''); //"utf-8");

        if (!empty($this->auth)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->auth[0].":".((empty($this->auth[1])) ? "" : $this->auth[1]));
        }

        return $ch;
    }

    /**
     * Convert the request body into a format suitable to be passed to curl.
     *
     * @return string|array Returns the curl body.
     */
    protected function makeCurlBody() {
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
     * @throws \Exception Throws an exception when the request returns a non 2xx response..
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
            $rawHeaders = substr($response, 0, $header_size);
            $status = null;
            $rawBody = substr($response, $header_size);
        } else {
            $status = $code;
            $rawHeaders = [];
            $rawBody = curl_error($ch);
        }

        $result = new HttpResponse($status, $rawHeaders, $rawBody);
        return $result;
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
     * @param string $method The new HTTP method.
     * @return HttpRequest Returns `$this` for fluent calls.
     */
    public function setMethod($method) {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * Get the URL where the request will be sent.
     *
     * @return string Returns the URL.
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Set the URL where the request will be sent.
     *
     * @param string $url The new URL.
     * @return HttpRequest Returns `$this` for fluent calls.
     */
    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    /**
     * Get whether or not to verify the SSL certificate of HTTPS calls.
     *
     * In production this settings should always be set to `true`, but during development or testing it's sometimes
     * necessary to allow invalid SSL certificates.
     *
     * @return boolean Returns the verifyPeer.
     */
    public function getVerifyPeer() {
        return $this->verifyPeer;
    }

    /**
     * Set whether or not to verify the SSL certificate of HTTPS calls.
     *
     * @param bool $verifyPeer The new verify peer setting.
     * @return HttpRequest Returns `$this` for fluent calls.
     */
    public function setVerifyPeer($verifyPeer) {
        $this->verifyPeer = $verifyPeer;
        return $this;
    }

    /**
     * Get the request timeout.
     *
     * @return int Returns the timeout in seconds.
     */
    public function getTimeout() {
        return $this->timeout;
    }

    /**
     * Set the request timeout.
     *
     * @param int $timeout The new request timeout in seconds.
     * @return HttpRequest Returns `$this` for fluent calls.
     */
    public function setTimeout($timeout) {
        $this->timeout = $timeout;
        return $this;
    }
}
