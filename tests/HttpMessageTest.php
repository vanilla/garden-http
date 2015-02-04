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
