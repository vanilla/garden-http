<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http\Tests;

use Garden\Http\HttpRequest;


class HttpMessageTest extends \PHPUnit_Framework_TestCase {

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

        // Make a header string.
        $headerString = implode_assoc("\r\n", ": ", $headers);

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
    }

    public function testHeaderRemove() {
        $msg = new HttpRequest();

        $msg->setHeader('foo', 'bar');
        $this->assertTrue($msg->hasHeader('foo'));

        $msg->setHeader('foo', null);
        $this->assertFalse($msg->hasHeader('foo'));
    }
}
