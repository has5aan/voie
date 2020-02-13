<?php

namespace Voie\PipelineService;

use Exception;

/**
 * Interface ErrorHandlerInterface maintains error handling logic.
 * @package Voie\PipelineService
 */
interface ErrorHandlerInterface
{
    /**
     * Handles the exception.
     * @param Exception $ex Represents the error.
     */
    public function handleError(Exception $ex);
}
