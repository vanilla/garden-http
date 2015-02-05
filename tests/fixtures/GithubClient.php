<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

use Garden\Http\HttpClient;

// A custom HTTP client to access the github API.
class GithubClient extends HttpClient {

    // Set default options in your constructor.
    public function __construct() {
        parent::__construct('https://api.github.com');
        $this
            ->setDefaultHeader('Content-Type', 'application/json')
            ->setThrowExceptions(true);
    }

    // Use a default header to authorize every request.
    public function setAccessToken($token) {
        $this->setDefaultHeader('Authorization', "Bearer $token");
    }

    // Get the repos for a given user.
    public function getRepos($username = '') {
        if ($username) {
            return $this->get("/users/$username/repos");
        } else {
            return $this->get("/user/repos"); // my repos
        }
    }

    // Create a new repo.
    public function createRepo($name, $description, $private) {
        return $this->post(
            '/user/repos',
            ['name' => $name, 'description' => $description, 'private' => $private]
        );
    }

    // Get a repo.
    public function getRepo($owner, $repo) {
        return $this->get("/repos/$owner/$repo");
    }

    // Edit a repo.
    public function editRepo($owner, $repo, $name, $description = null, $private = null) {
        return $this->patch(
            "/repos/$owner/$repo",
            ['name' => $name, 'description' => $description, 'private' => $private]
        );
    }

    // Different APIs will return different responses on errors.
    // Override this method to handle errors in a way that is appropriate for the API.
    public function handleErrorResponse(HttpResponse $response, $options = []) {
        if ($this->val('throw', $options, $this->throwExceptions)) {
            $body = $response->getBody();
            if (is_array($body)) {
                $message = $this->val('message', $body, $response->getReasonPhrase());
            } else {
                $message = $response->getReasonPhrase();
            }
            throw new \Exception($message, $response->getStatusCode());
        }
    }
}
