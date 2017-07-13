<?php

namespace Clasyc\Bundle\SwaggerGeneratorBundle\Services;

use Symfony\Component\Security\Acl\Exception\Exception;

class Generator
{
    private $bundlePath;
    private $controllers;
    private $classes;
    private $namespace;

    public function __construct($config, $kernel, DirectoryFinder $finder, StringManager $sm){
        $this->config  = $config;
        $this->kernel  = $kernel;
        $this->finder  = $finder;
        $this->sm      = $sm;

        $this->bundlePath = $this->finder->findBundlesDirectory($this->kernel->getRootDir().'/../src', $this->config['bundle'])[0];

        $this->definitionPaths = $this->getDefinitionPaths();
        $this->controllers = $this->getControllerNames();
        $this->namespace = $this->generateNamespace();
        dump($this->definitionPaths);
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
            }
        }
    }
    
    private function addMethod($path, $name, $methodArray){
        $body = '';
        $namesAndParams = $this->sm->getNameFromPath($path);
        $class = $this->classes[$this->getControllerName($path)];
        $method = $class
            ->addMethod($name.$this->sm->combineToString($namesAndParams['names'])."Action")
            ->setVisibility('public');

        $method->addComment("@Route(\"".$path."\", name=\"\")");

        $this->functionOnElement($methodArray, "summary", $method, "addComment");
        $this->functionOnElement($methodArray, "description", $method, "addComment");

        $method->addComment("Responses");
        $method->addComment(json_encode($methodArray['responses'], JSON_PRETTY_PRINT));

        if(isset($methodArray['parameters'])){
            foreach($methodArray['parameters'] as $parameter){
                $this->addParameters($parameter, $method, $body);
            }
        }
        $method->setBody($body);
    }

    private function functionOnElement($element, $key, $object, $function){
        (!isset($element[$key])) ?: $object->$function($element[$key]."\n");
    }


    private function addParameters($parameter, $method, &$body){
        if(!isset($parameter['in']) || !isset($parameter['name'])) throw new Exception("Parameter must have 'in' and 'name' keys.");

        switch ($parameter['in']) {
            case 'body':
                $this->namespace
                    ->addUse('Symfony\Component\HttpFoundation\Request');
                $method
                    ->addParameter('request')
                    ->setTypeHint('Request');
                $body .= '$'.$parameter['name'].' = $request->request->get(\''.$parameter['name'].'\');';
                $body .= "\n";
                break;
            case 'query':
                $this->namespace
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
        $comment = $this->proccedCommentText($parameter);
        $method->addComment($this->proccedCommentHeaders("param", $comment));
    }


    private function proccedCommentHeaders($header, $text){
        return "@".$header."(\n".$text.")";
    }


    private function proccedCommentText(array $parameter){
        $text = '';
        foreach($parameter as $key => $param){
            if(!is_array($param)){
                $text .= "   ".$key."=".$param."\n";
            }
        }
        return $text;
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
        $class = new \Nette\PhpGenerator\ClassType(ucfirst($name)."Controller");
        $class
            ->addExtend('Controller')
        ;
        return $class;
    }

    private function generateNamespace(){
        $namespaceString = str_replace('/', '\\', $this->getBundleNamespace().'/Controller');
        $namespace = new \Nette\PhpGenerator\PhpNamespace($namespaceString);
        $namespace->addUse('Symfony\Bundle\FrameworkBundle\Controller\Controller');
        return $namespace;
    }

    private function getBundleNamespace(){
        return explode('/src/', $this->bundlePath)[1];
    }


    private function generateFiles(){
        foreach($this->classes as $class){
            $php_content = "<?php\n\n".(string) $this->namespace.(string) $class;
            file_put_contents($this->bundlePath.'/Controller/'.ucfirst($class->getName()).".php", $php_content);
        }
    }
}