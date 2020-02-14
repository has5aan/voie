<?php

namespace Voie;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use Exception;
use Voie\PipelineService\DestructorInterface;
use Voie\PipelineService\ErrorHandlerInterface;
use Voie\PipelineService\MiddlewareInterface;
use Voie\PipelineService\DispatchInterface;
use Voie\PipelineService\ErrorDispatchInterface;

set_error_handler(function($errno , $errstr , $errfile , $errline) {
    throw new Exception(sprintf("%s(%s):%s", $errfile, $errline, $errstr), 500);
}, E_ALL);

/**
 * Class AbstractController : Provides HTTP request handling infrastructure.
 * <p>
 * Controller class can be extended to handle different HTTP request types by providing
 * the corresponding method.
 * </p>
 * <p>
 * Controller object can also be injected with service(s) using service() or the constructor().
 * The services implement any combination of the interfaces defined in the service architecture
 * under @package Voie\Routing\PipelineService. These provide the controller with middleware, dispatch,
 * error handling and destruction logic that helps with the construction of a request handling pipeline.
 * </p>
 * <p>
 * action() can be used to trigger any specific public method implemented within a sub-class. This is specially useful
 * for a scenario where a certain method needs to be executed based on some external URL routing scheme.
 * </p>
 * @package Voie
 */
abstract class AbstractController
{
    /** @var array Contains the services. */
    private $services;

    /**
     * Construct the controller with the provided services.
     * @param array $services Represents injected services.
     */
    public function __construct($services = array())
    {
        $this->services = $services;
    }

    /**
     * Appends a service to the request processing pipeline.
     * @param mixed $service Service to be added to the pipeline.
     */
    public function service($service)
    {
        $this->services[] = $service;
    }

    /**
     * Executes the middleware logic contained in the services.
     * The execution is performed as per the chronological order of the services.
     */
    private function executeMiddleware()
    {
        foreach ($this->services as $middleware) {
            if ($middleware instanceof MiddlewareInterface)
                $middleware->middleware();
        }
    }

    /**
     * Executes the dispatch logic contained in the services.
     * The execution is performed as per the chronological order of the services.
     * @param mixed $result Results to be dispatched.
     */
    private function executeDispatch($result)
    {
        foreach ($this->services as $dispatcher) {
            if ($dispatcher instanceof DispatchInterface)
                $dispatcher->dispatch($result);
        }
    }

    /**
     * Executes the error handling logic contained in the services.
     * @param Exception $ex Exception object to be provided to the error handler logic.
     */
    private function handleError(Exception $ex)
    {
        foreach ($this->services as $handler) {
            if ($handler instanceof ErrorHandlerInterface)
                $handler->handleError($ex);
        }
    }

    /**
     * Executes the dispatch logic for erroneous cases.
     * @param Exception $ex Exception object to be provided to the error dispatch logic.
     */
    private function executeErrorDispatch(Exception $ex)
    {
        foreach ($this->services as $handler) {
            if ($handler instanceof ErrorDispatchInterface)
                $handler->errorDispatch($ex);
        }
    }

    /**
     * Executes the destructor logic contained in the services.
     */
    private function destruct()
    {
        foreach ($this->services as $destructor) {
            if ($destructor instanceof DestructorInterface)
                $destructor->destruct();
        }
    }

    /**
     * Executes the request handler corresponding to the provided request method.
     * @param string $requestMethod HTTP request type.
     */
    public function dispatch($requestMethod)
    {
        $result = null;

        try {
            $this->executeMiddleware();

            $result = $this->{$requestMethod}();

            $this->executeDispatch($result);
        }
        catch (Exception $ex) {
            $this->handleError($ex);

            $this->executeErrorDispatch($ex);
        }
        finally {
            $this->destruct();
        }
    }

    /**
     * Executes the method of the Controller represented by the provided parameter.
     * @param string $action The class method to be executed.
     */
    public function action($action)
    {
        $result = null;

        try {
            $this->executeMiddleware();

            $result = $this->{$action}();

            $this->executeDispatch($result);
        }
        catch (Exception $ex) {
            $this->handleError($ex);

            $this->executeErrorDispatch($ex);
        }
        finally {
            $this->destruct();
        }
    }
}
