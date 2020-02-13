<?php


namespace Voie\PipelineService;

/**
 * Interface DispatchInterface provides dispatch logic.
 * @package Voie\PipelineService
 */
interface DispatchInterface
{
    /**
     * Provides dispatch logic.
     * @param mixed $result Results to be dispatched.
     */
    public function dispatch($result);
}
