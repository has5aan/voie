<?php

namespace Voie\PipelineService;

use Exception;

/**
 * Interface ErrorDispatchInterface provides dispatch logic for erroneous situation.
 * @package Voie\PipelineService
 */
interface ErrorDispatchInterface
{
    /**
     * Provides dispatch logic for erroneous situation.
     * @param Exception $ex Represents the error.
     */
    public function errorDispatch(Exception $ex);
}
