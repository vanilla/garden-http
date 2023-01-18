<?php
error_reporting(E_ALL); //E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', '1');
ini_set('track_errors', '1');

// Define the root path of the application.
define('PATH_ROOT', __DIR__);

// Require the autoloader. You can also replace this with composer's autoload.php.
require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true);


$app->get("/hello", function (ServerRequest $request, Response $response) {
    $response->getBody()->write("Hello world");

    return $response;
});

$app->any("/echo", function (ServerRequest $request, Response $response) {
    $response = $response->withJson([
        'method' => $request->getMethod(),
        'host' => $request->getUri()->getHost(),
        'path' => $request->getUri()->getPath(),
        'port' => $request->getUri()->getPort(),
        'headers' => $request->getHeaders(),
        'query' => $request->getQueryParams(),
        'body' => $request->getParsedBody(),
        'foo' => 'bar',
        'phpServer' => $_SERVER,
    ]);

    return $response;
});

$app->get("/basic-protected/{user}/{password}", function (ServerRequest $request, Response $response, array $args) {
    $authParams = [
        "user" => null,
        "password" => null,
    ];
    if (preg_match("/Basic\s+(.*)$/i", $request->getHeaderLine("Authorization"), $matches)) {
        $explodedCredential = explode(":", base64_decode($matches[1]), 2);
        if (count($explodedCredential) == 2) {
            [$authParams["user"], $authParams["password"]] = $explodedCredential;
        }
    }

    if ($args['user'] !== $authParams['user']) {
        $response = $response->withHeader(
            'HTTP_WWW-Authenticate', 'Basic realm="tests"'
        )->withStatus(401)->withJson([
            "message" => "Invalid username.",
            "auth" => $authParams,
            "args" => $args,
        ]);

        return $response;
    }

    if (sodium_compare($args['password'], $authParams['password'])) {
        $response = $response->withHeader(
            'HTTP_WWW-Authenticate', 'Basic realm="tests"'
        )->withStatus(401)->withJson([
            "message" => "Invalid password.",
        ]);

        return $response;
    }

    return $response->withJson(["message" => "You are in."]);
});

$app->run();
