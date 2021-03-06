<?php
namespace Garden\Http\Tests {
    error_reporting(E_ALL); //E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
    ini_set('display_errors', '1');
    ini_set('track_errors', '1');

    // Define the root path of the application.
    define('PATH_ROOT', __DIR__);

    // Require the autoloader. You can also replace this with composer's autoload.php.
    require_once __DIR__.'/../vendor/autoload.php';
}

namespace Garden {
    use Garden\Exception\ClientException;

    // Instantiate the application.
    $app = new Application();

    // Register routes.
    $app->route('/hello', function () {
        return [200, [], 'Hello world'];
    });

    $app->route('/echo(/.+)?', function () use ($app) {
        $request = $app->request;

        $result = [
            'method' => $request->getMethod(),
            'host' => $request->getHost(),
            'path' => $request->getFullPath(),
            'port' => $request->getPort(),
            'headers' => $request->getHeaders(),
            'query' => $request->getQuery(),
            'body' => $request->getInput(),
            'foo' => 'bar',
            'phpServer' => $_SERVER
        ];

        return $result;
    });

    $app->route('/basic-protected/{username}/{password}', function ($username = '', $password = '') use ($app) {
        $request = $app->request;

        if ($request->getEnv('PHP_AUTH_USER') !== $username) {
            throw new ClientException('Invalid username.', 401, ['HTTP_WWW-Authenticate' => 'Basic realm="tests"']);
        } elseif ($request->getEnv('PHP_AUTH_PW') !== $password) {
            throw new ClientException('Invalid password.', 401, ['HTTP_WWW-Authenticate' => 'Basic realm="tests"']);
        } else {
            return ['message' => 'You are in.'];
        }
    });

    // Run the application.
    $app->run();
}

