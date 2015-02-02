<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Http\Tests;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;


class HttpClientTest extends \PHPUnit_Framework_TestCase {
    /**
     * @return HttpClient
     */
    public function getApi() {
        $api = new HttpClient();
        $api->setBaseUrl('http://garden-http.dev/')
            ->setThrowExceptions(true);
        return $api;
    }


    public function testServerAccess() {
        $api = $this->getApi();

        $response = $api->get('/request.json');
        $data = $response->getBody();
    }

    public function testBasicAuth() {
        $api = $this->getApi();
        $api->setDefaultOption('username', 'foo')
            ->setDefaultOption('password', 'bar');

        $response = $api->get('/basic-protected/foo/bar.json');
        $data = $response->getBody();
    }
}
