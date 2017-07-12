<?php

namespace Clasyc\Bundle\SwaggerGeneratorBundle\Services;

class Generator
{
    private $bundles = array();

    public function __construct(array $bundles, $kernel, DirectoryFinder $finder){
        $this->bundles = $bundles;
        $this->kernel  = $kernel;
        $this->finder  = $finder;
    }

    public function findBundleDirectory(){
        dump($this->finder->findBundlesDirectory($this->kernel->getRootDir().'/../src', $this->bundles));
    }



}