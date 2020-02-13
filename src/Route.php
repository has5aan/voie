<?php

namespace Voie;

use Closure;

/**
 * Class Route: Defines details of a route path, this includes;
 * Route path (URL Segment), Http request method, Request handler, Pre-handlers and Post-handlers.
 * @package Voie
 */
class Route {

    /** @var string $routePath Route path (URL segment). */
    private $routePath;

    /** @var string $httpVerb Http request method. */
    private $httpVerb;

    /** @var Closure $requestHandler Request handler. */
    private $requestHandler;

    /** @var Closure[] Handler(s) to be executed before the request handler. */
    private $pre;

    /** @var Closure[] Handler(s) to be executed after the request handler. */
    private $post;

    /**
     * Route constructor.
     * @param string $url Route path.
     * @param Closure $requestHandler Request handler.
     * @param string $httpVerb Http request method.
     */
    function __construct($url, $requestHandler, $httpVerb) {
        $this->routePath = $url;

        $this->httpVerb = $httpVerb;

        $this->requestHandler = $requestHandler;

        $this->pre = array();

        $this->post = array();
    }

    /**
     * Returns Http request type.
     * @return string Http request for the route.
     */
    public function httpVerb() {
        return $this->httpVerb;
    }

    /**
     * Returns request handler.
     * @return Closure Request handler.
     */
    public function requestHandler() {
        return $this->requestHandler;
    }

    /**
     * Appends a handler to be executed before the request handler.
     * @param $handler Closure Handler to be executed before the request handler.
     * @return Route Current instance.
     */
    public function pre($handler) {
        $this->pre[] = $handler;

        return $this;
    }

    /**
     * Returns all the handlers that are to be executed before the request handler.
     * @return Closure[] Pre-handlers.
     */
    public function preHandlers() {
        return $this->pre;
    }

    /**
     * Appends a handler to be executed after the request handler.
     * @param $handler Closure Handler to be executed after the request handler.
     * @return Route Current instance.
     */
    public function post($handler) {
        $this->post[] = $handler;

        return $this;
    }

    /**
     * Returns all the handlers that are to be executed after the request handler.
     * @return Closure[] Post-handlers.
     */
    public function postHandlers() {
        return $this->post;
    }
}