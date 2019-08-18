<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;

/**
 * An interface for handling HttpRequests and turning them into HttpResponses.
 *
 */
interface HttpHandlerInterface {
    /**
     * Send the request.
     *
     * @param HttpRequest $request The request to send.
     * @return HttpResponse Returns the response corresponding to the request.
     */
    public function send(HttpRequest $request): HttpResponse;
}
