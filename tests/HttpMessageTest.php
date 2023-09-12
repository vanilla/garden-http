<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\UriFactory;

/**
 * Contains tests against the {@link HttpMessage}, {@link HttpRequest}, and  {@link HttpResponse}classes.
 */
class HttpMessageTest extends TestCase {

    /**
     * Test basic case-insensitive header access.
     */
    public function testBasicHeaderAccess() {
        $msg = new HttpRequest();

        $msg->setHeader('foo', 'bar');
        $this->assertSame('bar', $msg->getHeader('foo'));
        $this->assertSame('bar', $msg->getHeader('FOO'));
        $this->assertSame('bar', $msg->getHeader('fOO'));
    }


    /**
     * Test that {@link HttpRequest} can get/set basic properties.
     */
    public function testBasicRequestPropertyAccess() {
        $request = new HttpRequest();

        $auth = ['username', 'password'];
        $request->setAuth($auth);
        $this->assertSame($auth, $request->getAuth());

        $body = 'foo=bar';
        $request->setBody($body);
        $this->assertSame($body, $request->getBody());

        $body = ['foo' => 'bar'];
        $request->setBody($body);
        $this->assertSame($body, $request->getBody());

        $request->setMethod('get');
        $this->assertSame('GET', $request->getMethod());

        $pv = '1.0';
        $request->setProtocolVersion($pv);
        $this->assertSame($pv, $request->getProtocolVersion());

        $url = 'http://example.com';
        $request->setUrl($url);
        $this->assertSame($url, $request->getUrl());

        $vps = [false, true];
        foreach ($vps as $vp) {
            $request->setVerifyPeer($vp);
            $this->assertSame($vp, $request->getVerifyPeer());
        }
    }

    /**
     * Test that {@link HttpResponse} can get/set basic properties.
     */
    public function testBasicResponsePropertyAccess() {
        $response = new HttpResponse();

        $rawBody = '{}';
        $response->setRawBody($rawBody);
        $this->assertSame($rawBody, $response->getRawBody());
        $this->assertSame($response->getRawBody(), (string)$response);

        $body = 'foo=bar';
        $response->setBody($body);
        $this->assertSame($body, $response->getBody());

        $body = ['foo' => 'bar'];
        $response->setBody($body);
        $this->assertSame($body, $response->getBody());

        $pv = '1.0';
        $response->setProtocolVersion($pv);
        $this->assertSame($pv, $response->getProtocolVersion());

        $reason = 'Because';
        $response->setReasonPhrase($reason);
        $this->assertSame($reason, $response->getReasonPhrase());

        $code = 222;
        $response->setStatusCode($code);
        $this->assertSame($code, $response->getStatusCode());
    }

    /**
     * Test that {@link HttpMessage::getHeaders()} preserves the case that was set.
     */
    public function testGetHeaders() {
        $msg = new HttpRequest();

        $msg->setHeader('Foo', 'bar');
        $headers = $msg->getHeaders();
        $this->assertArrayHasKey('Foo', $headers);
        $this->assertArrayNotHasKey('foo', $headers);
        $this->assertSame($msg->getHEaderLines('foo'), $headers['Foo']);
    }

    /**
     * Test headers with multiple values.
     */
    public function testMultipleHeaders() {
        $msg = new HttpRequest();

        $msg->setHeader('foo', 'bar')
            ->addHeader('fOO', 'baz');

        $this->assertSame('bar,baz', $msg->getHeader('Foo'));
        $this->assertSame(['bar', 'baz'], $msg->getHeaderLines('FOO'));
    }

    /**
     * Test HTTP headers that are in the form of one giant string.
     */
    public function testStringBlockHeaders() {
        $headers = [
            "X-Cache-Control" => "no-cache",
            "X-Content-Type" => "something/something",
            "X-Content-Encoding" => "null"
        ];

        $carriage = "\r";
        // Make a header string.
        $headerString = <<<HEADERS
X-Cache-Control: no-cache{$carriage}
X-Content-Type: something/something{$carriage}
X-Content-Encoding: null
HEADERS;

        $msg = new HttpRequest();
        $msg->setHeaders($headerString);

        foreach ($headers as $key => $value) {
            $this->assertSame($value, $msg->getHeader($key));
        }

        // Test a header with multiple elements.
        $headerString2 = "X-Multi: one\r\nx-multi: two";
        $msg2 = new HttpRequest();
        $msg2->setHeaders($headerString2);
        $this->assertSame(['one', 'two'], $msg2->getHeaderLines('X-Multi'));
    }

    /**
     * Test overwriting existing headers.
     */
    public function testHeaderOverwrite() {
        $msg = new HttpRequest();

        $msg->setHeader('foo', 'bar');
        $this->assertSame('bar', $msg->getHeader('foo'));

        $msg->setHeader('foo', 'baz');
        $this->assertSame('baz', $msg->getHeader('foo'));

        $msg->setHeader('foo', 'bar')
            ->addHeader('fOO', 'baz')
            ->setHeader('foo', 'world');
        $this->assertSame('world', $msg->getHeader('foo'));

        // PSR-7
        $msg = $msg->withHeader("head1", "val1");
        $this->assertEquals("val1", $msg->getHeaderLine("head1"));
        $msg = $msg->withAddedHeader("head1", "val2");
        $this->assertEquals("val1,val2", $msg->getHeaderLine("head1"));
        $msg = $msg->withHeader("head1", "val3");
        $this->assertEquals("val3", $msg->getHeaderLine("head1"));
        $msg = $msg->withoutHeader("head1");
        $this->assertEquals("", $msg->getHeaderLine("head1"));
    }

    /**
     * Test removing headers by setting them to `null`.
     */
    public function testHeaderRemove() {
        $msg = new HttpRequest();

        $msg->setHeader('foo', 'bar');
        $this->assertTrue($msg->hasHeader('foo'));

        $msg->setHeader('foo', null);
        $this->assertFalse($msg->hasHeader('foo'));
    }

    /**
     * Test the {@link HttpResponse} body and raw body access.
     */
    public function testResponseBodyAccess() {
        $r = new HttpResponse();
        $r->setHeader('Content-Type', 'application/json');

        // Test setting through the raw body.
        $emptyJson = '{}';
        $r->setRawBody($emptyJson);
        $this->assertSame($emptyJson, $r->getRawBody());
        $this->assertSame([], $r->getBody());

        $arrayJson = '{ "foo": "bar" }';
        $r->setRawBody($arrayJson);
        $this->assertSame(json_decode($arrayJson, true), $r->getBody());

        // Test setting throw the body.
        $empty = [];
        $r->setBody($empty);
        $this->assertSame('[]', $r->getRawBody());

        $arr = ['foo' => 'baz'];
        $r->setBody($arr);
        $this->assertJsonStringEqualsJsonString('{"foo":"baz"}', $r->getRawBody());

        foreach ([true, false, 0, 1, 1.25] as $value) {
            $r->setBody($value);
            $this->assertJsonStringEqualsJsonString(json_encode($value), $r->getRawBody());
        }

        // Test a type that can't be decoded.
        $obj = new \stdClass();
        $obj->foo = 'baz';
        $r->setBody($obj);
        $this->assertSame($obj, $r->getBody());
        $this->assertEmpty($r->getRawBody());
    }

    /**
     * Test the {@link HttpResponse} status code classes.
     */
    public function testResponseClasses() {
        $r = new HttpResponse();
        $r->setStatusCode('222');

        $this->assertTrue($r->isSuccessful());

        $this->assertTrue($r->isResponseClass('222'));
        $this->assertTrue($r->isResponseClass('2xx'));
        $this->assertTrue($r->isResponseClass('22x'));
        $this->assertTrue($r->isResponseClass('2x2'));
    }

    /**
     * Test the various response status methods.
     */
    public function testResponseStatuses() {
        // Test setting the status as a code.
        $r = new HttpResponse();
        $this->assertSame(200, $r->getStatusCode());
        $r->setStatus(404);
        $this->assertSame(404, $r->getStatusCode());
        $this->assertSame('Not Found', $r->getReasonPhrase());
        $this->assertSame("404 Not Found", $r->getStatus());

        // Test setting the status with a custom reason phrase.
        $r2 = new HttpResponse();
        $r2->setStatus(401, 'Locked Out');
        $this->assertSame('401 Locked Out', $r2->getStatus());

        // Test setting the status from a full HTTP status line.
        $r3 = new HttpResponse();
        $r3->setStatus('HTTP/1.2 423 Running Cool');
        $this->assertSame(423, $r3->getStatusCode());
        $this->assertSame('Running Cool', $r3->getReasonPhrase());
        $this->assertSame('1.2', $r3->getProtocolVersion());

        // Test setting the status from a partial HTTP status line.
        $r4 = new HttpResponse();
        $r4->setStatus('599 Red Alert');
        $this->assertSame(599, $r4->getStatusCode());
        $this->assertSame('Red Alert', $r4->getReasonPhrase());
    }

    /**
     * Test accessing an {@link HttpResponse} as an array.
     */
    public function testResponseArrayAccess() {
        $r = new HttpResponse();
        $r->setBody(['foo' => 'bar']);

        $this->assertTrue(isset($r['foo']));
        $this->assertSame('bar', $r['foo']);
        $this->assertFalse(isset($r['baz']));
        $this->assertNull($r['not']);

        $r['baz'] = 'ploop';
        $this->assertSame('ploop', $r['baz']);

        unset($r['foo'], $r['baz']);
        $this->assertEmpty($r->getBody());

        $r[] = 'one';
        $r[] = 'two';
        $this->assertSame(['one', 'two'], $r->getBody());
    }

    /**
     * Test requesting to an host that doesn't resolve.
     */
    public function testUnresolvedUrl() {
        $request = new HttpRequest("GET", "http://foo.example");
        $request->setTimeout(1);
        $response = $request->send();

        $this->assertSame(0, $response->getStatusCode());
        $this->assertStringStartsWith("Could not resolve host", $response->getReasonPhrase());
    }

    /**
     * Test the preservation of string keys in parseHeaders().
     */
    public function testHeaderKeyParsing() {
        $msg = new HttpRequest();
        $msg->setHeaders(['foo' => '1', 'bar' => '2']);

        $this->assertSame('1', $msg->getHeader('foo'));
        $this->assertSame('2', $msg->getHeader('bar'));
    }

    /**
     * Test setting headers with a status line.
     */
    public function testParseHeadersWithStatus() {
        $msg = new HttpResponse();
        $msg->setHeaders("HTTP/1.1 201 Bamboozled\r\nFoo: bar\r\nX-Lookup-Mode: normal");

        $this->assertSame('bar', $msg->getHeader('Foo'));
        $this->assertSame('normal', $msg->getHeader('X-Lookup-Mode'));
        $this->assertSame(201, $msg->getStatusCode());
        $this->assertSame('Bamboozled', $msg->getReasonPhrase());
    }

    /**
     * Test header overrides when constructing an {@link HttpResponse}.
     */
    public function testHeaderOverrides() {
        $headerStr = "HTTP/1.1 201 Bamboozled\r\nFoo: bar\r\nX-Lookup-Mode: normal";

        // An explicit status should override the one in the header.
        $msg = new HttpResponse(404, $headerStr);
        $this->assertSame(404, $msg->getStatusCode());
        $this->assertSame('Not Found', $msg->getReasonPhrase());

        // A null status should fetch from the header.
        $msg2 = new HttpResponse(null, $headerStr);
        $this->assertSame(201, $msg2->getStatusCode());
        $this->assertSame('Bamboozled', $msg2->getReasonPhrase());

    }

    /**
     * Test various scenarios where parseStatusLine is called.
     */
    public function testParseStatusLine() {
        // Test parsing the status on an array without a status line.
        $msg1 = new HttpResponse(333, ['Foo' => 'Bar']);
        $this->assertSame(333, $msg1->getStatusCode());

        $msg2 = new HttpResponse(null, ['Baz' => 'Bump']);
        $this->assertSame(200, $msg2->getStatusCode());
    }

    /**
     * Responses that follow redirects will have multiple header blocks.
     */
    public function testRedirectFollowHeaders() {
        $headers = <<<EOT
HTTP/1.1 301 Moved Permanently\r
Content-Type: text/html\r
Date: Tue, 20 Jun 2017 21:10:19 GMT\r
Location: https://example.com\r
Connection: Keep-Alive\r
Content-Length: 0\r
\r
HTTP/1.1 201 CREATED\r
Server: nginx/1.10.1\r
Date: Tue, 20 Jun 2017 21:10:20 GMT\r
Content-Type: application/json; charset=utf-8\r
Transfer-Encoding: chunked\r
Connection: keep-alive\r
P3P: CP="CAO PSA OUR"\r
Cache-Control: no-cache\r
Expires: Tue, 20 Jun 2017 21:10:19 GMT\r
Pragma: no-cache\r
X-Frame-Options: SAMEORIGIN\r
X-Content-Type-Options: nosniff\r
Strict-Transport-Security: max-age=63072000; includeSubdomains; preload\r
Content-Encoding: gzip\r
EOT;

        $response = new HttpResponse(null, $headers, '{"foo": "bar"}');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Location'));
    }

    /**
     * A response should include the request that generated it.
     */
    public function testRequestOnResponse() {
        $request = new HttpRequest('GET', 'http://garden-http.dev:8080/hello', '', ['Referer' => __CLASS__]);
        $response = $request->send();

        $this->assertSame($request, $response->getRequest());
    }

    /**
     * A basic smoke test for `HttpResponse::reasonPhrase()`.
     */
    public function testReasonPhrase(): void {
        $this->assertSame('OK', HttpResponse::reasonPhrase(200));
        $this->assertNull(HttpResponse::reasonPhrase(4234324));
    }

    /**
     * Test various PSR-7 compatible methods on the request objects.
     */
    public function testPsr7Methods() {
        $request = new HttpRequest("GET", "https://some-place.com/path?query", "", ["header1" => "vla"]);
        $request = $request->withProtocolVersion("2.0");
        $this->assertEquals("2.0", $request->getProtocolVersion());

        $uriFactory = new UriFactory();
        $newUrl = "https://other-place.com/other-path?query2";
        $newUri = $uriFactory->createUri($newUrl);
        $newUriReq = $request->withUri($newUri);
        $this->assertEquals($newUrl, $newUriReq->getUrl());
        $this->assertNotEquals($newUrl, $request->getUrl());

        $postReq = $newUriReq->withMethod("POST");
        $this->assertEquals("POST", $postReq->getMethod());
        $this->assertEquals("/other-path?query2", $postReq->getRequestTarget());

        $otherTargetReq = $newUriReq->withRequestTarget("/new-path?query3");
        $this->assertEquals("https://other-place.com/new-path?query3", $otherTargetReq->getUrl());
    }
}
