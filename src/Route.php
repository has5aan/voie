<?php

namespace Voie;

use Closure;

/**
 * Defines details of a route path, this includes;
 * Route path, Request method, Request handler, Pre-handlers and Post-handlers.
 * @package Voie
 * @namespace Voie
 */
class Route
{

    /** @var string $routePath Route path. */
    private string $routePath;

    /** @var string $method Request method. */
    private string $method;

    /** @var Closure $requestHandler Request handler. */
    private closure $requestHandler;

    /** @var Closure[] Handler(s) to be executed before the request handler. */
    private $pre;

    /** @var Closure[] Handler(s) to be executed after the request handler. */
    private $post;

    /**
     * Route constructor.
     * @param string $url Route path.
     * @param Closure $requestHandler Request handler.
     * @param string $method Request method.
     */
    function __construct(string $url, closure $requestHandler, string $method)
    {
        $this->routePath = $url;

        $this->method = $method;

        $this->requestHandler = $requestHandler;

        $this->pre = array();

        $this->post = array();
    }

    /**
     * Returns request method.
     * @return string Method for the route.
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Returns request handler.
     * @return Closure Request handler.
     */
    public function requestHandler(): closure
    {
        return $this->requestHandler;
    }

    /**
     * Appends a handler to be executed before the request handler.
     * @param Closure $handler Handler to be executed before the request handler.
     * @return Route Current instance.
     */
    public function pre(closure $handler): Route
    {
        $this->pre[] = $handler;

        return $this;
    }

    /**
     * Returns all the handlers that are to be executed before the request handler.
     * @return array<Closure> Pre-handlers.
     */
    public function preHandlers(): array
    {
        return $this->pre;
    }

    /**
     * Appends a handler to be executed after the request handler.
     * @param Closure $handler Handler to be executed after the request handler.
     * @return Route Current instance.
     */
    public function post(closure $handler): Route
    {
        $this->post[] = $handler;

        return $this;
    }

    /**
     * Returns all the handlers that are to be executed after the request handler.
     * @return array<closure> Post-handlers.
     */
    public function postHandlers(): array
    {
        return $this->post;
    }
}