Garden HTTP
===========

[![Build Status](https://img.shields.io/travis/vanilla/garden-http.svg?style=flat-square)](https://travis-ci.org/vanilla/garden-http)
[![Coverage](http://img.shields.io/scrutinizer/coverage/g/vanilla/garden-http.svg?style=flat-square)](https://scrutinizer-ci.com/g/vanilla/garden-http/)

Garden HTTP is an unbloated HTTP client library for building RESTful API clients. It's meant to allow you to access
people's APIs without having to copy/paste a bunch of cURL setup and without having to double the size of your codebase.
You can use this library as is for quick API clients or extend the `HttpClient` class to make structured API clients
that you use regularly.

Installation
------------

*Garden HTTP requires PHP 5.4 or higher and libcurl*

Garden HTTP is [PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md) compliant and can be installed using [composer](//getcomposer.org). Just add `vanilla/garden-http` to your composer.json.

```json
"require": {
    "vanilla/garden-http": "~1.0"
}
```

Basic Example
-------------

Almost all uses of Garden HTTP involve first creating an `HttpClient` object and then making requests from it.
You can see below a default header is also set to pass a standard header to every request made with the client.

```PHP

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

Throwing Exceptions
-------------------

You can tell the HTTP client to throw an exception on unsuccessful requests.

```PHP
use Garden\Http\HttpClient;

$api = new HttpClient('https://httpbin.org');
$api->setThrowExceptions(true);

try {
    $api->get('/status/404');
} catch (\Exception $ex) {
    $code = $ex->getCode(); // should be 404
    throw $ex;
}
```

Basic Authentication
--------------------

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

Extending the HttpClient
-------------------------

If you are going to be calling the same API over and over again you might want to extend the `HttpClient` class
to make an API client that is more convenient to reuse.

```PHP
use Garden\Http\HttpClient;

// A custom HTTP client to access the github API.
class GithubClient extends HttpClient {

    // Set default options in your constructor.
    public function __construct() {
        parent::__construct('https://api.github.com');
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
            throw new \Exception($message, $response->getStatusCode());
        }
    }
}
```
