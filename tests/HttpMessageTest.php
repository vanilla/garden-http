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
        $message = new HttpRequest();

        $message->setHeader('foo', 'bar');
        $this->assertSame('bar', $message->getHeader('foo'));
        $this->assertSame('bar', $message->getHeader('FOO'));
        $this->assertSame('bar', $message->getHeader('fOO'));
    }

    /**
     * Test that {@link HttpMessage::getHeaders()} preserves the case that was set.
     */
    public function testGetHeaders() {
        $message = new HttpRequest();

        $message->setHeader('Foo', 'bar');
        $headers = $message->getHeaders();
        $this->assertArrayHasKey('Foo', $headers);
        $this->assertArrayNotHasKey('foo', $headers);
        $this->assertSame($message->getHEaderLines('foo'), $headers['Foo']);
    }

    /**
     * Test headers with multiple values.
     */
    public function testMultipleHeaders() {
        $message = new HttpRequest();

        $message
            ->setHeader('foo', 'bar')
            ->addHeader('fOO', 'baz');

        $this->assertSame('bar,baz', $message->getHeader('Foo'));
        $this->assertSame(['bar', 'baz'], $message->getHeaderLines('FOO'));
    }
}
