<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests\Fixtures;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;

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
