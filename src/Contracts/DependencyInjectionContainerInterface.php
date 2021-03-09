<?php


namespace Voie\Contracts;


interface DependencyInjectionContainerInterface
{
    /**
     * Returns an instance of the specified class.
     * @param string $name Name of the class.
     * @return mixed Instance of the provided class.
     */
    public function resolve(string $name);
}