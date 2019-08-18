<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;


/**
 * Represents an client connection to a RESTful API.
 */
class HttpClient {
    /// Properties ///

    /**
     * @var string The base URL prefix of the API.
     */
    protected $baseUrl;

    /**
     * @var array The default headers to send with each API request.
     */
    protected $defaultHeaders = [];

    /**
     * @var array The default options for all requests.
     */
    protected $defaultOptions = [];

    /**
     * @var bool Whether or not to throw exceptions on non-2xx responses.
     */
    protected $throwExceptions = false;

    /**
     * @var callable Middleware that modifies requests and responses.
     */
    protected $middleware;

    /**
     * @var HttpHandlerInterface The handler that will send the actual requests.
     */
    protected $handler;

    /// Methods ///

    /**
     * Initialize a new instance of the {@link HttpClient} class.
     *
     * @param string $baseUrl The base URL prefix of the API.
     * @param HttpHandlerInterface The handler that will send the actual requests.
     */
    public function __construct(string $baseUrl = '', HttpHandlerInterface $handler = null) {
        $this->baseUrl = $baseUrl;
        $this->setHandler($handler === null ? new CurlHandler() : $handler);
        $this->setDefaultHeader('User-Agent', 'garden-http/2 (HttpRequest)');
        $this->middleware = function (HttpRequest $request): HttpResponse {
            return $request->send($this->getHandler());
        };
    }

    /**
     * Construct and append a querystring array to a uri.
     *
     * @param string $uri The uri to append the query to.
     * @param array $query The query to turn into a querystring.
     * @return string Returns the final uri.
     */
    protected static function appendQuery(string $uri, array $query = []): string {
        if (!empty($query)) {
            $qs = http_build_query($query);
            $uri .= (strpos($uri, '?') === false ? '?' : '&').$qs;
        }
        return $uri;
    }

    /**
     * Create a new {@link HttpRequest} object with properties filled out from the API client's settings.
     *
     * @param string $method The HTTP method of the request.
     * @param string $uri The URL or path of the request.
     * @param string|array $body The body of the request.
     * @param array $headers An array of HTTP headers to add to the request.
     * @param array $options Additional options to be sent with the request.
     * @return HttpRequest Returns the new {@link HttpRequest} object.
     */
    public function createRequest(string $method, string $uri, $body, array $headers = [], array $options = []) {
        if (strpos($uri, '//') === false) {
            $uri = $this->baseUrl.'/'.ltrim($uri, '/');
        }

        $headers = array_replace($this->defaultHeaders, $headers);
        $options = array_replace($this->defaultOptions, $options);

        $request = new HttpRequest($method, $uri, $body, $headers, $options);
        return $request;
    }

    /**
     * Send a DELETE request to the API.
     *
     * @param string $uri The URL or path of the request.
     * @param array $query The querystring to add to the URL.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    public function delete(string $uri, array $query = [], array $headers = [], array $options = []) {
        $uri = static::appendQuery($uri, $query);

        return $this->request(HttpRequest::METHOD_DELETE, $uri, '', $headers, $options);
    }

    /**
     * Send a GET request to the API.
     *
     * @param string $uri The URL or path of the request.
     * @param array $query The querystring to add to the URL.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    public function get(string $uri, array $query = [], array $headers = [], $options = []) {
        $uri = static::appendQuery($uri, $query);

        return $this->request(HttpRequest::METHOD_GET, $uri, '', $headers, $options);
    }

    /**
     * Handle a non 200 series response from the API.
     *
     * This method is here specifically for sub-classes to override. When an API call is made and a non-200 series
     * status code is returned then this method is called with that response. This lets API client authors to extract
     * the appropriate error message out of the response and decide whether or not to throw a PHP exception.
     *
     * It is recommended that you obey the caller's wishes on whether or not to throw an exception by using the
     * following `if` statement:
     *
     * ```php
     * if ($options['throw'] ?? $this->throwExceptions) {
     * ...
     * }
     * ```
     *
     * @param HttpResponse $response The response sent from the API.
     * @param array $options The options that were sent with the request.
     * @throws HttpResponseException Throws an exception if the settings or options say to throw an exception.
     */
    public function handleErrorResponse(HttpResponse $response, $options = []) {
        if ($options['throw'] ?? $this->throwExceptions) {
            $body = $response->getBody();
            if (is_array($body)) {
                $message = $body['message'] ?? $response->getReasonPhrase();
            } else {
                $message = $response->getReasonPhrase();
            }
            throw new HttpResponseException($response, $message);
        }
    }

    /**
     * Send a HEAD request to the API.
     *
     * @param string $uri The URL or path of the request.
     * @param array $query The querystring to add to the URL.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    public function head(string $uri, array $query = [], array $headers = [], $options = []) {
        $uri = static::appendQuery($uri, $query);
        return $this->request(HttpRequest::METHOD_HEAD, $uri, '', $headers, $options);
    }


    /**
     * Send an OPTIONS request to the API.
     *
     * @param string $uri The URL or path of the request.
     * @param array $query The querystring to add to the URL.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    public function options(string $uri, array $query = [], array $headers = [], $options = []) {
        $uri = static::appendQuery($uri, $query);
        return $this->request(HttpRequest::METHOD_OPTIONS, $uri, '', $headers, $options);
    }

    /**
     * Send a PATCH request to the API.
     *
     * @param string $uri The URL or path of the request.
     * @param array|string $body The HTTP body to send to the request or an array to be appropriately encoded.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    public function patch(string $uri, $body = [], array $headers = [], $options = []) {
        return $this->request(HttpRequest::METHOD_PATCH, $uri, $body, $headers, $options);
    }

    /**
     * Send a POST request to the API.
     *
     * @param string $uri The URL or path of the request.
     * @param array|string $body The HTTP body to send to the request or an array to be appropriately encoded.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    public function post(string $uri, $body = [], array $headers = [], $options = []) {
        return $this->request(HttpRequest::METHOD_POST, $uri, $body, $headers, $options);
    }

    /**
     * Send a PUT request to the API.
     *
     * @param string $uri The URL or path of the request.
     * @param array|string $body The HTTP body to send to the request or an array to be appropriately encoded.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    public function put(string $uri, $body = [], array $headers = [], $options = []) {
        return $this->request(HttpRequest::METHOD_PUT, $uri, $body, $headers, $options);
    }

    /**
     * Make a generic HTTP request against the API.
     *
     * @param string $method The HTTP method of the request.
     * @param string $uri The URL or path of the request.
     * @param array|string $body The HTTP body to send to the request or an array to be appropriately encoded.
     * @param array $headers The HTTP headers to add to the request.
     * @param array $options An array of additional options for the request.
     * @return HttpResponse Returns the {@link HttpResponse} object from the call.
     */
    public function request(string $method, string $uri, $body, array $headers = [], array $options = []) {
        $request = $this->createRequest($method, $uri, $body, $headers, $options);
        // Call the chain of middleware on the request.
        $response = call_user_func($this->middleware, $request);

        if (!$response->isResponseClass('2xx')) {
            $this->handleErrorResponse($response, $options);
        }

        return $response;
    }

    /**
     * Get the base URL of the API.
     *
     * @return string Returns the baseUrl.
     */
    public function getBaseUrl(): string {
        return $this->baseUrl;
    }

    /**
     * Set the base URL of the API.
     *
     * @param string $baseUrl The base URL of the API.
     * @return HttpClient Returns `$this` for fluent calls.
     */
    public function setBaseUrl(string $baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Get the value of a default header.
     *
     * Default headers are sent along with all requests.
     *
     * @param string $name The name of the header to get.
     * @param mixed $default The value to return if there is no default header.
     * @return mixed Returns the value of the default header.
     */
    public function getDefaultHeader(string $name, $default = null) {
        return $this->defaultHeaders[$name] ?? $default;
    }

    /**
     * Set the value of a default header.
     *
     * @param string $name The name of the header to set.
     * @param string $value The new value of the default header.
     * @return HttpClient Returns `$this` for fluent calls.
     */
    public function setDefaultHeader(string $name, string $value) {
        $this->defaultHeaders[$name] = $value;
        return $this;
    }

    /**
     * Get the all the default headers.
     *
     * The default headers are added to every request.
     *
     * @return array Returns the default headers.
     */
    public function getDefaultHeaders(): array {
        return $this->defaultHeaders;
    }

    /**
     * Set the default headers.
     *
     * @param array $defaultHeaders The new default headers.
     * @return HttpClient Returns `$this` for fluent calls.
     */
    public function setDefaultHeaders(array $defaultHeaders) {
        $this->defaultHeaders = $defaultHeaders;
        return $this;
    }

    /**
     * Get the value of a default option.
     *
     * @param string $name The name of the default option.
     * @param mixed $default The value to return if there is no default option set.
     * @return mixed Returns the default option or {@link $default}.
     */
    public function getDefaultOption(string $name, $default = null) {
        return $this->defaultOptions[$name] ?? $default;
    }

    /**
     * Set the value of a default option.
     *
     * @param string $name The name of the default option.
     * @param mixed $value The new value of the default option.
     * @return HttpClient Returns `$this` for fluent calls.
     */
    public function setDefaultOption(string $name, $value) {
        $this->defaultOptions[$name] = $value;
        return $this;
    }

    /**
     * Get all of the default options.
     *
     * @return array Returns an array of default options.
     */
    public function getDefaultOptions(): array {
        return $this->defaultOptions;
    }

    /**
     * Gets whether or not to throw exceptions when an error response is returned from a request.
     *
     * @return boolean Returns `true` if exceptions should be thrown or `false` otherwise.
     */
    public function getThrowExceptions(): bool {
        return $this->throwExceptions;
    }

    /**
     * Sets whether or not to throw exceptions when an error response is returned from a request.
     *
     * @param boolean $throwExceptions Whether or not to throw exceptions when an error response is encountered.
     * @return HttpClient Returns `$this` for fluent calls.
     */
    public function setThrowExceptions(bool $throwExceptions) {
        $this->throwExceptions = $throwExceptions;
        return $this;
    }

    /**
     * Set the default options.
     *
     * @param array $defaultOptions The new default options array.
     * @return HttpClient Returns `$this` for fluent calls.
     */
    public function setDefaultOptions(array $defaultOptions) {
        $this->defaultOptions = $defaultOptions;
        return $this;
    }

    /**
     * Add a middleware function to the client.
     *
     * A Middleware is a callable that has the following signature:
     *
     * ```php
     * function middleware(HttpRequest $request, callable $next): HttpResponse {
     *      // Optionally modify the request.
     *      $request->setHeader('X-Foo', 'bar');
     *
     *      // Process the request by calling $next. You must always call next.
     *      $response = $next($request);
     *
     *      // Optionally modify the response.
     *      $response->setHeader('Access-Control-Allow-Origin', '*');
     *
     *      return $response;
     * }
     * ```
     *
     * @param callable $middleware The middleware callback to add.
     * @return $this
     */
    public function addMiddleware(callable $middleware) {
        $next = $this->middleware;

        $this->middleware = function (HttpRequest $request) use ($middleware, $next): HttpResponse {
            return $middleware($request, $next);
        };

        return $this;
    }

    /**
     * Get the HTTP handler that will send the actual requests.
     *
     * @return HttpHandlerInterface Returns the current handler.
     */
    public function getHandler(): HttpHandlerInterface {
        return $this->handler;
    }

    /**
     * Set the HTTP handler that will send the actual requests.
     *
     * @param HttpHandlerInterface $handler The new handler.
     * @return $this
     */
    public function setHandler(HttpHandlerInterface $handler) {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Safely get a value out of an array.
     *
     * @param string|int $key The array key.
     * @param array $array The array to get the value from.
     * @param mixed $default The default value to return if the key doesn't exist.
     * @return mixed The item from the array or `$default` if the array key doesn't exist.
     * @deprecated
     */
    protected function val($key, $array, $default = null) {
        if (isset($array[$key])) {
            return $array[$key];
        }
        return $default;
    }
}
