<?php

namespace Clasyc\Bundle\SwaggerGeneratorBundle\Services;

use Symfony\Component\Security\Acl\Exception\Exception;
use Clasyc\Bundle\SwaggerGeneratorBundle\libs\YmlGenerator\YmlGenerator;
use Symfony\Component\Yaml\Yaml;

class Generator
{
    private $bundlePath;
    private $controllers;
    private $classes;
    private $namespace;
    private $yamlGenerator;
    private $controllerPrefix = "Controller";
    private $actionPrefix = "Action";

    private $use = [
        'Controller' => 'Symfony\Bundle\FrameworkBundle\Controller\Controller',
        'Request'    => 'Symfony\Component\HttpFoundation\Request',
        'Route'      => 'Sensio\Bundle\FrameworkExtraBundle\Configuration\Route',
    ];

    public function __construct($config, $kernel, DirectoryFinder $finder, StringManager $sm)
    {
        $this->config  = $config;
        $this->kernel  = $kernel;
        $this->finder  = $finder;
        $this->sm      = $sm;

        $this->yamlGenerator = new YmlGenerator();

        $this->bundlePath = $this->finder->findBundlesDirectory($this->kernel->getRootDir().'/../src', $this->config['bundle'])[0];

        $this->definitionPaths = $this->getDefinitionPaths();
        $this->controllers = $this->getControllerNames();
        $this->namespace = $this->generateNamespace();
    }


    public function generate()
    {
        if(empty($this->bundlePath)){
            throw new Exception('Bundle "'.$this->config['bundle'].'" does not exists.');
        }

        $this->generateControllerClasses();
        $this->generateMethods();
        $this->generateFiles();
    }


    private function getDefinitionPaths()
    {
        $definitionDirectory = $this->bundlePath.'/'.$this->config['definition_path'];
        return json_decode(file_get_contents($definitionDirectory), true)['paths'];
    }


    private function generateControllerClasses()
    {
        foreach($this->controllers as $controllerName){
            $this->classes[$controllerName] = $this->generateControllerClass($controllerName);
        }
    }


    private function generateMethods()
    {
        foreach($this->definitionPaths as $path => $array){
            $this->proccedMethods($path, $array);
        }
    }


    private function proccedMethods($path, $methods)
    {
        foreach($methods as $methodName => $method){
            if($methodName != 'options'){
                $this->addMethod($path, $methodName, $method);
            }
        }
    }

    private function addRouteAnnotation($method, $path, $name = ''){
        $method->addComment("@Route(\"".$path."\", name=\"".$name.$this->sm->routeToDashString($path)."\")");
    }


    private function addYamlRoute($method, $path, $name){
        $namesAndParams = $this->sm->getNameFromPath($path);
        $yml_name = $this->getControllerName($path)."_".$name;
        $methodName = $this->config["bundle"].':'.ucfirst($this->getControllerName($path)).':'.$method;

        (!isset($namesAndParams["names"][1])) ? : $yml_name .= "_".$namesAndParams["names"][1];

        $this->yamlGenerator->addOption($yml_name);
        $this->yamlGenerator->addOption("path", $path, $yml_name);
        $this->yamlGenerator->addOption("defaults", array("_controller" => $methodName), $yml_name);
        $this->yamlGenerator->addOption("methods", '['.strtoupper($name).']', $yml_name);
    }

    
    private function addMethod($path, $name, $data)
    {
        $body = '';
        $namesAndParams = $this->sm->getNameFromPath($path);
        $class = $this->classes[$this->getControllerName($path)];
        $method = $class
            ->addMethod($name.$this->sm->combineToString($namesAndParams['names']).$this->actionPrefix)
            ->setVisibility('public');

        if ($this->config['route'] == 'annotation') {
            $this->namespace->addUse($this->use["Route"]);
            $this->addRouteAnnotation($method, $path, $name);
        } else if($this->config['route'] == 'yml') {
            $this->addYamlRoute($name.$this->sm->combineToString($namesAndParams['names']), $path, $name);
        }

        $this->functionOnElement($data, "summary", $method, "addComment");
        $this->functionOnElement($data, "description", $method, "addComment");

        if ($this->config["responses"] === true) {
            $method->addComment("Responses");
            $this->functionOnElement($data, "responses", $method, "addComment", ["json_encode", JSON_PRETTY_PRINT]);
        }


        if(isset($data['parameters'])){
            foreach($data['parameters'] as $parameter){
                $this->addParameters($parameter, $method, $body);
            }
        }
        $method->setBody($body);
    }


    private function functionOnElement($element, $key, $object, $function, array $transform = null)
    {
        (!isset($element[$key])) ? : ((!$transform) ? $object->$function($element[$key]."\n") :
            $object->$function($transform[0]($element[$key], $transform[1]))
        );
    }


    private function addParameters($parameter, $method, &$body)
    {
        if(!isset($parameter['in']) || !isset($parameter['name'])) throw new Exception("Parameter must have 'in' and 'name' keys.");

        $name = $parameter['name'];

        switch ($parameter['in']) {
            case 'body':
                $text = '$'.$name.' = $request->request->get(\''.$name.'\');';
                $this->addParameter($method, $body, $text);
                break;
            case 'query':
                $text = '$'.$name.' = $request->get(\''.$name.'\');';
                $this->addParameter($method, $body, $text);
                break;
            case 'path':
                $method
                    ->addParameter($parameter['name']);
                break;
        }
        $comment = $this->proccedCommentText($parameter);
        $method->addComment($this->proccedCommentHeaders("param", $comment));
    }


    private function addParameter($method, &$body, $text){
        $this->namespace->addUse($this->use["Request"]);
        $method
            ->addParameter('request')
            ->setTypeHint('Request');
        $body .= $text;
        $body .= "\n";
    }


    private function proccedCommentHeaders($header, $text)
    {
        return "@".$header."(\n".$text.")";
    }


    private function proccedCommentText(array $parameter)
    {
        $text = '';
        foreach($parameter as $key => $param){
            if(!is_array($param)){
                $text .= "   ".$key."=".$param."\n";
            }
        }
        return $text;
    }


    private function getControllerNames()
    {
        $names = array();
        foreach($this->definitionPaths as $key => $path){
            $name = $this->getControllerName($key);
            if(!in_array($name, $names)){
                $names[] = $name;
            }
        }
        return $names;
    }


    private function getControllerName($string)
    {
        return explode("/", $string)[1];
    }


    private function generateControllerClass($name)
    {
        $class = new \Nette\PhpGenerator\ClassType(ucfirst($name).$this->controllerPrefix);
        $class
            ->addExtend('Controller')
        ;
        return $class;
    }


    private function generateNamespace()
    {
        $namespaceString = str_replace('/', '\\', $this->getBundleNamespace().'/Controller');
        $namespace = new \Nette\PhpGenerator\PhpNamespace($namespaceString);
        $namespace->addUse($this->use['Controller']);
        return $namespace;
    }


    private function getBundleNamespace()
    {
        return explode('/src/', $this->bundlePath)[1];
    }


    private function generateFiles()
    {
        if ($this->config['route'] == 'yml') {

            if (!file_exists($this->bundlePath.'/Resources/config')) {
                mkdir($this->bundlePath.'/Resources/config', 0777, true);
            }

            file_put_contents($this->bundlePath.'/Resources/config/routing.yml', $this->yamlGenerator->getOutput());
        }
        foreach($this->classes as $class){
            $php_content = "<?php\n\n".(string) $this->namespace.(string) $class;
            file_put_contents($this->bundlePath.'/Controller/'.ucfirst($class->getName()).".php", $php_content);
        }
    }
}