<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests\Fixtures;

use Garden\Http\HttpHandlerInterface;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;

class EchoHandler implements HttpHandlerInterface
{
    public function send(HttpRequest $request): HttpResponse
    {
        $url = parse_url($request->getUrl(), PHP_URL_QUERY);
        if ($url) {
            parse_str($url, $get);
        } else {
            $get = [];
        }

        $response = new HttpResponse();
        $response->setBody([
            "method" => $request->getMethod(),
            "url" => $request->getUrl(),
            "headers" => $request->getHeaders(),
            "get" => $get,
            "body" => $request->getBody(),
        ]);

        $response->setRequest($request);
        $request->setResponse($response);

        return $response;
    }
}
