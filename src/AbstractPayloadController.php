<?php

namespace Voie;

/**
 * Class AbstractPayloadController: Extends HTTP request handling interface provided by Voie\Routing\AbstractController.
 * @package Voie
 */
abstract class AbstractPayloadController extends AbstractController
{
    /** @var mixed Injected payload. */
    protected $payload;

    /**
     * Construct the controller with the provided services.
     * @param mixed $payload Represents injected JSON payload.
     * @param array $services Represents injected services.
     */
    public function __construct($payload, $services = array())
    {
        parent::__construct($services);

        $this->payload = $payload;
    }
}
