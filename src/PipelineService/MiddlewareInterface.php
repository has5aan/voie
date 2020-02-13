<?php

namespace Voie\PipelineService;

/**
 * Interface MiddlewareInterface provides middleware logic.
 * @package Voie\PipelineService
 */
interface MiddlewareInterface
{
    /**
     * Provides middleware logic.
     */
    public function middleware();
}
