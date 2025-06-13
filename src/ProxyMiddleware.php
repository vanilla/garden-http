<?php
namespace Garden\Http;

use Slim\Psr7\Uri;

/**
 * Middleware to proxy requests through a proxy.
 */
class ProxyMiddleware implements MiddlewareInterface {


    /**
     * @param string $proxyHostname The hostname of the proxy to use.
     * @param bool $downgradeScheme Whether to downgrade the scheme of the request from https to http.
     */
    public function __construct(protected string $proxyHostname, protected bool $downgradeScheme = true) { }

    /**
     * @inheritdoc
     */
    public function __invoke(HttpRequest $request, callable $next): HttpResponse
    {
        $this->alterRequest($request);
        return $next($request, $next);
    }

    /**
     * Given a url, try to replace it's base url so it routes with the cluster router.
     *
     * @param string $url
     *
     * @return void
     */
    protected function alterRequest(HttpRequest $request): void
    {
        /** @var Uri $uri */
        $originalUri = $request->getUri();

        $uri = $originalUri;
        if ($this->downgradeScheme && $uri->getScheme() === "https") {
            $uri = $uri->withScheme("http");
        }

        $requestUri = $uri->withHost($this->proxyHostname);

        $request->setUrl($requestUri);
        $request->setHeader("Host", $originalUri->getHost());
        $request->setProxiedToUri($originalUri);
    }
}
