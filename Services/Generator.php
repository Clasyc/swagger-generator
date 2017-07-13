<?php

namespace Clasyc\Bundle\SwaggerGeneratorBundle\Services;

use Symfony\Component\Security\Acl\Exception\Exception;

class Generator
{
    private $bundlePath;
    private $controllers;
    private $classes;

    public function __construct($config, $kernel, DirectoryFinder $finder){
        $this->config = $config;
        $this->kernel  = $kernel;
        $this->finder  = $finder;

        $this->bundlePath = $this->finder->findBundlesDirectory($this->kernel->getRootDir().'/../src', $this->config['bundle'])[0];

        $this->definitionPaths = $this->getDefinitionPaths();
        $this->controllers = $this->getControllerNames();
    }


    public function generate(){
        $this->generateControllerClasses();
        $this->generateMethods();
        $this->generateFiles();

        if(empty($this->bundlePath)){
            throw new Exception('Bundle "'.$this->config['bundle'].'" does not exists.');
        }
    }


    private function getDefinitionPaths(){
        $definitionDirectory = $this->bundlePath.'/'.$this->config['definition_path'];
        return json_decode(file_get_contents($definitionDirectory), true)['paths'];
    }


    private function generateControllerClasses(){
        foreach($this->controllers as $controllerName){
            $this->classes[$controllerName] = $this->generateControllerClass($controllerName);
        }
    }


    private function generateMethods(){
        foreach($this->definitionPaths as $path => $array){
            $this->proccedMethods($path, $array);
        }
    }


    private function proccedMethods($path, $methods){
        foreach($methods as $methodName => $method){
            if($methodName != 'options'){
                $this->addMethod($path, $methodName, $method);
                dump($path);
            }
        }
    }


    private function getNameFromPath($path){
        $array = array();
        $exploded = explode('/', $path);
        foreach($exploded as $part){
            $param = false;
            (preg_match('/{(.*?)}/', $part)) ? $param = true : $param = false;
            if(!$param){
                if($exploded[1] != $part){
                    $array['names'][] = $part;
                }
            }
        }
        return $array;
    }


    private function combineToString(array $names){
        $joined = '';
        foreach($names as $name){
            $joined .= ucfirst($name);
        }
        return $joined;
    }


    private function addMethod($path, $name, $methodArray){
        $body = '';
        $namesAndParams = $this->getNameFromPath($path);
        $class = $this->classes[$this->getControllerName($path)];
        $method = $class
            ->addMethod($name.$this->combineToString($namesAndParams['names'])."Action")
            ->setVisibility('public');

        foreach($methodArray['parameters'] as $parameter){
            $this->addParameters($parameter, $method, $class, $body);
        }
        $method->setBody($body);
    }


    private function addParameters($parameter, $method, $class, &$body){
        switch ($parameter['in']) {
            case 'body':
                $class->getNamespace()
                    ->addUse('Symfony\Component\HttpFoundation\Request');
                $method
                    ->addParameter('request')
                    ->setTypeHint('Request');
                $body .= '$'.$parameter['name'].' = $request->request->get(\''.$parameter['name'].'\');';
                $body .= "\n";
                break;
            case 'query':
                $class->getNamespace()
                    ->addUse('Symfony\Component\HttpFoundation\Request');
                $method
                    ->addParameter('request')
                    ->setTypeHint('Request');
                $body .= '$'.$parameter['name'].' = $request->get(\''.$parameter['name'].'\');';
                $body .= "\n";
                break;
            case 'path':
                $method
                    ->addParameter($parameter['name']);
                break;
        }
        $method->addComment($this->proccedParameterComment($parameter));
    }


    private function proccedParameterComment($parameter){
        $comment = "@Parameter(\n";
        foreach($parameter as $key => $param){
            if(!is_array($param)){
                $comment .= "   ".$key."=".$param."\n";
            }
        }
        $comment .= ")";
        return $comment;
    }


    private function getControllerNames(){
        $names = array();
        foreach($this->definitionPaths as $key => $path){
            $name = $this->getControllerName($key);
            if(!in_array($name, $names)){
                $names[] = $name;
            }
        }
        return $names;
    }


    private function getControllerName($string){
        return explode("/", $string)[1];
    }


    private function generateControllerClass($name){
        $namespaceString = str_replace('/', '\\', $this->getBundleNamespace().'/Controller');
        $namespace = new \Nette\PhpGenerator\PhpNamespace($namespaceString);
        $namespace->addUse('Symfony\Bundle\FrameworkBundle\Controller\Controller');

        $class = $namespace->addClass(ucfirst($name)."Controller");
        $class
            ->addExtend('Controller')
        ;
        return $class;
    }

    private function getBundleNamespace(){
        return explode('/src/', $this->bundlePath)[1];
    }


    private function generateFiles(){
        foreach($this->classes as $class){
            $php_content = "<?php\n\n".(string) $class->getNamespace();

            file_put_contents($this->bundlePath.'/Controller/'.ucfirst($class->getName()).".php", $php_content);
        }
    }
}