<?php


namespace Voie;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

set_error_handler(function($errno , $errstr , $errfile , $errline) {
    throw new Exception(sprintf("%s(%s):%s", $errfile, $errline, $errstr), 500);
}, E_ALL);

use Closure;
use Exception;
use stdClass;
use Voie\PipelineService\DestructorInterface;
use Voie\PipelineService\DispatchInterface;
use Voie\PipelineService\ErrorHandlerInterface;
use Voie\PipelineService\MiddlewareInterface;

/**
 * Class Router: Provides Http web request routing infrastructure through an object instance.
 * <p>
 * Allows registering different route handlers. Each route handler responds to a route path and a specific Http
 * request method. Route handlers against an Http request method can be registered using, get(), put(), patch(), post()
 * and delete().
 * </p>
 * <p>
 * Router object also allows registering request handler only against any Http request method. The request handler
 * is registered using requestHandler().
 * </p>
 * <p>
 * Every request handler can also have a sequence of predecessors and successors that are executed in their
 * chronological order, before and after the execution of the actual request handler.
 * </p>
 * <p>
 * Initializers can be defined for the router object using initialize(). If defined, initialization logic
 * is executed for each request at the beginning of the request processing pipeline.
 * </p>
 * <p>
 * Router object can also contain middleware logic defined using middleware(). If defined, the middleware logic
 * is executed for each request after the execution of initializers.
 * </p>
 * <p>
 * Error handling and destruction logic can be defined using errorHandler() and destructor().
 * </p>
 * <p>
 * Router object can also be injected with service(s) using service().
 * The services may implement any combination of the interfaces defined in the service architecture
 * under @package Voie\PipelineService. These provide the router with middleware, dispatch, error handling
 * and destruction logic.
 * </p>
 * <p>
 * Summary: Router object provides an approach to build a request processing pipeline through request handlers for
 * a specific route using get(), put(), post(), patch() and delete(). It can also act as a script with a single
 * request handler for any Http request method that is defined through requestHandler(). Allows the construction of
 * request processing pipeline using initialize() and middleware(). errorHandler() and destructor()
 * provides a mechanism to define error handling and destruction logic.
 * </p>
 * <p>
 * service() provides a mechanism to plug services at different stages of request processing pipeline through the
 * implementation of any combination of interfaces defined under @package Voie\PipelineService.
 * </p>
 * <p>
 * The request processing pipeline logic injected through services are executed after the execution of the logic
 * maintained as Closures within the Router class.
 * </p>
 * <p>
 * All the middleware logic leading to the execution of the request against a request handler is executed in
 * chronological order after the execution of initialization logic.
 * </p>
 * @package Voie
 */
class Router extends stdClass
{
    /** @var Route[] Contains route handlers against a corresponding route path. */
    private $routes;

    /** @var Closure[] Contains initialization logic. */
    private $initializers;

    /** @var Closure[] Contains middleware logic. */
    private $middleware;

    /** @var Route[] Contains request handler for script mode against a combination of Http request methods. */
    private $requestHandlers;

    /** @var Closure[] Contains destruction logic. */
    private $destructors;

    /** @var Closure[] Contains error handling logic. */
    private $errorHandlers;

    /** @var Closure[] Contains services. */
    private $services;

    /** Initializes the internal data structures to maintain routes, middleware and error handlers. */
    public function __construct() {
        $this->routes = array();
        $this->initializers = array();
        $this->middleware = array();
        $this->destructors = array();
        $this->requestHandlers = array();
        $this->errorHandlers = array();
        $this->services = array();
    }

    /**
     * Appends a route that responds to HTTP-GET requests against a route path.
     * @param string $routePath Url route path.
     * @param Closure $requestHandler Defines the logic to handle the Http request.
     * @return Route Route object added to the list of routes.
     */
    public function get($routePath, $requestHandler) : Route
    {
        return $this->addRoute($routePath, $requestHandler, 'GET');
    }

    /**
     * Appends a route that responds to HTTP-PUT requests against a route path.
     * @param string $routePath Url route path.
     * @param Closure $requestHandler Defines the logic to handle the Http request.
     * @return Route Route object added to the route collection.
     */
    public function put($routePath, $requestHandler) : Route
    {
        return $this->addRoute($routePath, $requestHandler, 'PUT');
    }

    /**
     * Appends a route that responds to HTTP-PATCH requests against a route path.
     * @param string $routePath Url route path.
     * @param Closure $requestHandler Defines the logic to handle the Http request.
     * @return Route Route object added to the route collection.
     */
    public function patch($routePath, $requestHandler) : Route
    {
        return $this->addRoute($routePath, $requestHandler, 'PATCH');
    }

    /**
     * Appends a route that responds to HTTP-POST requests against a route path.
     * @param string $routePath Url route path.
     * @param Closure $requestHandler Defines the logic to handle the Http request.
     * @return Route Route object added to the route collection.
     */
    public function post($routePath, $requestHandler) : Route
    {
        return $this->addRoute($routePath, $requestHandler, 'POST');
    }

    /**
     * Appends a route that responds to HTTP-DELETE requests against a route path.
     * @param string $routePath Url route path.
     * @param Closure $requestHandler Defines the logic to handle the Http request.
     * @return Route Route object added to the route collection.
     */
    public function delete($routePath, $requestHandler) : Route
    {
        return $this->addRoute($routePath, $requestHandler, 'DELETE');
    }

    /**
     * Appends a route path for the specified Http request method and its request handler.
     * @param string $routePath Url route path.
     * @param Closure $requestHandler Route handler.
     * @param string $httpVerb Http request method.
     * @return Route The route object that was appended.
     */
    private function addRoute($routePath, $requestHandler, $httpVerb) : Route
    {
        $route = new Route($routePath, $requestHandler, $httpVerb);

        $this->routes[$routePath] = $route;

        return $route;
    }

    /**
     * Appends the request handler against the specified Http request method.
     * @param string $requestMethod Http request method.
     * @param Closure $requestHandler Defines the logic to handle the request.
     * @return Route The added Route object.
     */
    public function requestHandler($requestMethod, $requestHandler) : Route
    {
        $route = new Route('', $requestHandler, $requestMethod);

        $this->requestHandlers[$requestMethod] = $route;

        return $route;
    }

    /**
     * Appends initialization logic to the request handling pipeline.
     * @param Closure $handler Defines the initialization logic.
     * @return Router Current instance.
     */
    public function initialize($handler) : Router
    {
        $this->initializers[] = $handler;

        return $this;
    }

    /**
     * Appends middleware logic to the request handling pipeline.
     * @param Closure $handler Defines the middleware logic.
     * @return Router Current instance.
     */
    public function middleware($handler) : Router
    {
        $this->middleware[] = $handler;

        return $this;
    }

    /**
     * Appends error handling logic to the request handling pipeline.
     * @param Closure $errorHandler Defines the error handling logic.
     * @return Router Current instance.
     */
    public function errorHandler($errorHandler) : Router
    {
        $this->errorHandlers[] = $errorHandler;

        return $this;
    }

    /**
     * Appends destruction logic to the request handling pipeline.
     * @param Closure $handler Defines the destruction logic.
     * @return Router Current instance.
     */
    public function destructor($handler) : Router
    {
        $this->destructors[] = $handler;

        return $this;
    }

    /**
     * Appends services to the request handling pipeline.
     * @param mixed $service Service to be injected.
     * @return Router Current instance.
     */
    public function service($service) : Router
    {
        $this->services[] = $service;

        return $this;
    }

    /**
     * Executes the middleware logic contained in services.
     * The execution is performed as per the chronological order of the services.
     */
    private function serviceMiddleware()
    {
        foreach ($this->services as $middleware)
            if ($middleware instanceof MiddlewareInterface)
                $middleware->middleware();
    }

    /**
     * Executes the dispatch logic contained in services.
     * The execution is performed as per the chronological order of the services.
     * @param mixed $result Results to be dispatched.
     */
    private function serviceDispatch($result)
    {
        foreach ($this->services as $dispatcher)
            if ($dispatcher instanceof DispatchInterface)
                $dispatcher->dispatch($result);
    }

    /**
     * Executes the error handler contained in services.
     * The execution is performed as per the chronological order of the services.
     * @param Exception $ex Exception object to be provided to the error handling logic.
     */
    private function serviceErrorHandler(Exception $ex)
    {
        foreach ($this->services as $errorHandler)
            if ($errorHandler instanceof ErrorHandlerInterface)
                $errorHandler->handleError($ex);
    }

    /**
     * Executes the dispatch logic for erroneous cases.
     * The execution is performed as per the chronological order of the services.
     * @param Exception $ex Exception object to be provided to the error handling logic.
     */
    private function serviceErrorDispatch(Exception $ex)
    {
        foreach ($this->services as $handler)
            if ($handler instanceof ErrorHandlerInterface)
                $handler->handleError($ex);
    }

    /**
     * Executes the destructor logic contained in the services.
     * The execution is performed as per the chronological order of the services.
     */
    private function serviceDestructor()
    {
        foreach ($this->services as $destructor)
            if ($destructor instanceof  DestructorInterface)
                $destructor->destruct();
    }

    /**
     * Executes the request handler for a specific Http request method defined against a route path.
     * The route path is provided through the $routePath parameter.
     * <p>
     * Triggers error if the route handler for the specified route handler against the Http request method is not
     * defined.
     * </p>
     * <p>
     * The execution of every request handler is preceded by the execution of middleware handlers in chronological
     * order.
     * </p>
     * <p>
     * The execution of defined middleware is followed by the pre handlers for the request handler.
     * </p>
     * <p>
     * This is followed by the execution of request handler.
     * </p>
     * <p>
     * The execution of post handlers for the request handler continues after the request handler.
     * </p>
     * @param string $requestMethod The Http request method.
     * @param boolean|string $routePath Url route path against which the request handler logic is to be executed.
     */
    public function route($requestMethod, $routePath)
    {
        try {
            if (!array_key_exists($routePath, $this->routes))
                throw new Exception('Route does not exist!', 404);

            /** @var Route $route for the corresponding Http request method. */
            $route = $this->routes[$routePath];
            if ($requestMethod != $route->httpVerb())
                throw new Exception('Route does not exist for the Http request method!', 404);

            $this->processRequest($route);
        }
        catch (Exception $ex) {
            $this->catchBlock($ex);
        }
        finally {
            $this->finallyBlock();
        }
    }

    /**
     * Executes the request handler defined for script mode.
     * Triggers error if the router is not configured as a script.
     * @param string $requestMethod The Http request method.
     */
    public function dispatch($requestMethod)
    {
        try {
            if (!array_key_exists($requestMethod, $this->requestHandlers))
                throw new Exception('Request handler does not exist for the request method', 404);

            /** @var Route $requestHandler for the corresponding Http request method. */
            $requestHandler = $this->requestHandlers[$requestMethod];

            $this->processRequest($requestHandler);
        }
        catch (Exception $ex) {
            $this->catchBlock($ex);
        }
        finally {
            $this->finallyBlock();
        }
    }

    /**
     * Loads the specified file.
     * @param $filename string Path to the view file
     * @return mixed The value returned from the view (if any).
     */
    public function view($filename) {
        return require $filename;
    }

    /**
     * Executes the provided handlers in chronological order.
     * @param Closure[] $handlers representing the handlers to be executed.
     * @param string $logicZone Hint for debugging.
     */
    private function executeHandlers($handlers, $logicZone)
    {
        /** @var Closure $handler */
        foreach ($handlers as $handler)
            $handler();
    }

    /**
     * Executes the pipeline against an Http request.
     * @param Route $route object containing the request processing logic.
     */
    private function processRequest($route)
    {
        /** Execute all the initialization logic in chronological order. */
        $this->executeHandlers($this->initializers, 'initializers');

        /** Execute all the middleware logic in chronological order. */
        $this->executeHandlers($this->middleware, 'middleware');

        /** Execute all the middleware logic provided through services in chronological order. */
        $this->serviceMiddleware();

        /** Executes all the pre-processing handlers in chronological order. */
        $this->executeHandlers($route->preHandlers(), 'pre-Handlers');

        $handler = $route->requestHandler();

        /** Executes the request handler. */
        /** @var mixed $result */
        $result = $handler();

        /** Executes all the post-processing handlers in chronological order. */
        $this->executeHandlers($route->postHandlers(), 'post-Handlers');

        /** Executes the dispatch logic provided through services in chronological order. */
        $this->serviceDispatch($result);
    }

    /**
     * Executes the common logic for the exception catch block.
     * @param Exception $ex Represents the exception raised.
     */
    private function catchBlock($ex)
    {
        foreach ($this->errorHandlers as $errorHandler)
            $errorHandler($ex);

        /** Executes the error handling logic provided through services in chronological order. */
        $this->serviceErrorHandler($ex);

        /** Executes the dispatch logic for erroneous situation provided through services in chronological order. */
        $this->serviceErrorDispatch($ex);
    }

    /**
     * Executes the common logic for the finally block.
     */
    private function finallyBlock()
    {
        $this->executeHandlers($this->destructors, 'finally block');

        /** Executes the destruction logic provided through services in chronological order. */
        $this->serviceDestructor();
    }

    /** Destruct the Router object. */
    public function __destruct()
    {
    }
}