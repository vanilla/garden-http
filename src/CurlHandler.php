<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Http;

/**
 * A handler that uses cURL to send the request.
 */
class CurlHandler implements HttpHandlerInterface {
    /**
     * Create the cURL resource that represents this request.
     *
     * @param HttpRequest $request The request to create the cURL resource for.
     * @return resource Returns the cURL resource.
     * @see curl_init(), curl_setopt(), curl_exec()
     */
    protected function createCurl(HttpRequest $request) {
        $ch = curl_init();

        // Add the body first so we can calculate a content length.
        $body = '';
        if ($request->getMethod() === HttpRequest::METHOD_HEAD) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        } elseif ($request->getMethod() !== HttpRequest::METHOD_GET) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->getMethod());

            $body = $this->makeCurlBody($request);
            if ($body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        // Encode the headers.
        $headers = [];
        foreach ($request->getHeaders() as $key => $values) {
            foreach ($values as $line) {
                $headers[] = "$key: $line";
            }
        }

        if (is_string($body) && !$request->hasHeader('Content-Length')) {
            $headers[] = 'Content-Length: '.strlen($body);
        }

        if (!$request->hasHeader('Expect')) {
            $headers[] = 'Expect:';
        }

        curl_setopt(
            $ch,
            CURLOPT_HTTP_VERSION,
            $request->getProtocolVersion() == '1.0' ? CURL_HTTP_VERSION_1_0 : CURL_HTTP_VERSION_1_1
        );

        curl_setopt($ch, CURLOPT_URL, $request->getUrl());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request->getTimeout());
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $request->getVerifyPeer());
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $request->getVerifyPeer() ? 2 : 0);
        curl_setopt($ch, CURLOPT_ENCODING, ''); //"utf-8");
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if (!empty($request->getAuth())) {
            curl_setopt($ch, CURLOPT_USERPWD, $request->getAuth()[0].":".((empty($request->getAuth()[1])) ? "" : $request->getAuth()[1]));
        }

        return $ch;
    }

    /**
     * Convert the request body into a format suitable to be passed to curl.
     *
     * @param HttpRequest $request The request to turn into the cURL body.
     * @return string|array Returns the curl body.
     */
    protected function makeCurlBody(HttpRequest $request) {
        $body = $request->getBody();

        if (is_string($body)) {
            return (string)$body;
        }

        $contentType = $request->getHeader('Content-Type');
        if (stripos($contentType, 'application/json') === 0) {
            $body = json_encode($body);
        }

        return $body;
    }

    /**
     * Execute a curl handle.
     *
     * This method just calls `curl_exec()` and returns the result. It is meant to stay this way for easier subclassing.
     *
     * @param resource $ch The curl handle to execute.
     * @return HttpResponse Returns an {@link RestResponse} object with the information from the request
     */
    protected function execCurl($ch) {
        $response = curl_exec($ch);

        return $response;
    }

    /**
     * Decode a curl response and turn it into
     *
     * @param $ch
     * @param $response
     * @return HttpResponse
     */
    protected function decodeCurlResponse($ch, $response): HttpResponse {
        // Split the full response into its headers and body
        $info = curl_getinfo($ch);
        $code = $info["http_code"];
        if ($response) {
            $header_size = $info["header_size"];
            $rawHeaders = substr($response, 0, $header_size);
            $status = null;
            $rawBody = substr($response, $header_size);
        } else {
            $status = $code;
            $rawHeaders = [];
            $rawBody = curl_error($ch);
        }

        $result = new HttpResponse($status, $rawHeaders, $rawBody);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function send(HttpRequest $request): HttpResponse {
        $ch = $this->createCurl($request);
        $curlResponse = $this->execCurl($ch);
        $response = $this->decodeCurlResponse($ch, $curlResponse);
        curl_close($ch);
        $response->setRequest($request);

        return $response;
    }
}
