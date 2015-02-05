Garden HTTP
===========

[![Build Status](https://img.shields.io/travis/vanilla/garden-http.svg?style=flat)](https://travis-ci.org/vanilla/garden-http) [![Coverage](http://img.shields.io/scrutinizer/coverage/g/vanilla/garden-http.svg?style=flat)](https://scrutinizer-ci.com/g/vanilla/garden-http/)

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

if (!$response->isSuccessful()) {
    $this->markTestSkipped();
}
$this->assertInternalType('array', $data);
$this->assertSame(['foo' => 'bar'], $posted);
```

Throwing Exceptions
-------------------

You can tell the http client to throw an exception on unsuccessful requests.

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
$api->setDefaultOption('auth', ['username', 'password']);

// This request is made with the default authentication set above.
$r1 = $api->get('/basic-auth/username/password');

// This request overrides the basic authentication.
$r2 = $api->get('/basic-auth/username/password123', [], [], ['auth' => ['username', 'password123']]);
```
