<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
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
    protected $timeout = 0;

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
    public function __construct(string $method = self::METHOD_GET, string $url = '', $body = '', array $headers = [], array $options = []) {
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
        $this->setTimeout($options['timeout']);
    }

    /**
     * Get the auth.
     *
     * @return array Returns the auth.
     */
    public function getAuth(): array {
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
    public function send(HttpHandlerInterface $executor = null): HttpResponse {
        if ($executor === null) {
            $executor = new CurlHandler();
        }

        $response = $executor->send($this);

        return $response;
    }

    /**
     * Get the HTTP method.
     *
     * @return string Returns the HTTP method.
     */
    public function getMethod(): string {
        return $this->method;
    }

    /**
     * Set the HTTP method.
     *
     * @param string $method The new HTTP method.
     * @return HttpRequest Returns `$this` for fluent calls.
     */
    public function setMethod(string $method) {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * Get the URL where the request will be sent.
     *
     * @return string Returns the URL.
     */
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * Set the URL where the request will be sent.
     *
     * @param string $url The new URL.
     * @return HttpRequest Returns `$this` for fluent calls.
     */
    public function setUrl(string $url) {
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
    public function getVerifyPeer(): bool {
        return $this->verifyPeer;
    }

    /**
     * Set whether or not to verify the SSL certificate of HTTPS calls.
     *
     * @param bool $verifyPeer The new verify peer setting.
     * @return HttpRequest Returns `$this` for fluent calls.
     */
    public function setVerifyPeer(bool $verifyPeer) {
        $this->verifyPeer = $verifyPeer;
        return $this;
    }

    /**
     * Get the request timeout.
     *
     * @return int Returns the timeout in seconds.
     */
    public function getTimeout(): int {
        return $this->timeout;
    }

    /**
     * Set the request timeout.
     *
     * @param int $timeout The new request timeout in seconds.
     * @return HttpRequest Returns `$this` for fluent calls.
     */
    public function setTimeout(int $timeout) {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Get constructor options as an array.
     *
     * This method is useful for copying requests as it can be passed into constructors.
     *
     * @return array Returns an array of options.
     */
    public function getOptions(): array {
        return [
            'protocolVersion' => $this->getProtocolVersion(),
            'auth' => $this->getAuth(),
            'timeout' => $this->getTimeout(),
            'verifyPeer' => $this->getVerifyPeer()
        ];
    }
}
