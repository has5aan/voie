<?php

namespace Voie;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new \Exception(sprintf("%s:%s(%s):%s%s", $errno, $errfile, $errline, $errstr, PHP_EOL), 500);
}, E_ALL);

use Closure;
use Exception;
use stdClass;

/**
 * Provides an infrastructure to route requests analogous to HTTP request methods through an instance object.
 *
 * Allows registering different route handlers. Each route handler responds to a route path and a specific
 * request method. Route handlers against a request method can be registered using, get(), put(), patch(), post()
 * and delete().
 *
 * Router object also allows registering request handler only against the provided request method without a route path.
 * The request handler is registered using requestHandler().
 *
 * Every request handler can also have a sequence of predecessors and successors that are executed in their
 * chronological order, before and after the execution of the actual request handler.
 *
 * Initializers can be defined for the router object using initialize(). If defined, initialization logic
 * is executed for each request at the beginning of the request processing pipeline.
 *
 * Router object can also contain middleware logic defined using middleware(). If defined, the middleware logic
 * is executed for each request after the execution of initializers.
 *
 * Error handling and destruction logic can be defined using errorHandler() and destructor().
 *
 * Summary: Router object provides an approach to build a request processing pipeline through request handlers for
 * a specific route using get(), put(), post(), patch() and delete(). It can also act as a script with a single
 * request handler for any Http request method that is defined through requestHandler(). Allows the construction of
 * request processing pipeline using initialize() and middleware(). errorHandler() and destructor()
 * provides a mechanism to define error handling and destruction logic.
 *
 * All the middleware logic leading to the execution of the request against a request handler is executed in
 * chronological order after the execution of initialization logic.
 *
 * @package Voie
 */
class Router extends stdClass
{
    /** @var Route[] Contains route handlers against a corresponding route path. */
    private array $routes;

    /** @var array<Closure> Contains initialization logic. */
    private array $initializers;

    /** @var array<Closure> Contains middleware logic. */
    private array $middleware;

    /** @var Route[] Contains request handler for script mode against a combination of Http request methods. */
    private array $requestHandlers;

    /** @var array<Closure> Contains the dispatch logic. */
    private array $dispatchers;

    /** @var array<Closure> Contains destruction logic. */
    private array $destructors;

    /** @var array<Closure> Contains error handling logic. */
    private array $errorHandlers;

    /** Initializes the internal data structures to maintain routes, middleware and error handlers. */
    public function __construct()
    {
        $this->routes = array();
        $this->initializers = array();
        $this->middleware = array();
        $this->dispatchers = array();
        $this->destructors = array();
        $this->requestHandlers = array();
        $this->errorHandlers = array();
    }

    /**
     * Appends a route that responds to GET requests against a route path.
     * @param string $routePath Url route path.
     * @param Closure $requestHandler Defines the logic to handle the Http request.
     * @return Route Route object added to the route collection.
     */
    public function get(string $routePath, Closure $requestHandler): Route
    {
        return $this->addRoute($routePath, $requestHandler, 'GET');
    }

    /**
     * Appends a route that responds to PUT requests against a route path.
     * @param string $routePath Url route path.
     * @param Closure $requestHandler Defines the logic to handle the Http request.
     * @return Route Route object added to the route collection.
     */
    public function put(string $routePath, Closure $requestHandler): Route
    {
        return $this->addRoute($routePath, $requestHandler, 'PUT');
    }

    /**
     * Appends a route that responds to PATCH requests against a route path.
     * @param string $routePath Url route path.
     * @param Closure $requestHandler Defines the logic to handle the Http request.
     * @return Route Route object added to the route collection.
     */
    public function patch(string $routePath, Closure $requestHandler): Route
    {
        return $this->addRoute($routePath, $requestHandler, 'PATCH');
    }

    /**
     * Appends a route that responds to POST requests against a route path.
     * @param string $routePath Url route path.
     * @param Closure $requestHandler Defines the logic to handle the Http request.
     * @return Route Route object added to the route collection.
     */
    public function post(string $routePath, closure $requestHandler): Route
    {
        return $this->addRoute($routePath, $requestHandler, 'POST');
    }

    /**
     * Appends a route that responds to DELETE requests against a route path.
     * @param string $routePath Url route path.
     * @param Closure $requestHandler Defines the logic to handle the Http request.
     * @return Route Route object added to the route collection.
     */
    public function delete(string $routePath, closure $requestHandler): Route
    {
        return $this->addRoute($routePath, $requestHandler, 'DELETE');
    }

    /**
     * Appends a route path for the specified request method and its request handler.
     * @param string $routePath Route path.
     * @param Closure $requestHandler Route handler.
     * @param string $method Request method.
     * @return Route The route object added to the route collection.
     */
    public function addRoute(string $routePath, closure $requestHandler, string $method): Route
    {
        $route = new Route($routePath, $requestHandler, $method);

        $this->routes[$routePath] = $route;

        return $route;
    }

    /**
     * Appends the request handler against the specified request method.
     * @param string $requestMethod Request method.
     * @param Closure $requestHandler Defines the logic to handle the request.
     * @return Route The added Route object.
     */
    public function requestHandler(string $requestMethod, closure $requestHandler): Route
    {
        $route = new Route(null, $requestHandler, $requestMethod);

        $this->requestHandlers[$requestMethod] = $route;

        return $route;
    }

    /**
     * Appends initialization logic to the request handling pipeline.
     * @param Closure $handler Defines the initialization logic.
     * @return Router Current instance.
     */
    public function initialize(closure $handler): Router
    {
        $this->initializers[] = $handler;

        return $this;
    }

    /**
     * Appends middleware logic to the request handling pipeline.
     * @param Closure $handler Defines the middleware logic.
     * @return Router Current instance.
     */
    public function middleware(closure $handler): Router
    {
        $this->middleware[] = $handler;

        return $this;
    }

    /**
     * Appends the dispatch logic to the request handling pipeline.
     * @param Closure $handler
     * @return Router Current instance.
     */
    public function dispatcher(closure $handler): Router
    {
        $this->dispatchers[] = $handler;

        return $this;
    }

    /**
     * Appends error handling logic to the request handling pipeline.
     * @param Closure $errorHandler Defines the error handling logic.
     * @return Router Current instance.
     */
    public function errorHandler(closure $errorHandler): Router
    {
        $this->errorHandlers[] = $errorHandler;

        return $this;
    }

    /**
     * Appends destruction logic to the request handling pipeline.
     * @param Closure $handler Defines the destruction logic.
     * @return Router Current instance.
     */
    public function destructor(closure $handler): Router
    {
        $this->destructors[] = $handler;

        return $this;
    }

    /**
     * Appends services to the request handling pipeline.
     * @param mixed $service Service to be injected.
     * @return Router Current instance.
     */
    public function service($service): Router
    {
        $this->services[] = $service;

        return $this;
    }

    /**
     * Executes the request handler for a specific request method defined against a route path.
     * The route path is provided through the $routePath parameter.
     *
     * Triggers error if the route handler for the specified route handler against the Http request method is not
     * defined.
     *
     * The execution of every request handler is preceded by the execution of middleware handlers in chronological
     * order.
     *
     * The execution of defined middleware is followed by the pre handlers for the request handler.
     *
     * This is followed by the execution of request handler.
     *
     * The execution of post handlers for the request handler continues after the request handler.
     *
     * @param string $requestMethod The request method.
     * @param boolean|string $routePath Url route path against which the request handler logic is to be executed.
     */
    public function route(string $requestMethod, $routePath)
    {
        try {
            if (!array_key_exists($routePath, $this->routes))
                throw new Exception('Route does not exist!', 404);

            /** @var Route $route for the corresponding request method. */
            $route = $this->routes[$routePath];
            if ($requestMethod != $route->method())
                throw new Exception('Route does not exist for the request method!', 404);

            $this->processRequest($route);
        } catch (Exception $ex) {
            $this->catchBlock($ex);
        } finally {
            $this->finallyBlock();
        }
    }

    /**
     * Executes the request handler defined for script mode.
     * Triggers error if the router is not configured as a script.
     * @param string $requestMethod The request method.
     */
    public function dispatch(string $requestMethod)
    {
        try {
            if (!array_key_exists($requestMethod, $this->requestHandlers))
                throw new Exception('Request handler does not exist for the request method', 404);

            /** @var Route $requestHandler for the corresponding Http request method. */
            $requestHandler = $this->requestHandlers[$requestMethod];

            $this->processRequest($requestHandler);
        } catch (Exception $ex) {
            $this->catchBlock($ex);
        } finally {
            $this->finallyBlock();
        }
    }

    /**
     * Loads the specified file.
     * @param $filename string Path to the view file
     * @return mixed The value returned from the view (if any).
     */
    public function view(string $filename)
    {
        return require $filename;
    }

    /**
     * Executes the provided handlers in chronological order.
     * @param array<closure> $handlers representing the handlers to be executed.
     * @param string $logicZone Hint for debugging.
     */
    private function executeHandlers(array $handlers, string $logicZone)
    {
        /** @var Closure $handler */
        foreach ($handlers as $handler)
            $handler();
    }

    private function executeHandlersWithPayload(array $handlers, string $logicZone, $payload)
    {
        /** @var Closure $handler */
        foreach ($handlers as $handler) {
            if (is_array($payload))
                $payload = $handler(...$payload);
            else
                $handler($payload);
        }

        return $payload;
    }

    /**
     * Executes the pipeline against an Http request.
     * @param Route $route object containing the request processing logic.
     */
    private function processRequest(Route $route)
    {
        $result = null;

        /** Execute all the initialization logic in chronological order. */
        $this->executeHandlers($this->initializers, 'initializers');

        /** Execute all the middleware logic in chronological order. */
        $result = $this->executeHandlersWithPayload($this->middleware, 'middleware', $result);

        /** Executes all the pre-processing handlers in chronological order. */
        $result = $this->executeHandlersWithPayload($route->preHandlers(), 'pre-Handlers', $result);

        $handler = $route->requestHandler();

        /** Executes the request handler. */
        /** @var mixed $result */
        if (is_array($result))
            $result = $handler(...$result);
        else
            $result = $handler($result);

        /** Executes all the post-processing handlers in chronological order. */
        $this->executeHandlersWithPayload($route->postHandlers(), 'post-Handlers', $result);

        /** Executes all the dispatch handlers in chronological order. */
        $this->executeHandlersWithPayload($this->dispatchers, 'dispatchers', $result);
    }

    /**
     * Executes the common logic for the exception catch block.
     * @param Exception $ex Represents the exception raised.
     */
    private function catchBlock(Exception $ex)
    {
        foreach ($this->errorHandlers as $errorHandler)
            $errorHandler($ex);
    }

    /**
     * Executes the common logic for the finally block.
     */
    private function finallyBlock()
    {
        $this->executeHandlers($this->destructors, 'finally block');
    }

    /** Destruct the Router object. */
    public function __destruct()
    {
    }
}