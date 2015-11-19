<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;

/**
 * Class HttpClient
 *
 * @package Garden\Http
 */
class HttpClient {

    /** @var string  */
    protected $baseUrl;

    /** @var array  */
    protected $defaultHeaders = [];

    /** @var array  */
    protected $defaultOptions = [];

    /** @var bool  */
    protected $throwExceptions = false;

    /**
     * Basic setup.
     *
     * @param string $baseUrl
     */
    public function __construct($baseUrl = '') {
        $this->baseUrl = $baseUrl;
        $this->setDefaultHeader('User-Agent', 'garden-http/1.0.0 (HttpRequest)');
    }

    /**
     * Construct and append a querystring array to a uri.
     *
     * @param string $uri The uri to append the query to.
     * @param array $query The query to turn into a querystring.
     * @return string Returns the final uri.
     */
    protected static function appendQuery($uri, array $query = []) {
        if (!empty($query)) {
            $qs = http_build_query($query);
            $uri .= (strpos($uri, '?') === false ? '?' : '&').$qs;
        }
        return $uri;
    }

    /**
     * Create a new request.
     *
     * @param $method
     * @param $uri
     * @param $parameters
     * @param array $headers
     * @param array $options
     * @return HttpRequest
     */
    public function createRequest($method, $uri, $parameters, array $headers = [], array $options = []) {
        if (strpos($uri, '//') === false) {
            $uri = $this->baseUrl.'/'.ltrim($uri, '/');
        }

        $headers = array_replace($this->defaultHeaders, $headers);
        $options = array_replace($this->defaultOptions, $options);

        $request = new HttpRequest($method, $uri, $parameters, $headers, $options);
        return $request;
    }

    /**
     * Delete a request.
     *
     * @param $uri
     * @param array $query
     * @param array $headers
     * @param array $options
     * @return HttpResponse
     */
    public function delete($uri, array $query = [], array $headers = [], $options = []) {
        $uri = static::appendQuery($uri, $query);

        return $this->request(HttpRequest::METHOD_DELETE, $uri, '', $headers, $options);
    }

    /**
     * Setup a GET request.
     *
     * @param $uri
     * @param array $query
     * @param array $headers
     * @param array $options
     * @return HttpResponse
     */
    public function get($uri, array $query = [], array $headers = [], $options = []) {
        $uri = static::appendQuery($uri, $query);

        return $this->request(HttpRequest::METHOD_GET, $uri, '', $headers, $options);
    }

    /**
     * Handle an error.
     *
     * @param HttpResponse $response
     * @param array $options
     * @throws \Exception
     */
    public function handleErrorResponse(HttpResponse $response, $options = []) {
        if ($this->val('throw', $options, $this->throwExceptions)) {
            $body = $response->getBody();
            if (is_array($body)) {
                $message = $this->val('message', $body, $response->getReasonPhrase());
            } else {
                $message = $response->getReasonPhrase();
            }
            throw new \Exception($message, $response->getStatusCode());
        }
    }

    /**
     * Setup a HEAD request.
     *
     * @param $uri
     * @param array $query
     * @param array $headers
     * @param array $options
     * @return HttpResponse
     */
    public function head($uri, array $query = [], array $headers = [], $options = []) {
        $uri = static::appendQuery($uri, $query);
        return $this->request(HttpRequest::METHOD_HEAD, $uri, '', $headers, $options);
    }


    /**
     * Setup an OPTIONS request.
     *
     * @param $uri
     * @param array $headers
     * @param array $options
     * @return HttpResponse
     */
    public function options($uri, array $query = [], array $headers = [], $options = []) {
        $uri = static::appendQuery($uri, $query);
        return $this->request(HttpRequest::METHOD_OPTIONS, $uri, '', $headers, $options);
    }

    /**
     * Setup a PATCH request.
     *
     * @param $uri
     * @param $body
     * @param array $headers
     * @param array $options
     * @return HttpResponse
     */
    public function patch($uri, $body = [], array $headers = [], $options = []) {
        return $this->request(HttpRequest::METHOD_PATCH, $uri, $body, $headers, $options);
    }

    /**
     * Setup a POST request.
     *
     * @param $uri
     * @param $body
     * @param array $headers
     * @param array $options
     * @return HttpResponse
     */
    public function post($uri, $body = [], array $headers = [], $options = []) {
        return $this->request(HttpRequest::METHOD_POST, $uri, $body, $headers, $options);
    }

    /**
     * Setup a PUT request.
     *
     * @param $uri
     * @param $body
     * @param array $headers
     * @param array $options
     * @return HttpResponse
     */
    public function put($uri, $body = [], array $headers = [], $options = []) {
        return $this->request(HttpRequest::METHOD_PUT, $uri, $body, $headers, $options);
    }

    /**
     * Handle full request process.
     *
     * @param string $method
     * @param string $uri
     * @param mixed $body
     * @param array $headers
     * @param array $options
     * @return HttpResponse
     */
    public function request($method, $uri, $body, $headers = [], array $options = []) {
        $request = $this->createRequest($method, $uri, $body, $headers, $options);
        $response = $request->send();

        if (!$response->isResponseClass('2xx')) {
            $this->handleErrorResponse($response, $options);
        }

        return $response;
    }

    /**
     * Get the baseUrl.
     *
     * @return mixed Returns the baseUrl.
     */
    public function getBaseUrl() {
        return $this->baseUrl;
    }

    /**
     * Set the baseUrl.
     *
     * @param mixed $baseUrl
     * @return HttpClient Returns `$this` for fluent calls.
     */
    public function setBaseUrl($baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    public function getDefaultHeader($name, $default = null) {
        return $this->val($name, $this->defaultHeaders, $default);
    }

    /**
     * Set a default header.
     *
     * @param string $name
     * @param mixed $value
     * @return HttpClient $this
     */
    public function setDefaultHeader($name, $value) {
        $this->defaultHeaders[$name] = $value;
        return $this;
    }

    /**
     * Get the defaultHeaders.
     *
     * @return array Returns the defaultHeaders.
     */
    public function getDefaultHeaders() {
        return $this->defaultHeaders;
    }

    /**
     * Set the default headers.
     *
     * @param array $defaultHeaders
     * @return HttpClient Returns `$this` for fluent calls.
     */
    public function setDefaultHeaders($defaultHeaders) {
        $this->defaultHeaders = $defaultHeaders;
        return $this;
    }

    /**
     *
     *
     * @param $name
     * @param null $default
     * @return null
     */
    public function getDefaultOption($name, $default = null) {
        return $this->val($name, $this->defaultOptions, $default);
    }

    /**
     * Set a default option.
     *
     * @param string $name
     * @param mixed $value
     * @return HttpClient $this
     */
    public function setDefaultOption($name, $value) {
        $this->defaultOptions[$name] = $value;
        return $this;
    }

    /**
     * Get the default options.
     *
     * @return array Returns the defaultOptions.
     */
    public function getDefaultOptions() {
        return $this->defaultOptions;
    }

    /**
     * Gets whether or not to throw exceptions when an error response is returned from a request.
     *
     * @return boolean Returns `true` if exceptions should be thrown or `false` otherwise.
     */
    public function getThrowExceptions() {
        return $this->throwExceptions;
    }

    /**
     * Sets whether or not to throw exceptions when an error response is returned from a request.
     *
     * @param boolean $throwExceptions Whether or not to throw exceptions when an error response is encountered.
     * @return HttpClient Returns `$this` for fluent calls.
     */
    public function setThrowExceptions($throwExceptions) {
        $this->throwExceptions = $throwExceptions;
        return $this;
    }

    /**
     * Set the defaultOptions.
     *
     * @param array $defaultOptions
     * @return HttpClient Returns `$this` for fluent calls.
     */
    public function setDefaultOptions($defaultOptions) {
        $this->defaultOptions = $defaultOptions;
        return $this;
    }

    /**
     * Get specified key from array if it exists, otherwise use the default.
     *
     * @param $key
     * @param $arr
     * @param null $default
     * @return null
     */
    protected function val($key, $arr, $default = null) {
        if (isset($arr[$key])) {
            return $arr[$key];
        }
        return $default;
    }
}
