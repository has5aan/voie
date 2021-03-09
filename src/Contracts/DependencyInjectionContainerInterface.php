<?php


namespace Voie\Contracts;


interface DependencyInjectionContainerInterface
{
    /**
     * Returns an instance of the specified class.
     * @param string $class Name of the class.
     * @return mixed Instance of the provided class.
     */
    public function create(string $class);
}