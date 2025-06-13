<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;

use Garden\Http\Mocks\MockHttpHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Factory\UriFactory;

/**
 * Representation of an outgoing, client-side request.
 */
class HttpRequest extends HttpMessage implements \JsonSerializable, RequestInterface {
    /// Constants ///

    public const OPT_TIMEOUT = "timeout";
    public const OPT_CONNECT_TIMEOUT = "connectTimeout";
    public const OPT_VERIFY_PEER = "verifyPeer";
    public const OPT_PROTOCOL_VERSION = "protocolVersion";
    public const OPT_AUTH = "auth";

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
     * @deprecated Use $uri instead.
     */
    protected $url;

    /** @var UriInterface */
    protected UriInterface $uri;

    /** @var UriInterface|null */
    protected UriInterface|null $proxiedToUri = null;

    /**
     * @var array
     */
    protected $auth;

    /**
     * @var int
     */
    protected $timeout = 0;

    /** @var int */
    protected $connectTimeout = 0;

    /**
     * @var bool
     */
    protected $verifyPeer;

    /** @var HttpResponse|null */
    protected ?HttpResponse $response;

    /// Methods ///

    /**
     * Initialize an instance of the {@link HttpRequest} class.
     *
     * @param string $method The HTTP method of the request.
     * @param string|UriInterface $url The URL where the request will be sent.
     * @param string|array $body The body of the request.
     * @param array $headers An array of http headers to be sent with the request.
     * @param array $options An array of extra options.
     *
     * - protocolVersion: The HTTP protocol version.
     * - verifyPeer: Whether or not to verify an SSL peer. Default true.
     * - auth: A username/password used to send basic HTTP authentication with the request.
     * - timeout: The number of seconds to wait before the request times out. A value of zero means no timeout.
     */
    public function __construct(string $method = self::METHOD_GET, string|UriInterface $url = '', $body = '', array $headers = [], array $options = []) {
        $this->setMethod(strtoupper($method));
        if ($url instanceof UriInterface) {
            $this->setUri($url);
        } else {
            $this->setUrl($url);
        }
        $this->setBody($body);
        $this->setHeaders($headers);

        $this->setProtocolVersion($options[self::OPT_PROTOCOL_VERSION] ?? "1.1");
        $this->setAuth($options[self::OPT_AUTH] ?? []);
        $this->setVerifyPeer($options[self::OPT_VERIFY_PEER] ?? true);
        $this->setConnectTimeout($options[self::OPT_CONNECT_TIMEOUT] ?? 0);
        $this->setTimeout($options[self::OPT_TIMEOUT] ?? 0);
    }

    /**
     * @return HttpResponse|null
     */
    public function getResponse(): ?HttpResponse {
        return $this->response;
    }

    /**
     * @param HttpResponse $response
     * @return void
     */
    public function setResponse(HttpResponse $response): void {
        $this->response = $response;
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

        if (MockHttpHandler::getMock() !== null) {
            $executor = MockHttpHandler::getMock();
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
        $uriFactory = new UriFactory();
        $uri = $uriFactory->createUri($url);
        $this->url = $url;
        $this->uri = $uri;
        return $this;
    }

    /**
     * Set the URI of the request.
     *
     * @param UriInterface $uri The new URI.
     *
     * @return static Returns `$this` for fluent calls.
     */
    public function setUri(UriInterface $uri): static {
        $this->uri = $uri;
        $this->url = (string) $uri;
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
     * @return int
     */
    public function getConnectTimeout(): int {
        return $this->connectTimeout;
    }

    /**
     * @param int $connectTimeout
     * @return void
     */
    public function setConnectTimeout(int $connectTimeout): HttpRequest {
        $this->connectTimeout = $connectTimeout;
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

    /**
     * Basic JSON implementation.
     *
     * @return array
     */
    public function jsonSerialize(): array {
        return [
            "url" => $this->getUrl(),
            "proxiedToUrl" => $this->proxiedToUri !== null ? (string) $this->proxiedToUri : null,
            "host" => $this->proxiedToUri !== null ? $this->proxiedToUri->getHost() : $this->getUri()->getHost(),
            "method" => $this->getMethod(),
        ];
    }

    ///
    /// PSR Implementation
    ///

    /**
     * @inheritDoc
     */
    public function getRequestTarget(): string {
        $uri = $this->getUri();
        $path = $uri->getPath();
        $path = '/' . ltrim($path, '/');

        $query = $uri->getQuery();
        if ($query) {
            $path .= '?' . $query;
        }

        return $path;
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget) {
        $uri = $this->getUri();
        $pieces = parse_url($requestTarget);

        if (isset($pieces['path'])) {
            $uri = $uri->withPath($pieces['path']);
        }

        if (isset($pieces['query'])) {
            $uri = $uri->withQuery($pieces['query']);
        }
        return $this->withUri($uri);
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method) {
        $cloned = clone $this;
        $cloned->setMethod($method);
        return $cloned;
    }

    /**
     * @inheritDoc
     */
    public function getUri(): UriInterface {
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false) {
        $cloned = clone $this;
        $cloned->setUrl((string) $uri);
        return $cloned;
    }

    /**
     * @return UriInterface|null
     */
    public function getProxiedToUri(): ?UriInterface {
        return $this->proxiedToUri;
    }

    /**
     * @param UriInterface|null $proxiedToUri
     * @return void
     */
    public function setProxiedToUri(?UriInterface $proxiedToUri): void {
        $this->proxiedToUri = $proxiedToUri;
    }
}
