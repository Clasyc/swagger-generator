<?php

namespace Clasyc\Bundle\SwaggerGeneratorBundle\Services;

class Generator
{
    private $bundles = array();

    public function __construct(array $bundles){
        $this->bundles = $bundles;
    }

    
}