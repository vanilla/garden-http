<?php
namespace Garden\Http;

/**
 * An interface for middleware that processes HTTP requests and responses.
 *
 * Middleware can modify the request before passing it to the next handler,
 * or modify the response after it has been processed by the next handler.
 */
interface MiddlewareInterface {

    /**
     * Call next to execute the next middleware or handler in the chain (next returns the response).
     * 
     * @param HttpRequest $request
     * @param callable(HttpRequest): HttpResponse $next
     *
     * @return HttpResponse
     */
    public function __invoke(HttpRequest $request, callable $next): HttpResponse;
}