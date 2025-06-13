<?php
namespace Garden\Http\Tests;

use Garden\Http\HttpClient;
use Garden\Http\ProxyMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Tests for using the HttpClient with {@link ProxyMiddleware}
 */
class ProxyMiddlewareTest extends TestCase {

    /**
     * Test that the proxied URI is applied correctly.
     * 
     * @return void
     */
    public function testProxiedUriIsApplied(): void {
        $proxyHostname = "0.0.0.0:8091";
        
        $client = new HttpClient("https://example.com/base-url");
        $client->addMiddleware(new ProxyMiddleware($proxyHostname, downgradeScheme: true));
        $response = $client->get("/api?query#hash");
        
        $request = $response->getRequest();
        $this->assertEquals("http://$proxyHostname/base-url/api?query#hash", $request->getUrl());
        $this->assertEquals("example.com", $request->getHeader("Host"));
        
        // If we have a response exception it's message will indicate the proxied URL
        $this->assertEquals(
            <<<MESSAGE
Request "GET https://example.com/base-url/api?query#hash (proxied through http://0.0.0.0:8091/base-url/api?query#hash)" failed with a response code of 404 and a standard message of "Not Found"
MESSAGE
,
            $response->asException()->getMessage()
        );
    }
}
