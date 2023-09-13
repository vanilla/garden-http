# Garden HTTP

[![CI Tests](https://github.com/vanilla/garden-http/actions/workflows/ci.yml/badge.svg)](https://github.com/vanilla/garden-http/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/vanilla/garden-http.svg?style=flat)](https://packagist.org/packages/vanilla/garden-http)
[![CLA](https://cla-assistant.io/readme/badge/vanilla/garden-http)](https://cla-assistant.io/vanilla/garden-http)

Garden HTTP is an unbloated HTTP client library for building RESTful API clients. It's meant to allow you to access
people's APIs without having to copy/paste a bunch of cURL setup and without having to double the size of your codebase.
You can use this library as is for quick API clients or extend the `HttpClient` class to make structured API clients
that you use regularly.

## Installation

*Garden HTTP requires PHP 7.4 or higher and libcurl*

Garden HTTP is [PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md) compliant and can be installed using [composer](//getcomposer.org). Just add `vanilla/garden-http` to your composer.json.

Garden request and response objects are [PSR-7](https://www.php-fig.org/psr/psr-7/) compliant as well.

## Basic Example

Almost all uses of Garden HTTP involve first creating an `HttpClient` object and then making requests from it.
You can see below a default header is also set to pass a standard header to every request made with the client.

```php
use Garden\Http\HttpClient;

$api = new HttpClient('http://httpbin.org');
$api->setDefaultHeader('Content-Type', 'application/json');

// Get some data from the API.
$response = $api->get('/get'); // requests off of base url
if ($response->isSuccessful()) {
    $data = $response->getBody(); // returns array of json decoded data
}

$response = $api->post('https://httpbin.org/post', ['foo' => 'bar']);
if ($response->isResponseClass('2xx')) {
    // Access the response like an array.
    $posted = $response['json']; // should be ['foo' => 'bar']
}
```

## Throwing Exceptions

You can tell the HTTP client to throw an exception on unsuccessful requests.

```php
use Garden\Http\HttpClient;

$api = new HttpClient('https://httpbin.org');
$api->setThrowExceptions(true);

try {
    $api->get('/status/404');
} catch (\Exception $ex) {
    $code = $ex->getCode(); // should be 404
    throw $ex;
}

// If you don't want a specific request to throw.
$response = $api->get("/status/500", [], [], ["throw" => false]);
// But you could throw it yourself.
if (!$response->isSuccessful()) {
    throw $response->asException();
}
```

Exceptions will be thrown with a message indicating the failing response and structured data as well.

```php
try {
    $response = new HttpResponse(501, ["content-type" => "application/json"], '{"message":"Some error occured."}');
    throw $response->asException();
    // Make an exception
} catch (\Garden\Http\HttpResponseException $ex) {
    // Request POST /some/path failed with a response code of 501 and a custom message of "Some error occured."
    $ex->getMessage();
    
    // [
    //      "request" => [
    //          'url' => '/some/path',
    //          'method' => 'POST',
    //      ],
    //      "response" => [
    //          'statusCode' => 501,
    //          'content-type' => 'application/json',
    //          'body' => '{"message":"Some error occured."}',
    //      ]
    // ]
    $ex->getContext();
    
    // It's serializable too.
    json_encode($ex);
}
```

## Basic Authentication

You can specify a username and password for basic authentication using the `auth` option.

```PHP
use Garden\Http\HttpClient;

$api = new HttpClient('https://httpbin.org');
$api->setDefaultOption('auth', ['username', 'password123']);

// This request is made with the default authentication set above.
$r1 = $api->get('/basic-auth/username/password123');

// This request overrides the basic authentication.
$r2 = $api->get('/basic-auth/username/password', [], [], ['auth' => ['username', 'password']]);
```

## Extending the HttpClient through subclassing

If you are going to be calling the same API over and over again you might want to extend the `HttpClient` class
to make an API client that is more convenient to reuse.

```PHP
use Garden\Http\HttpClient;
use Garden\Http\HttpHandlerInterface

// A custom HTTP client to access the github API.
class GithubClient extends HttpClient {

    // Set default options in your constructor.
    public function __construct(HttpHandlerInterface $handler = null) {
        parent::__construct('https://api.github.com', $handler);
        $this
            ->setDefaultHeader('Content-Type', 'application/json')
            ->setThrowExceptions(true);
    }

    // Use a default header to authorize every request.
    public function setAccessToken($token) {
        $this->setDefaultHeader('Authorization', "Bearer $token");
    }

    // Get the repos for a given user.
    public function getRepos($username = '') {
        if ($username) {
            return $this->get("/users/$username/repos");
        } else {
            return $this->get("/user/repos"); // my repos
        }
    }

    // Create a new repo.
    public function createRepo($name, $description, $private) {
        return $this->post(
            '/user/repos',
            ['name' => $name, 'description' => $description, 'private' => $private]
        );
    }

    // Get a repo.
    public function getRepo($owner, $repo) {
        return $this->get("/repos/$owner/$repo");
    }

    // Edit a repo.
    public function editRepo($owner, $repo, $name, $description = null, $private = null) {
        return $this->patch(
            "/repos/$owner/$repo",
            ['name' => $name, 'description' => $description, 'private' => $private]
        );
    }

    // Different APIs will return different responses on errors.
    // Override this method to handle errors in a way that is appropriate for the API.
    public function handleErrorResponse(HttpResponse $response, $options = []) {
        if ($this->val('throw', $options, $this->throwExceptions)) {
            $body = $response->getBody();
            if (is_array($body)) {
                $message = $this->val('message', $body, $response->getReasonPhrase());
            } else {
                $message = $response->getReasonPhrase();
            }
            throw new \HttpResponseExceptionException($response, $message);
        }
    }
}
```

## Extending the HttpClient with middleware

The `HttpClient` class has an `addMiddleware()` method that lets you add a function that can modify the request and response before and after being sent. Middleware lets you develop a library of reusable utilities that can be used with any client. Middleware is good for things like advanced authentication, caching layers, CORS support, etc.

### Writing middleware

Middleware is a callable that accepts two arguments: an `HttpRequest` object, and the next middleware. Each middleware must return an `HttpResponse` object.

```php
function (HttpRequest $request, callable $next): HttpResponse {
    // Do something to the request.
    $request->setHeader('X-Foo', '...');
    
    // Call the next middleware to get the response.
    $response = $next($request);
    
    // Do something to the response.
    $response->setHeader('Cache-Control', 'public, max-age=31536000');
    
    return $response;
}
```

You have to call `$next` or else the request won't be processed by the `HttpClient`. Of course, you may want to short circuit processing of the request in the case of a caching layer so in that case you can leave out the call to `$next`.

### Example: Modifying the request with middleware

Consider the following class that implements HMAC SHA256 hashing for a hypothetical API that expects more than just a static access token.

```php
class HmacMiddleware {
    protected $apiKey;

    protected $secret;

    public function __construct(string $apiKey, string $secret) {
        $this->apiKey = $apiKey;
        $this->secret = $secret;
    }

    public function __invoke(HttpRequest $request, callable $next): HttpResponse {
        $msg = time().$this->apiKey;
        $sig = hash_hmac('sha256', $msg, $this->secret);

        $request->setHeader('Authorization', "$msg.$sig");

        return $next($request);
    }
}
```

This middleware calculates a new authorization header for each request and then adds it to the request. It then calls the `$next` closure to perform the rest of the request.

## The HttpHandlerInterface

In Garden HTTP, requests are executed with an HTTP handler. The currently included default handler executes requests with cURL. However, you can implement the the `HttpHandlerInterface` however you want and completely change the way requests are handled. The interface includes only one method:

```php
public function send(HttpRequest $request): HttpResponse;
```

The method is supposed to transform a request into a response. To use it, just pass an `HttpRequest` object to it.

You can also use your custom handler with the `HttpClient`. Just pass it to the constructor:

```json
$api = new HttpClient('https://example.com', new CustomHandler());
```

## Inspecting requests and responses

Sometimes when you get a response you want to know what request generated it. The `HttpResponse` class has an `getRequest()` method for this. The `HttpRequest` class has a `getResponse()` method for the inverse.

Exceptions that are thrown from `HttpClient` objects are instances of the `HttpResponseException` class. That class has `getRequest()` and `getResponse()` methods so that you can inspect both the request and the response for the exception. This exception is of particular use since request objects are created inside the client and not by the programmer directly.

## Mocking for Tests

An `HttpHandlerInterface` implementation and utilities are provided for mocking requests and responses.

### Setup

```php
use Garden\Http\HttpClient
use Garden\Http\Mocks\MockHttpHandler;

// Manually apply the handler.
$httpClient = new HttpClient();
$mockHandler = new MockHttpHandler();
$httpClient->setHandler($mockHandler);

// Automatically apply a handler to `HttpClient` instances.
// You can call this again later to retrieve the same handler.
$mockHandler = MockHttpHandler::mock();

// Don't forget this in your phpunit `teardown()`
MockHttpHandler::clearMock();;

// Reset the handler instance
$mockHandler->reset();
```

### Mocking Requests

```php
use Garden\Http\Mocks\MockHttpHandler;
use Garden\Http\Mocks\MockResponse;

// By default this will return 404 for all requests.
$mockHttp = MockHttpHandler::mock();

$mockHttp
    // Explicit request and response
    ->addMockRequest(
        new \Garden\Http\HttpRequest("GET", "https://domain.com/some/url"),
        new \Garden\Http\HttpResponse(200, ["content-type" => "application/json"], '{"json": "here"}'),
    )
    // Shorthand
    ->addMockRequest(
        "GET https://domain.com/some/url",
        MockResponse::json(["json" => "here"])
    )
    // Even shorter-hand
    // Mocking 200 JSON responses to GET requests is very easy.
    ->addMockRequest(
        "https://domain.com/some/url",
        ["json" => "here"]
    )
    
    // Wildcards
    // Wildcards match with lower priority than explicitly matching requests.
    
    // Explicit wildcard hostname.
    ->addMockRequest("https://*/some/path", MockResponse::success())
    // Implied wildcard hostname.
    ->addMockRequest("/some/path", MockResponse::success())
    // wildcard in path
    ->addMockRequest("https://some-doain.com/some/*", MockResponse::success())
    // Total wildcard
    ->addMockRequest("*", MockResponse::notFound())
;

// Mock multiple requests at once
$mockHttp->mockMulti([
    "GET /some/path" => MockResponse::success()
    "POST /other/path" => MockResponse::json([])
]);
```

### Response Sequences 

Anywhere you can use a mocked `HttpResponse` you can also use a `MockHttpSequence`.

Each item pushed into the sequence will return exactly once. Once that response has been returned it will not be returned again.

If the whole sequence is exhausted it will return 404 responses.

```php
use Garden\Http\Mocks\MockHttpHandler;
use Garden\Http\Mocks\MockResponse;

$mockHttp = MockHttpHandler::mock();

$mockHttp->mockMulti([
    "GET /some/path" => MockResponse::sequence()
        ->push(new \Garden\Http\HttpResponse(500, [], ""))
        ->push(MockResponse::success())
        ->push(MockResponse::json([])
        ->push([]) // Implied json
    ,
]);
```

### Response Functions

You can make a mock dynamic by providing a callable.

```php
use Garden\Http\Mocks\MockHttpHandler;
use Garden\Http\Mocks\MockResponse;
use \Garden\Http\HttpRequest;
use \Garden\Http\HttpResponse;

$mockHttp = MockHttpHandler::mock();
$mockHttp->addMockRequest("*", function (\Garden\Http\HttpRequest $request): HttpResponse {
    return MockResponse::json([
        "requestedUrl" => $request->getUrl(),
    ]);
})
```

### Assertions about requests

Some utilities are provided to make assertions against requests that were made. This can be particularly useful with a wildcard response.

```php
use Garden\Http\Mocks\MockHttpHandler;
use Garden\Http\Mocks\MockResponse;
use Garden\Http\HttpRequest;

$mockHttp = MockHttpHandler::mock();

$mockHttp->addMockRequest("*", MockResponse::success());

// Ensure no requests were made.
$mockHttp->assertNothingSent();

// Check that a request was made
$foundRequest = $mockHttp->assertSent(fn (HttpRequest $request) => $request->getUri()->getPath() === "/some/path");

// Check that a request was not made.
$foundRequest = $mockHttp->assertNotSent(fn (HttpRequest $request) => $request->getUri()->getPath() === "/some/path");

// Clear the history (and mocked requests)
$mockHttp->reset();
```
